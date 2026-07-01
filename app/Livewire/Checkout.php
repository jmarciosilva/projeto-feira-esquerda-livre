<?php

namespace App\Livewire;

use App\Models\SiteSetting;
use App\Services\CartService;
use App\Services\MercadoPagoService;
use App\Services\OrderService;
use App\Services\Shipping\MelhorEnvioService;
use Illuminate\View\View;
use Livewire\Component;
use Throwable;

class Checkout extends Component
{
    public bool $showAuthModal = false;

    public string $customer_name = '';

    public string $customer_whatsapp = '';

    public string $customer_email = '';

    public string $delivery_type = 'entrega';

    public ?int $customer_address_id = null;

    public bool $addingAddress = false;

    public string $new_label = '';

    public string $new_cep = '';

    public string $new_rua = '';

    public string $new_numero = '';

    public string $new_complemento = '';

    public string $new_bairro = '';

    public string $new_cidade = '';

    public string $new_estado = '';

    public string $shipping_destination_zipcode = '';

    public array $shipping_quotes = [];

    public array $selected_shipping_options = [];

    public function mount(): void
    {
        if (auth()->guest()) {
            $this->showAuthModal = true;

            return;
        }

        $user = auth()->user();
        $this->customer_name = $user->name;
        $this->customer_email = $user->email ?? '';
        $this->customer_whatsapp = $user->whatsapp ?? '';

        $defaultAddress = $user->addresses()->where('is_default', true)->first()
            ?? $user->addresses()->first();
        $this->customer_address_id = $defaultAddress?->id;
        $this->shipping_destination_zipcode = $defaultAddress?->cep ?? '';
    }

    public function updateQty(int $itemId, int $qty, CartService $cart): void
    {
        $cart->update($itemId, $qty);
        $this->resetShippingQuotes();
        $this->dispatch('cart-updated');
    }

    public function removeItem(int $itemId, CartService $cart): void
    {
        $cart->remove($itemId);
        $this->resetShippingQuotes();
        $this->dispatch('cart-updated');
    }

    public function updatedDeliveryType(): void
    {
        $this->resetShippingQuotes();
    }

    public function updatedCustomerAddressId(): void
    {
        if (! auth()->check()) {
            return;
        }

        $address = auth()->user()->addresses()->find($this->customer_address_id);
        $this->shipping_destination_zipcode = $address?->cep ?? '';
        $this->resetShippingQuotes();
    }

    public function calculateShipping(MelhorEnvioService $shipping, CartService $cart): void
    {
        if ($this->delivery_type !== 'entrega') {
            $this->addError('shipping_destination_zipcode', 'Selecione entrega em casa para consultar frete.');

            return;
        }

        $this->validate([
            'shipping_destination_zipcode' => 'required|string|max:9',
        ], [], [
            'shipping_destination_zipcode' => 'CEP de entrega',
        ]);

        $this->shipping_quotes = [];
        $this->selected_shipping_options = [];

        foreach ($cart->grouped() as $expositorId => $storeItems) {
            $store = $storeItems->first()?->expositor;

            if (! $store) {
                continue;
            }

            $this->shipping_quotes[$expositorId] = collect(
                $shipping->quoteForStore($store, $this->shipping_destination_zipcode, $storeItems)
            )->map->toArray()->all();
        }
    }

    public function selectShippingOption(int $storeId, array $quote): void
    {
        if (! empty($quote['error_message'])) {
            return;
        }

        $this->selected_shipping_options[$storeId] = [
            'service_id' => $quote['service_id'] ?? null,
            'company' => $quote['company'] ?? null,
            'service_name' => $quote['service_name'] ?? null,
            'price' => isset($quote['price']) ? round((float) $quote['price'], 2) : 0.0,
            'delivery_time' => $quote['delivery_time'] ?? null,
            'currency' => $quote['currency'] ?? 'BRL',
            'error_message' => null,
        ];
    }

    public function startAddingAddress(): void
    {
        $this->addingAddress = true;
    }

