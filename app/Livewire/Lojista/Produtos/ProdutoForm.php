<?php

namespace App\Livewire\Lojista\Produtos;

use App\Enums\ItemType;
use App\Enums\Modality;
use App\Enums\PriceType;
use App\Livewire\Concerns\ValidatesFileUploads;
use App\Models\ContentCategory;
use App\Models\Product;
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

    public string $item_type = 'produto';

    public string $name = '';

    public string $slug = '';

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

    public int $sort_order = 0;

    public array $images = [];

    public $upload1 = null;

    public $upload2 = null;

    public $upload3 = null;

    public $upload4 = null;

    public function mount(?Product $product = null): void
    {
        if ($product && $product->exists) {
            $this->product = $product;
            $this->item_type = $product->item_type?->value ?? 'produto';
            $this->name = $product->name;
            $this->slug = $product->slug;
            $this->description = $product->description ?? '';
            $this->price = $product->price ? number_format((float) $product->price, 2, '.', '') : '';
            $this->weight = $product->weight ? number_format((float) $product->weight, 3, '.', '') : '';
            $this->height = $product->height ? number_format((float) $product->height, 2, '.', '') : '';
            $this->width = $product->width ? number_format((float) $product->width, 2, '.', '') : '';
            $this->length = $product->length ? number_format((float) $product->length, 2, '.', '') : '';
            $this->price_type = $product->price_type?->value ?? 'fixo';
            $this->modality = $product->modality?->value ?? 'presencial';
            $this->duration_min = $product->duration_min;
            $this->category_id = $product->category_id;
            $this->has_stock = $product->has_stock;
            $this->stock_quantity = $product->stock_quantity;
            $this->is_active = $product->is_active;
            $this->is_featured = $product->is_featured;
            $this->sort_order = $product->sort_order ?? 0;
            $this->images = $product->images ?? [];
        }
    }

    public function updatedName(): void
    {
        if (! $this->product) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function removeImage(int $index): void
    {
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
        foreach (['upload1', 'upload2', 'upload3', 'upload4'] as $field) {
            if (! $this->checkUploadedFile($this->{$field}, 4096, $field)) {
                return;
            }
        }

        $isProduto = $this->item_type === 'produto';

        $this->validate([
            'item_type' => 'required|in:produto,servico,cuidado',
            'name' => 'required|string|max:255',
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

        $expositor = auth()->user()->expositor;
        $images = $this->images;

        foreach (['upload1', 'upload2', 'upload3', 'upload4'] as $field) {
            if ($this->{$field} && count($images) < 4) {
                $images[] = $imageService->store($this->{$field}, 'products');
            }
        }

        $data = [
            'expositor_id' => $expositor->id,
            'item_type' => $this->item_type,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug ?: Str::slug($this->name),
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
            'sort_order' => $this->sort_order,
            'images' => array_values($images),
            'image_path' => $images[0]['medium'] ?? ($this->product?->image_path),
        ];

        if ($this->product && $this->product->exists) {
            $this->product->update($data);
            $label = ItemType::from($this->item_type)->label();
            session()->flash('success', "{$label} atualizado com sucesso!");
        } else {
            $product = Product::create($data);
            $this->redirect(route('lojista.produtos.edit', $product));

            return;
        }

        $this->images = $this->product->fresh()->images ?? [];
        $this->upload1 = $this->upload2 = $this->upload3 = $this->upload4 = null;
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
