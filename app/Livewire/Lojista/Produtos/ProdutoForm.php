<?php

namespace App\Livewire\Lojista\Produtos;

use App\Actions\Catalog\SaveProductWithOffer;
use App\CatalogIntelligence\Actions\GenerateListingSuggestion;
use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\Enums\ListingOutcomeState;
use App\Enums\ItemType;
use App\Enums\Modality;
use App\Enums\PriceType;
use App\Exceptions\SemAutoridadeCanonica;
use App\Livewire\Concerns\ValidatesFileUploads;
use App\Models\Ava\AvaCourse;
use App\Models\ContentCategory;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductOfferFaq;
use App\Services\ImageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProdutoForm extends Component
{
    use ValidatesFileUploads, WithFileUploads;

    /**
     * Campo da tela → chave de `ListingSuggestion::toArray()`.
     *
     * Só os três textos de identidade se aplicam (CAT-09). Palavras-chave e
     * informações faltantes são exibidas e não têm onde entrar: o domínio não tem
     * campo de palavra-chave.
     */
    private const CAMPOS_APLICAVEIS = [
        'name' => 'suggested_name',
        'short_description' => 'short_description',
        'description' => 'description',
    ];

    public ?Product $product = null;

    /**
     * A oferta do lojista autenticado sobre este item.
     *
     * O formulário continua sendo aberto pelo produto — a URL não mudou —, mas
     * o que o lojista edita é a oferta dele. Um item de catálogo que ele não
     * ofereça não é editável por ele, ainda que exista.
     */
    public ?ProductOffer $offer = null;

    public string $item_type = 'produto';

    public string $name = '';

    public string $slug = '';

    public string $short_description = '';

    public string $description = '';

    public string $price = '';

    public string $weight = '';

    public string $height = '';

    public string $width = '';

    public string $length = '';

    public string $price_type = 'fixo';

    public string $modality = 'presencial';

    public ?int $duration_min = null;

    public ?int $category_id = null;

    public bool $has_stock = true;

    public ?int $stock_quantity = null;

    public bool $is_active = true;

    public bool $is_featured = false;

    public bool $is_digital = false;

    public int $sort_order = 0;

    public array $images = [];

    /** @var array<int, array{question: string, answer: string}> */
    public array $faqs = [];

    public $upload1 = null;

    public $upload2 = null;

    public $upload3 = null;

    public $upload4 = null;

    /**
     * A última sugestão do assistente, na forma de `ListingSuggestion::toArray()`.
     *
     * CAT-09. É pré-visualização e nada mais: gerar não grava, e aplicar só copia
     * um campo daqui para a propriedade da tela — quem grava continua sendo o
     * `save()`, pela `SaveProductWithOffer`. `#[Locked]` porque o texto aplicado
     * tem de ser o que o servidor gerou, e não o que o navegador devolveu.
     *
     * @var array<string, mixed>|null
     */
    #[Locked]
    public ?array $sugestao = null;

    /** O `ListingOutcomeState` da última sugestão: é por ele, e não pela sugestão, que a tela fala do desfecho. */
    #[Locked]
    public ?string $desfecho = null;

    /**
     * Assinatura do contexto que produziu a última sugestão.
     *
     * É o que permite honrar `convidaARepetir()`: com os mesmos dados na tela e um
     * desfecho que não convida, pedir de novo traria a mesma resposta.
     */
    #[Locked]
    public ?string $assinaturaDaSugestao = null;

    /**
     * Se quem gerou a sugestão pode aplicá-la — nome, resumo e descrição.
     *
     * Perguntado ao gerar e reconferido a cada aplicação, e **não** a cada
     * renderização: com a sugestão na tela, todo round-trip do formulário — o nome
     * é `wire:model.live` — pagaria de novo as consultas de `updateCanonical`. Se a
     * delegação cair entre gerar e aplicar, a aplicação reconfere, recusa e o
     * valor se corrige; o salvamento continua protegido pela `SaveProductWithOffer`.
     */
    #[Locked]
    public bool $sugestaoAplicavel = false;

    /** Aviso da última ação sobre a sugestão; vale só para a renderização desta requisição. */
    private ?string $avisoDaSugestao = null;

    public function mount(?Product $product = null): void
    {
        if ($product && $product->exists) {
            $this->product = $product;
            $this->offer = $product->offers()
                ->where('expositor_id', auth()->user()?->expositor?->id)
                ->first();

            // Antes de ler qualquer campo: sem oferta própria não há o que
            // editar aqui, e a tela não deve nem se montar.
            $this->guardOwnership();

            $this->item_type = $product->item_type?->value ?? 'produto';
            $this->name = $product->name;
            $this->slug = $product->slug;
            $this->short_description = $product->short_description ?? '';
            $this->description = $product->description ?? '';
            $this->category_id = $product->category_id;
            $this->is_digital = (bool) $product->is_digital;

            // Daqui para baixo, tudo vem da oferta: é o que este lojista cobra
            // e oferece, não o que o item é.
            $offer = $this->offer;
            $this->price = $offer->price ? number_format((float) $offer->price, 2, '.', '') : '';
            $this->weight = $offer->weight ? number_format((float) $offer->weight, 3, '.', '') : '';
            $this->height = $offer->height ? number_format((float) $offer->height, 2, '.', '') : '';
            $this->width = $offer->width ? number_format((float) $offer->width, 2, '.', '') : '';
            $this->length = $offer->length ? number_format((float) $offer->length, 2, '.', '') : '';
            $this->price_type = $offer->price_type?->value ?? 'fixo';
            $this->modality = $offer->modality?->value ?? 'presencial';
            $this->duration_min = $offer->duration_min;
            $this->has_stock = $offer->has_stock;
            $this->stock_quantity = $offer->stock_quantity;
            $this->is_active = $offer->is_active;
            $this->is_featured = $offer->is_featured;
            $this->sort_order = $offer->sort_order ?? 0;

            // Imagem e FAQ passaram para a oferta na CAT-DOM-02E. O que este
            // lojista vê e edita é o conteúdo dele, não o do catálogo.
            $this->images = $offer->images ?? [];

            $this->faqs = $offer->offerFaqs
                ->map(fn ($f) => ['question' => $f->question, 'answer' => $f->answer])
                ->toArray();
        }
    }

    /**
     * Propriedade do item, reconferida a cada operação.
     *
     * Proteger apenas o mount() não basta: em Livewire cada método público é
     * um endpoint próprio, alcançável sem passar de novo pela tela que
     * renderizou o componente. Por isso o guard é chamado também no início de
     * save() e de removeImage(), antes de qualquer escrita.
     *
     * A comparação é direta contra o expositor do usuário autenticado, e não
     * uma Policy: `Gate::before` no AppServiceProvider concede tudo a admin, e
     * admin não possui expositor — uma Policy passaria e o código seguinte
     * quebraria no expositor nulo. Item novo não tem dono a conferir.
     */
    private function guardOwnership(): void
    {
        if (! $this->product || ! $this->product->exists) {
            return;
        }

        // A CAT-DOM-01 mudou o que se confere, não o rigor: o produto mestre
        // deixou de ter dono, e a pergunta passou a ser se ESTE lojista tem uma
        // oferta sobre ele. Item que ele não oferece não é editável por ele,
        // ainda que exista no catálogo — e um produto que ficou sem nenhuma
        // oferta não vira porta de entrada para ninguém.
        //
        // A CAT-DOM-02F não mudou a regra: mudou o lugar dela. A comparação
        // agora vem de `ProductOffer::pertenceAoExpositorDe()`, a definição
        // única de ownership comercial (D-02F-1), para que a resposta seja a
        // mesma aqui, na API, no painel e nas perguntas.
        abort_unless(
            $this->offer?->pertenceAoExpositorDe(auth()->user()) === true,
            403,
            'Este item pertence a outra loja.',
        );
    }

    public function addFaq(): void
    {
        if (count($this->faqs) >= 15) {
            return;
        }
        $this->faqs[] = ['question' => '', 'answer' => ''];
    }

    public function removeFaq(int $index): void
    {
        array_splice($this->faqs, $index, 1);
        $this->faqs = array_values($this->faqs);
    }

    public function updatedName(): void
    {
        if (! $this->product) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function removeImage(int $index): void
    {
        // Autorizar ANTES do I/O: apagar o arquivo e só depois descobrir que
        // não havia permissão deixaria o estrago feito.
        $this->guardOwnership();

        if (! isset($this->images[$index])) {
            return;
        }

        $removida = $this->images[$index];
        array_splice($this->images, $index, 1);

        if ($this->offer) {
            $this->offer->update(['images' => array_values($this->images)]);
        }

        // Só os arquivos da oferta saem do disco, e só os que mais nada
        // referencia. `ImageService::delete()` apaga por caminho e não conta
        // referências (M-05): com a imagem canônica ainda apontando para o
        // mesmo arquivo — o que acontece em item cuja oferta nunca teve imagem
        // própria e vinha exibindo a do catálogo por fallback —, apagar aqui
        // destruiria a imagem do item para todo mundo.
        $canonicos = $this->pathsCanonicos();

        foreach (['thumb', 'medium'] as $chave) {
            $path = $removida[$chave] ?? null;

            if ($path && ! in_array($path, $canonicos, true)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    /**
     * Todo caminho de arquivo que a imagem canônica do item referencia.
     *
     * @return list<string>
     */
    private function pathsCanonicos(): array
    {
        $paths = [];

        foreach ($this->product?->images ?? [] as $entrada) {
            foreach (['thumb', 'medium'] as $chave) {
                if (! empty($entrada[$chave])) {
                    $paths[] = $entrada[$chave];
                }
            }
        }

        if ($this->product?->image_path) {
            $paths[] = $this->product->image_path;
        }

        return $paths;
    }

    public function save(ImageService $imageService): void
    {
        $this->guardOwnership();

        foreach (['upload1', 'upload2', 'upload3', 'upload4'] as $field) {
            if (! $this->checkUploadedFile($this->{$field}, 4096, $field)) {
                return;
            }
        }

        $isProduto = $this->item_type === 'produto';

        $this->validate([
            'item_type' => 'required|in:produto,servico,cuidado',
            'name' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'price' => 'nullable|numeric|min:0',
            'weight' => $isProduto ? 'nullable|numeric|min:0.001' : 'nullable',
            'height' => $isProduto ? 'nullable|numeric|min:0.01' : 'nullable',
            'width' => $isProduto ? 'nullable|numeric|min:0.01' : 'nullable',
            'length' => $isProduto ? 'nullable|numeric|min:0.01' : 'nullable',
            'price_type' => 'nullable|in:fixo,por_hora,por_sessao,sob_consulta',
            'modality' => 'nullable|in:presencial,online,ambos',
            'duration_min' => 'nullable|integer|min:1|max:480',
            'category_id' => 'nullable|exists:content_categories,id',
            'stock_quantity' => $isProduto ? 'nullable|integer|min:0' : 'nullable',
            'sort_order' => 'integer|min:0',
            'upload1' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp',
            'upload2' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp',
            'upload3' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp',
            'upload4' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp',
        ]);

        // O LojistaMiddleware deixa admin e editor entrarem na área do lojista,
        // mas eles não têm expositor. Sem expositor não há dono a atribuir.
        $expositor = auth()->user()?->expositor;
        abort_if($expositor === null, 403, 'Sua conta não possui uma loja associada.');

        $images = $this->images;

        foreach (['upload1', 'upload2', 'upload3', 'upload4'] as $field) {
            if ($this->{$field} && count($images) < 4) {
                $images[] = $imageService->store($this->{$field}, 'products');
            }
        }

        // `expositor_id` não aparece aqui: quem decide o dono é a
        // SaveProductWithOffer, e só na criação. A divisão entre o que é
        // identidade do item e o que é condição de venda também mora lá — esta
        // tela apenas entrega o que o lojista preencheu.
        $data = [
            'item_type' => $this->item_type,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug ?: Str::slug($this->name),
            'short_description' => $this->short_description !== '' ? $this->short_description : null,
            'description' => $this->description,
            'price' => $this->price !== '' ? $this->price : null,
            'weight' => $isProduto && $this->weight !== '' ? $this->weight : null,
            'height' => $isProduto && $this->height !== '' ? $this->height : null,
            'width' => $isProduto && $this->width !== '' ? $this->width : null,
            'length' => $isProduto && $this->length !== '' ? $this->length : null,
            'price_type' => $isProduto ? 'fixo' : ($this->price_type ?: null),
            'modality' => $isProduto ? null : ($this->modality ?: null),
            'duration_min' => $isProduto ? null : $this->duration_min,
            'has_stock' => $isProduto && $this->has_stock,
            'stock_quantity' => ($isProduto && $this->has_stock) ? $this->stock_quantity : null,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'is_digital' => $this->is_digital,
            'sort_order' => $this->sort_order,
            // Campo da oferta desde a CAT-DOM-02E. `image_path` saiu do payload
            // junto: ele é espelho legado do primeiro medium canônico (D-1), e
            // a imagem que o lojista envia não é canônica.
            'images' => array_values($images),
        ];

        $editando = $this->product && $this->product->exists;

        try {
            $offer = app(SaveProductWithOffer::class)(
                $data,
                $expositor,
                $editando ? $this->offer : null,
                auth()->user(),
            );
        } catch (SemAutoridadeCanonica $semAutoridade) {
            // Nada foi gravado — a action roda em transação. O lojista fica na
            // tela com o que digitou e entende por que a alteração não passou.
            session()->flash('error', $semAutoridade->mensagemParaOLojista());

            return;
        }

        $this->syncFaqs($offer);
        $this->syncAvaCourse($offer->product);

        if (! $editando) {
            $this->redirect(route('lojista.produtos.edit', $offer->product));

            return;
        }

        $this->offer = $offer;
        $label = ItemType::from($this->item_type)->label();
        session()->flash('success', "{$label} atualizado com sucesso!");

        $this->images = $offer->fresh()->images ?? [];
        $this->upload1 = $this->upload2 = $this->upload3 = $this->upload4 = null;
    }

    private function syncAvaCourse(Product $product): void
    {
        if ($this->is_digital && ! $product->avaCourse) {
            AvaCourse::create(['product_id' => $product->id]);
        }
    }

    /**
     * A FAQ que o lojista escreve é da oferta dele (CAT-DOM-02E).
     *
     * O destino mudou; a semântica da tela, não: continua sendo substituição
     * integral do conjunto, com a posição vindo do índice do array. É o mesmo
     * `delete` + `create` de sempre, agora dentro de uma transação porque
     * `product_offer_faqs` tem `UNIQUE(product_offer_id, sort_order)` e um
     * conjunto meio apagado deixaria a próxima inserção colidindo.
     *
     * `product_faqs` **não** é tocada: ela passou a significar FAQ canônica, e
     * povoá-la a partir daqui faria a plataforma afirmar como verdade do
     * catálogo o que é resposta de um vendedor (D-CAT-16, D-CAT-18).
     */
    private function syncFaqs(ProductOffer $offer): void
    {
        $valid = array_values(array_filter(
            $this->faqs,
            fn ($f) => ! empty(trim($f['question'] ?? '')) && ! empty(trim($f['answer'] ?? '')),
        ));

        DB::transaction(function () use ($offer, $valid) {
            ProductOfferFaq::where('product_offer_id', $offer->id)->delete();

            foreach ($valid as $i => $faq) {
                ProductOfferFaq::create([
                    'product_offer_id' => $offer->id,
                    'question' => trim($faq['question']),
                    'answer' => trim($faq['answer']),
                    'sort_order' => $i,
                ]);
            }
        });
    }

    /**
     * Pede ao assistente uma sugestão para o que está na tela (CAT-09).
     *
     * A ordem é a de `save()` e `removeImage()`: propriedade primeiro — este
     * método é um endpoint próprio, e ninguém monta contexto sobre item de outra
     * loja. Depois, o mínimo para haver do que partir: tipo válido e nome.
     *
     * **Não grava nada**, e não trata falha: o assistente captura a do motor e a
     * do provider e as devolve como desfecho (CAT-05F, CAT-06G). Uma exceção que
     * atravesse é defeito, e tem de aparecer como tal.
     */
    public function gerarSugestao(GenerateListingSuggestion $assistente): void
    {
        $this->guardOwnership();

        $this->validate([
            'item_type' => 'required|in:produto,servico,cuidado',
            'name' => 'required|string|max:255',
        ]);

        $contexto = $this->contextoDaTela();
        $assinatura = hash('sha256', serialize([$this->product?->id, $contexto->toArray()]));

        if ($assinatura === $this->assinaturaDaSugestao
            && $this->desfecho !== null
            && ! ListingOutcomeState::from($this->desfecho)->convidaARepetir()) {
            $this->avisoDaSugestao = 'A sugestão abaixo já corresponde ao que está preenchido. Altere o tipo, o nome, as descrições ou a categoria para pedir outra.';

            return;
        }

        [$sugestao, , $desfecho] = $assistente->comContexto($contexto, $this->product);

        $this->sugestao = $sugestao->toArray();
        $this->desfecho = $desfecho->state->value;
        $this->assinaturaDaSugestao = $assinatura;
        $this->sugestaoAplicavel = $this->podeAplicarSugestao();
    }

    /**
     * Copia **um** campo da sugestão para a tela — nunca para o banco.
     *
     * Três recusas, todas sem efeito:
     *
     * - campo fora de nome, resumo e descrição, ou sem proposta;
     * - edição sem autoridade canônica: a tela nem oferece o botão, e o servidor
     *   confere de novo porque o botão não é a proteção;
     * - resumo ou descrição que já têm texto. O assistente só propõe onde havia
     *   vazio, e se o lojista escreveu depois de gerar, o texto dele fica. O nome
     *   é a exceção: está sempre preenchido, e só é trocado por este clique, com
     *   o nome atual visível ao lado da proposta.
     */
    public function aplicarSugestao(string $campo): void
    {
        $this->guardOwnership();

        $proposta = isset(self::CAMPOS_APLICAVEIS[$campo])
            ? ($this->sugestao[self::CAMPOS_APLICAVEIS[$campo]] ?? null)
            : null;

        if (! is_string($proposta)) {
            return;
        }

        // Reconfere a cada clique: a delegação pode ter caído depois de a
        // sugestão aparecer, e a tela passa a mostrar a recusa.
        $this->sugestaoAplicavel = $this->podeAplicarSugestao();

        if (! $this->sugestaoAplicavel) {
            return;
        }

        if ($campo !== 'name' && trim($this->{$campo}) !== '') {
            $this->avisoDaSugestao = 'Este campo já tem texto seu, e a sugestão não o substitui. Para usá-la, apague o texto atual e aplique de novo.';

            return;
        }

        $this->{$campo} = $proposta;

        // O mesmo efeito de digitar o nome: em item novo, o slug o acompanha.
        if ($campo === 'name') {
            $this->updatedName();
        }
    }

    /**
     * O contexto do assistente, montado com o que está **na tela** — salvo ou não.
     *
     * Campo a campo, nunca o array de propriedades (dívida C-1); `knownAttributes`
     * fica vazio porque o formulário não tem atributo estruturado. A categoria é
     * a selecionada agora, e a subida é a do próprio `ListingContext`. O pai vem
     * no `with('parent')`, que cobre os dois níveis que o catálogo usa; cada nível
     * acima disso custa uma consulta, até o teto de dez da subida (observação da
     * CAT-05G).
     */
    private function contextoDaTela(): ListingContext
    {
        $categoria = $this->category_id !== null
            ? ContentCategory::query()->with('parent')->find($this->category_id)
            : null;

        return ListingContext::paraItemNovo(
            itemType: ItemType::from($this->item_type),
            name: $this->name,
            categoryPath: ListingContext::caminhoDaCategoria($categoria),
            shortDescription: $this->short_description,
            description: $this->description,
        );
    }

    /**
     * Aplicar nome, resumo ou descrição é mexer na identidade do item.
     *
     * Item novo nasce com delegação para quem o cadastra, e não há o que conferir.
     * Na edição, a pergunta é a mesma que a `SaveProductWithOffer` faz ao salvar —
     * `updateCanonical` —, feita antes, para a tela não oferecer um caminho que
     * terminaria em `SemAutoridadeCanonica`. A recusa no salvamento continua lá.
     */
    private function podeAplicarSugestao(): bool
    {
        return $this->product === null
            || auth()->user()?->can('updateCanonical', $this->product) === true;
    }

    /**
     * O que a tela diz de cada desfecho — os oito, sem `default`.
     *
     * Nenhuma mensagem nomeia fornecedor, e `ProviderUnavailable` não é erro: sem
     * provider configurado, operar só com a inteligência da Feira é o estado
     * normal (D-CAT-06B-5).
     */
    private function mensagemDoDesfecho(ListingOutcomeState $estado): string
    {
        return match ($estado) {
            ListingOutcomeState::InternalKnowledgeSufficient => 'Sugestão preparada com o conhecimento da Feira.',
            ListingOutcomeState::InternalKnowledgeInsufficient => 'A Feira ainda não conhece este item o bastante para escrever por você. Veja abaixo o que vale informar.',
            ListingOutcomeState::InternalIntelligenceFailed => 'Não foi possível consultar o conhecimento da Feira agora. Você pode tentar de novo, ou preencher e salvar normalmente.',
            ListingOutcomeState::ProviderUnavailable => 'Sugestão preparada apenas com a inteligência interna da Feira.',
            ListingOutcomeState::ProviderFailed => 'O assistente complementar não respondeu agora. A sugestão abaixo usa só o conhecimento da Feira, e você pode tentar de novo.',
            ListingOutcomeState::ProviderResponseInvalid => 'A resposta do assistente complementar foi descartada por não seguir as regras da Feira. A sugestão abaixo usa só o conhecimento interno.',
            ListingOutcomeState::ExternalSuggestionUsed => 'Sugestão preparada com o conhecimento da Feira e complementada por um assistente externo.',
            ListingOutcomeState::ExternalSuggestionNotUsed => 'Sugestão preparada com o conhecimento da Feira; a consulta complementar não trouxe nada novo.',
        };
    }

    public function render(): View
    {
        $categories = ContentCategory::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('eixo')->orWhere('eixo', $this->item_type);
            })
            ->orderBy('name')
            ->get();

        $itemTypes = ItemType::cases();
        $priceTypes = PriceType::cases();
        $modalities = Modality::cases();

        $title = match ($this->item_type) {
            'servico' => $this->product ? 'Editar Serviço' : 'Novo Serviço',
            'cuidado' => $this->product ? 'Editar Cuidado & Bem Viver' : 'Novo Cuidado & Bem Viver',
            default => $this->product ? 'Editar Produto' : 'Novo Produto',
        };

        $estado = $this->desfecho !== null ? ListingOutcomeState::from($this->desfecho) : null;

        return view('livewire.lojista.produtos.produto-form', [
            ...compact('categories', 'itemTypes', 'priceTypes', 'modalities'),
            // O assistente (CAT-09). A autoridade não é perguntada aqui: ver
            // `$sugestaoAplicavel`.
            'mensagemDoDesfecho' => $estado !== null ? $this->mensagemDoDesfecho($estado) : null,
            'desfechoEhFalha' => $estado?->ehFalha() ?? false,
            'desfechoConvidaARepetir' => $estado?->convidaARepetir() ?? false,
            'avisoDaSugestao' => $this->avisoDaSugestao,
        ])->layout('lojista.layouts.app', ['title' => $title]);
    }
}