    public function saveNewAddress(): void
    {
        $this->validate([
            'new_label' => 'required|string|max:50',
            'new_cep' => 'required|string|max:9',
            'new_rua' => 'required|string|max:255',
            'new_numero' => 'required|string|max:20',
            'new_complemento' => 'nullable|string|max:255',
            'new_bairro' => 'required|string|max:255',
            'new_cidade' => 'required|string|max:255',
            'new_estado' => 'required|string|size:2',
        ], [], [
            'new_label' => 'identificação (ex: Casa)',
            'new_cep' => 'CEP',
            'new_rua' => 'rua',
            'new_numero' => 'número',
            'new_bairro' => 'bairro',
            'new_cidade' => 'cidade',
            'new_estado' => 'estado',
        ]);

        $hasAddresses = auth()->user()->addresses()->exists();

        $address = auth()->user()->addresses()->create([
            'label' => $this->new_label,
            'cep' => $this->new_cep,
            'rua' => $this->new_rua,
            'numero' => $this->new_numero,
            'complemento' => $this->new_complemento ?: null,
            'bairro' => $this->new_bairro,
            'cidade' => $this->new_cidade,
            'estado' => strtoupper($this->new_estado),
            'is_default' => ! $hasAddresses,
        ]);

        $this->customer_address_id = $address->id;
        $this->shipping_destination_zipcode = $address->cep;
        $this->addingAddress = false;
        $this->resetShippingQuotes();
        $this->reset([
            'new_label', 'new_cep', 'new_rua', 'new_numero',
            'new_complemento', 'new_bairro', 'new_cidade', 'new_estado',
        ]);
    }

    public function confirmar(OrderService $orderService, CartService $cart, MercadoPagoService $mercadoPago)
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
            'customer_name' => 'required|string|max:255',
            'customer_whatsapp' => 'required|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'delivery_type' => 'required|in:retirada,entrega',
        ], [], [
            'customer_name' => 'nome',
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
            'customer_name' => $this->customer_name,
            'customer_whatsapp' => $this->customer_whatsapp,
            'customer_email' => $this->customer_email ?: null,
            'delivery_type' => $this->delivery_type,
            'customer_address_id' => $address?->id,
            'address_cep' => $address?->cep,
            'address_rua' => $address?->rua,
            'address_numero' => $address?->numero,
            'address_complemento' => $address?->complemento,
            'address_bairro' => $address?->bairro,
            'address_cidade' => $address?->cidade,
            'address_estado' => $address?->estado,
            'shipping_total' => $this->shippingTotal(),
            'shipping_note' => $this->shippingNote(),
        ], $cart);

        if ($mercadoPago->isEnabled()) {
            try {
                $order = $mercadoPago->createPreference($order);

                return redirect()->away($mercadoPago->checkoutUrl($order));
            } catch (Throwable $exception) {
                report($exception);

                session()->flash('error', 'O pedido foi criado, mas não foi possível abrir o Mercado Pago agora. Tente pagar novamente pela página do pedido.');

                return $this->redirect(route('pedido.show', $order->reference), navigate: false);
            }
        }

        return $this->redirect(route('pedido.show', $order->reference), navigate: false);
    }

    public function render(CartService $cart): View
    {
        $shippingTotal = $this->delivery_type === 'entrega' ? $this->shippingTotal() : 0.0;

        return view('livewire.checkout', [
            'grouped' => $cart->grouped(),
            'total' => $cart->total(),
            'shippingTotal' => $shippingTotal,
            'totalWithShipping' => $cart->total() + $shippingTotal,
            'settings' => SiteSetting::instance(),
            'addresses' => auth()->check()
                ? auth()->user()->addresses()->orderByDesc('is_default')->get()
                : collect(),
        ]);
    }

    private function shippingTotal(): float
    {
        return collect($this->selected_shipping_options)
            ->sum(fn (array $quote) => (float) ($quote['price'] ?? 0));
    }

    private function shippingNote(): ?string
    {
        if ($this->delivery_type === 'retirada') {
            return 'Retirada combinada diretamente com o(s) lojista(s) via WhatsApp.';
        }

        if ($this->selected_shipping_options === []) {
            return null;
        }

        return collect($this->selected_shipping_options)
            ->map(function (array $quote, int|string $storeId) {
                $price = number_format((float) ($quote['price'] ?? 0), 2, ',', '.');
                $service = trim(($quote['company'] ?? '').' '.($quote['service_name'] ?? ''));
                $days = $quote['delivery_time'] ? " em até {$quote['delivery_time']} dias úteis" : '';

                return "Loja {$storeId}: {$service} - R$ {$price}{$days}.";
            })
            ->implode(' ');
    }

    private function resetShippingQuotes(): void
    {
        $this->shipping_quotes = [];
        $this->selected_shipping_options = [];
    }
}
