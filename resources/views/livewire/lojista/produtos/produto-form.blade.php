<div>
    @if(session('success'))
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-base font-medium flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif

    <div class="mb-5">
        <a href="{{ route('lojista.produtos.index') }}"
           class="inline-flex items-center gap-2 text-base font-semibold"
           style="color: #C47A00;">
            ← Voltar para Meus Produtos
        </a>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Coluna Principal --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Seletor de Eixo --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Tipo de Oferta</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @foreach($itemTypes as $type)
                        <label class="relative flex flex-col items-center gap-2 p-4 rounded-xl border-2 cursor-pointer transition-all"
                               :style="$wire.item_type === '{{ $type->value }}'
                                    ? 'border-color:#E8A000; background:#FFFBEB;'
                                    : 'border-color:#e5e7eb; background:#fff;'">
                            <input type="radio" wire:model.live="item_type" value="{{ $type->value }}" class="sr-only">
                            <span class="text-3xl">{{ $type->emoji() }}</span>
                            <span class="text-sm font-bold text-center leading-tight" style="color:#3D3000;">{{ $type->label() }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Dados básicos --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
                    <h2 class="text-lg font-bold text-gray-900">
                        @if($item_type === 'servico') Informações do Serviço
                        @elseif($item_type === 'cuidado') Informações do Cuidado
                        @else Informações do Produto
                        @endif
                    </h2>

                    <div>
                        <label class="block text-base font-semibold text-gray-700 mb-2">
                            @if($item_type === 'servico') Nome do Serviço *
                            @elseif($item_type === 'cuidado') Nome do Cuidado *
                            @else Nome do Produto *
                            @endif
                        </label>
                        <input wire:model.live="name" type="text"
                               placeholder="{{ $item_type === 'servico' ? 'Ex.: Aula de Design Gráfico' : ($item_type === 'cuidado' ? 'Ex.: Massagem Relaxante 60min' : 'Ex.: Bolsa de Macramê Artesanal') }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000] @error('name') border-red-400 @enderror"
                               style="min-height: 52px;">
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-base font-semibold text-gray-700 mb-2">Descrição curta</label>
                        <p class="text-sm text-gray-500 mb-2">Resumo rápido, que aparece em listagens e ao compartilhar o link.</p>
                        <textarea wire:model="short_description" rows="2" maxlength="500"
                                  placeholder="{{ $item_type === 'servico' ? 'Ex.: Atendimento de costura sob medida, com ajuste e prova incluídos.' : ($item_type === 'cuidado' ? 'Ex.: Sessão de massagem relaxante de 60 minutos, em consultório.' : 'Ex.: Peça artesanal em crochê para decoração de abajures.') }}"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000] resize-none"></textarea>
                        <p class="mt-1 text-sm text-gray-400">Até 500 caracteres.</p>
                        @error('short_description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-base font-semibold text-gray-700 mb-2">Descrição completa</label>
                        <p class="text-sm text-gray-500 mb-2">Detalhes de {{ $item_type === 'servico' ? 'serviço' : ($item_type === 'cuidado' ? 'cuidado' : 'produto') }}, exibidos na página do item.</p>
                        <textarea wire:model="description" rows="5"
                                  placeholder="{{ $item_type === 'servico' ? 'Descreva o serviço: o que inclui, para quem é indicado, como funciona...' : ($item_type === 'cuidado' ? 'Descreva a prática: benefícios, método, contraindicações, o que esperar...' : 'Descreva seu produto: materiais, tamanho, como foi feito...') }}"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000] resize-none"></textarea>
                    </div>

                    {{-- Campos de Preço --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @if($item_type !== 'produto')
                        <div>
                            <label class="block text-base font-semibold text-gray-700 mb-2">Tipo de Cobrança</label>
                            <select wire:model.live="price_type"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000]"
                                    style="min-height: 52px;">
                                @foreach($priceTypes as $pt)
                                <option value="{{ $pt->value }}">{{ $pt->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        @if($item_type === 'produto' || $price_type !== 'sob_consulta')
                        <div>
                            <label class="block text-base font-semibold text-gray-700 mb-2">
                                Preço (R$)
                                @if($item_type !== 'produto' && $price_type === 'por_hora') <span class="font-normal text-sm text-gray-400">por hora</span>@endif
                                @if($item_type !== 'produto' && $price_type === 'por_sessao') <span class="font-normal text-sm text-gray-400">por sessão</span>@endif
                            </label>
                            <input wire:model="price" type="number" step="0.01" min="0" placeholder="0,00"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000]"
                                   style="min-height: 52px;">
                            @error('price')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        @endif
                    </div>

                    {{-- Campos exclusivos de Serviço / Cuidado --}}
                    @if($item_type !== 'produto')
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-base font-semibold text-gray-700 mb-2">Modalidade</label>
                            <select wire:model="modality"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000]"
                                    style="min-height: 52px;">
                                @foreach($modalities as $mod)
                                <option value="{{ $mod->value }}">{{ $mod->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-base font-semibold text-gray-700 mb-2">Duração (minutos) <span class="font-normal text-sm text-gray-400">opcional</span></label>
                            <input wire:model="duration_min" type="number" min="1" max="480" placeholder="Ex.: 60"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000]"
                                   style="min-height: 52px;">
                            @error('duration_min')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    @endif

                    @if($item_type === 'produto')
                    <div class="rounded-2xl border border-yellow-100 p-4" style="background:#FFFBEB;">
                        <h3 class="text-base font-bold mb-1" style="color:#3D3000;">Dados para Frete</h3>
                        <p class="text-sm text-gray-500 mb-4">Preencha quando quiser liberar cotação automática pelo Melhor Envio.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Peso (kg)</label>
                                <input wire:model="weight" type="number" step="0.001" min="0" placeholder="0.300"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000]"
                                       style="min-height: 52px;">
                                @error('weight')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Altura (cm)</label>
                                <input wire:model="height" type="number" step="0.01" min="0" placeholder="4"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000]"
                                       style="min-height: 52px;">
                                @error('height')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Largura (cm)</label>
                                <input wire:model="width" type="number" step="0.01" min="0" placeholder="16"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000]"
                                       style="min-height: 52px;">
                                @error('width')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Comprimento (cm)</label>
                                <input wire:model="length" type="number" step="0.01" min="0" placeholder="24"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000]"
                                       style="min-height: 52px;">
                                @error('length')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Categoria --}}
                    <div>
                        <label class="block text-base font-semibold text-gray-700 mb-2">Categoria</label>
                        <select wire:model="category_id"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000]"
                                style="min-height: 52px;">
                            <option value="">Selecione...</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Fotos --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-1">
                        @if($item_type === 'servico') Fotos / Portfólio
                        @elseif($item_type === 'cuidado') Fotos do Espaço / Prática
                        @else Fotos do Produto
                        @endif
                    </h2>
                    <p class="text-sm text-gray-500 mb-5">Até 4 fotos. As imagens são convertidas para WebP automaticamente. Recomendado: fotos quadradas (1:1).</p>

                    {{-- Fotos existentes --}}
                    @if(count($images))
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                        @foreach($images as $i => $img)
                        <div class="relative rounded-xl overflow-hidden aspect-square border border-gray-200">
                            <img src="{{ Storage::url($img['medium'] ?? $img['thumb']) }}" alt="Foto {{ $i+1 }}"
                                 class="w-full h-full object-cover">
                            <button type="button" wire:click="removeImage({{ $i }})"
                                    class="absolute top-1.5 right-1.5 w-7 h-7 bg-red-500 text-white rounded-full flex items-center justify-center text-xs font-bold hover:bg-red-600">
                                ×
                            </button>
                            @if($i === 0)
                            <span class="absolute bottom-1.5 left-1.5 text-xs bg-black/60 text-white px-2 py-0.5 rounded-full">Principal</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Novos uploads --}}
                    @if(count($images) < 4)
                    <div class="grid grid-cols-1 sm:grid-cols-{{ min(4 - count($images), 4) }} gap-3">
                        @foreach(range(1, 4 - count($images)) as $n)
                        @php $field = 'upload' . $n; @endphp
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Foto {{ count($images) + $n }}</label>
                            <input type="file" wire:model="{{ $field }}" accept="image/*"
                                   class="w-full text-sm text-gray-500 file:mr-2 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-yellow-50 file:text-yellow-800 hover:file:bg-yellow-100">
                            @error($field)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-sm text-gray-500">Limite de 4 fotos atingido. Remova uma para adicionar outra.</p>
                    @endif
                </div>

                {{-- FAQ --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-start justify-between mb-1">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Perguntas Frequentes</h2>
                            <p class="text-sm text-gray-500 mt-0.5">
                                Antecipe as dúvidas dos clientes. Exibido na página pública do produto.
                            </p>
                        </div>
                        @if(count($faqs) < 15)
                        <button type="button" wire:click="addFaq"
                                class="flex-shrink-0 ml-4 px-4 py-2 rounded-xl text-sm font-bold transition-colors"
                                style="background:#FFFBEB; color:#C47A00; border:2px solid #E8A000;">
                            + Adicionar
                        </button>
                        @endif
                    </div>

                    @if(count($faqs) === 0)
                    <div class="mt-5 py-8 text-center rounded-xl border-2 border-dashed border-gray-200">
                        <p class="text-3xl mb-2">❓</p>
                        <p class="text-sm text-gray-500">Nenhuma pergunta ainda.</p>
                        <p class="text-xs text-gray-400 mt-1">Exemplos: "Qual o prazo de entrega?", "Tem garantia?", "Aceita encomenda?"</p>
                        <button type="button" wire:click="addFaq"
                                class="mt-4 px-5 py-2 rounded-xl text-sm font-bold"
                                style="background:#E8A000; color:#fff;">
                            Adicionar primeira pergunta
                        </button>
                    </div>
                    @else
                    <div class="mt-5 space-y-4">
                        @foreach($faqs as $i => $faq)
                        <div class="p-4 rounded-xl border border-gray-200 bg-gray-50 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pergunta {{ $i + 1 }}</span>
                                <button type="button" wire:click="removeFaq({{ $i }})"
                                        class="text-xs font-semibold text-red-500 hover:text-red-700 transition-colors">
                                    Remover
                                </button>
                            </div>
                            <div>
                                <input wire:model="faqs.{{ $i }}.question"
                                       type="text"
                                       placeholder="Ex.: Qual o prazo de entrega?"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl text-base bg-white focus:outline-none focus:ring-2 focus:ring-[#E8A000]"
                                       style="min-height:48px;">
                                @error("faqs.{$i}.question")
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <textarea wire:model="faqs.{{ $i }}.answer"
                                          rows="3"
                                          placeholder="Escreva a resposta..."
                                          class="w-full px-4 py-3 border border-gray-300 rounded-xl text-base bg-white focus:outline-none focus:ring-2 focus:ring-[#E8A000] resize-none"></textarea>
                                @error("faqs.{$i}.answer")
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

            </div>

            {{-- Coluna Lateral --}}
            <div class="space-y-6">

                {{-- Estoque (apenas para produtos físicos) --}}
                @if($item_type === 'produto')
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h2 class="text-lg font-bold text-gray-900">Estoque</h2>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model.live="has_stock" class="w-5 h-5 rounded border-gray-300" style="accent-color: #E8A000;">
                        <span class="text-base font-medium text-gray-700">Produto disponível em estoque</span>
                    </label>

                    @if($has_stock)
                    <div>
                        <label class="block text-base font-semibold text-gray-700 mb-2">Quantidade em estoque</label>
                        <input wire:model="stock_quantity" type="number" min="0" placeholder="Deixe vazio se não controla"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000]"
                               style="min-height: 52px;">
                    </div>
                    @endif
                </div>
                @endif

                {{-- Status --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h2 class="text-lg font-bold text-gray-900">Visibilidade</h2>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="w-5 h-5 rounded border-gray-300" style="accent-color: #E8A000;">
                        <div>
                            <span class="text-base font-medium text-gray-700">Exibir na loja</span>
                            <p class="text-sm text-gray-400">Produto visível para os clientes</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="is_featured" class="w-5 h-5 rounded border-gray-300" style="accent-color: #E8A000;">
                        <div>
                            <span class="text-base font-medium text-gray-700">Destaque na Home</span>
                            <p class="text-sm text-gray-400">Aparece na página inicial</p>
                        </div>
                    </label>
                    <div class="border-t border-gray-100 pt-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" wire:model.live="is_digital" class="mt-0.5 w-5 h-5 rounded border-gray-300" style="accent-color: #E8A000;">
                            <div>
                                <span class="text-base font-medium text-gray-700">Produto digital / Curso online</span>
                                <p class="text-sm text-gray-400">Entregue digitalmente — sem necessidade de frete. O comprador recebe acesso imediato ao conteúdo.</p>
                            </div>
                        </label>
                        @if($is_digital && $product?->avaCourse)
                        <p class="mt-2 ml-8 text-sm font-medium" style="color:#E8A000;">
                            ✓ Curso AVA criado — conteúdo configurável na Fase 8.2
                        </p>
                        @endif
                    </div>
                </div>

                {{-- Salvar --}}
                <button type="submit"
                        class="w-full py-4 rounded-xl text-white text-lg font-bold transition-colors"
                        style="background-color: #E8A000; min-height: 60px;">
                    <span wire:loading.remove>
                        @if($product)
                            Salvar Alterações
                        @elseif($item_type === 'servico')
                            Cadastrar Serviço
                        @elseif($item_type === 'cuidado')
                            Cadastrar Cuidado
                        @else
                            Cadastrar Produto
                        @endif
                    </span>
                    <span wire:loading>Salvando...</span>
                </button>

                @if($product)
                <a href="{{ route('loja.show', $product->expositor->slug) }}" target="_blank"
                   class="block text-center py-3 rounded-xl border-2 font-semibold text-base transition-colors"
                   style="border-color: #E8A000; color: #C47A00;">
                    Ver Minha Loja →
                </a>
                @endif
            </div>

        </div>
    </form>
</div>
