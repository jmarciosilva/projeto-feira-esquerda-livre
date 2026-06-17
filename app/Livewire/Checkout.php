<?php

namespace App\Livewire;

use App\Models\SiteSetting;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\View\View;
use Livewire\Component;

class Checkout extends Component
{
    public bool   $showAuthModal = false;

    public string $customer_name     = '';
    public string $customer_whatsapp = '';
    public string $customer_email    = '';

    public string $delivery_type      = 'entrega';
    public ?int   $customer_address_id = null;

    public bool   $addingAddress      = false;
    public string $new_label          = '';
    public string $new_cep            = '';
    public string $new_rua            = '';
    public string $new_numero         = '';
    public string $new_complemento    = '';
    public string $new_bairro         = '';
    public string $new_cidade         = '';
    public string $new_estado         = '';

    public function mount(): void
    {
        if (auth()->guest()) {
            $this->showAuthModal = true;
            return;
        }

        $user = auth()->user();
        $this->customer_name     = $user->name;
        $this->customer_email    = $user->email ?? '';
        $this->customer_whatsapp = $user->whatsapp ?? '';

        $defaultAddress = $user->addresses()->where('is_default', true)->first()
            ?? $user->addresses()->first();
        $this->customer_address_id = $defaultAddress?->id;
    }

    public function startAddingAddress(): void
    {
        $this->addingAddress = true;
    }

    public function saveNewAddress(): void
    {
        $this->validate([
            'new_label'       => 'required|string|max:50',
            'new_cep'         => 'required|string|max:9',
            'new_rua'         => 'required|string|max:255',
            'new_numero'      => 'required|string|max:20',
            'new_complemento' => 'nullable|string|max:255',
            'new_bairro'      => 'required|string|max:255',
            'new_cidade'      => 'required|string|max:255',
            'new_estado'      => 'required|string|size:2',
        ], [], [
            'new_label'  => 'identificação (ex: Casa)',
            'new_cep'    => 'CEP',
            'new_rua'    => 'rua',
            'new_numero' => 'número',
            'new_bairro' => 'bairro',
            'new_cidade' => 'cidade',
            'new_estado' => 'estado',
        ]);

        $hasAddresses = auth()->user()->addresses()->exists();

        $address = auth()->user()->addresses()->create([
            'label'       => $this->new_label,
            'cep'         => $this->new_cep,
            'rua'         => $this->new_rua,
            'numero'      => $this->new_numero,
            'complemento' => $this->new_complemento ?: null,
            'bairro'      => $this->new_bairro,
            'cidade'      => $this->new_cidade,
            'estado'      => strtoupper($this->new_estado),
            'is_default'  => ! $hasAddresses,
        ]);

        $this->customer_address_id = $address->id;
        $this->addingAddress       = false;
        $this->reset([
            'new_label', 'new_cep', 'new_rua', 'new_numero',
            'new_complemento', 'new_bairro', 'new_cidade', 'new_estado',
        ]);
    }

    public function confirmar(OrderService $orderService, CartService $cart)
    {
        if (auth()->guest()) {
            $this->showAuthModal = true;
            return;
        }

        if ($cart->items()->isEmpty()) {
            session()->flash('error', 'Seu carrinho está vazio.');
            return;
        }

        $this->validate([
            'customer_name'     => 'required|string|max:255',
            'customer_whatsapp' => 'required|string|max:20',
            'customer_email'    => 'nullable|email|max:255',
            'delivery_type'     => 'required|in:retirada,entrega',
        ], [], [
            'customer_name'     => 'nome',
            'customer_whatsapp' => 'WhatsApp',
        ]);

        $address = null;

        if ($this->delivery_type === 'entrega') {
            $address = auth()->user()->addresses()->find($this->customer_address_id);

            if (! $address) {
                $this->addError('customer_address_id', 'Selecione ou cadastre um endereço de entrega.');
                return;
            }
        }

        $order = $orderService->createFromCart([
            'customer_name'        => $this->customer_name,
            'customer_whatsapp'    => $this->customer_whatsapp,
            'customer_email'       => $this->customer_email ?: null,
            'delivery_type'        => $this->delivery_type,
            'customer_address_id'  => $address?->id,
            'address_cep'          => $address?->cep,
            'address_rua'          => $address?->rua,
            'address_numero'       => $address?->numero,
            'address_complemento'  => $address?->complemento,
            'address_bairro'       => $address?->bairro,
            'address_cidade'       => $address?->cidade,
            'address_estado'       => $address?->estado,
        ], $cart);

        return $this->redirect(route('pedido.show', $order->reference), navigate: false);
    }

    public function render(CartService $cart): View
    {
        return view('livewire.checkout', [
            'grouped'   => $cart->grouped(),
            'total'     => $cart->total(),
            'settings'  => SiteSetting::instance(),
            'addresses' => auth()->check()
                ? auth()->user()->addresses()->orderByDesc('is_default')->get()
                : collect(),
        ]);
    }
}
