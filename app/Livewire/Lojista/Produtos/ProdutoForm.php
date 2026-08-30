<?php

namespace App\Livewire\Lojista\Produtos;

use App\Actions\Catalog\SaveProductWithOffer;
use App\Enums\ItemType;
use App\Enums\Modality;
use App\Enums\PriceType;
use App\Exceptions\SemAutoridadeCanonica;
use App\Livewire\Concerns\ValidatesFileUploads;
use App\Models\Ava\AvaCourse;
use App\Models\ContentCategory;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\ProductOffer;
use App\Services\ImageService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProdutoForm extends Component
{
    use ValidatesFileUploads, WithFileUploads;

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
            $this->images = $product->images ?? [];

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

            $this->faqs = $product->faqs
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
        abort_unless(
            $this->offer !== null
                && $this->offer->expositor_id === auth()->user()?->expositor?->id,
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

        if (isset($this->images[$index])) {
            Storage::disk('public')->delete($this->images[$index]['thumb'] ?? '');
            Storage::disk('public')->delete($this->images[$index]['medium'] ?? '');
            array_splice($this->images, $index, 1);
            if ($this->product) {
                $this->product->update(['images' => array_values($this->images)]);
            }
        }
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
            'images' => array_values($images),
            'image_path' => $images[0]['medium'] ?? ($this->product?->image_path),
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

        $this->syncFaqs($offer->product_id);
        $this->syncAvaCourse($offer->product);

        if (! $editando) {
            $this->redirect(route('lojista.produtos.edit', $offer->product));

            return;
        }

        $this->offer = $offer;
        $label = ItemType::from($this->item_type)->label();
        session()->flash('success', "{$label} atualizado com sucesso!");

        $this->images = $this->product->fresh()->images ?? [];
        $this->upload1 = $this->upload2 = $this->upload3 = $this->upload4 = null;
    }

    private function syncAvaCourse(Product $product): void
    {
        if ($this->is_digital && ! $product->avaCourse) {
            AvaCourse::create(['product_id' => $product->id]);
        }
    }

    private function syncFaqs(int $productId): void
    {
        ProductFaq::where('product_id', $productId)->delete();

        $valid = array_values(array_filter(
            $this->faqs,
            fn ($f) => ! empty(trim($f['question'] ?? '')) && ! empty(trim($f['answer'] ?? '')),
        ));

        foreach ($valid as $i => $faq) {
            ProductFaq::create([
                'product_id' => $productId,
                'question' => trim($faq['question']),
                'answer' => trim($faq['answer']),
                'sort_order' => $i,
            ]);
        }
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

        return view('livewire.lojista.produtos.produto-form', compact('categories', 'itemTypes', 'priceTypes', 'modalities'))
            ->layout('lojista.layouts.app', ['title' => $title]);
    }
}
