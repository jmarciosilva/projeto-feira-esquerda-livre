<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductQuestion;
use Illuminate\View\View;
use Livewire\Component;

class ProductQandA extends Component
{
    public Product $product;

    public string $question = '';

    public bool $submitted = false;

    public function submit(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'));
            return;
        }

        $this->validate([
            'question' => 'required|string|min:5|max:500',
        ]);

        ProductQuestion::create([
            'product_id' => $this->product->id,
            'user_id'    => auth()->id(),
            'question'   => trim($this->question),
        ]);

        $this->question  = '';
        $this->submitted = true;
    }

    public function render(): View
    {
        $answered = ProductQuestion::where('product_id', $this->product->id)
            ->whereNotNull('answered_at')
            ->where('is_visible', true)
            ->with('user')
            ->orderByDesc('answered_at')
            ->get();

        $myPending = auth()->check()
            ? ProductQuestion::where('product_id', $this->product->id)
                ->where('user_id', auth()->id())
                ->whereNull('answered_at')
                ->orderByDesc('created_at')
                ->get()
            : collect();

        return view('livewire.product-q-and-a', compact('answered', 'myPending'));
    }
}
