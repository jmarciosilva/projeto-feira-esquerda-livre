<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CheckoutAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_from_checkout_redirects_back_to_checkout(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret-password'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-password',
            'redirect_to' => route('checkout'),
        ]);

        $response->assertRedirect('/checkout');
        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_cart_survives_login(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret-password'),
        ]);

        $expositor = Expositor::create(['name' => 'Loja Teste', 'slug' => 'loja-teste']);
        $product = Product::create([
            'expositor_id' => $expositor->id,
            'name' => 'Produto Teste',
            'slug' => 'produto-teste',
            'price' => 25.00,
            'is_active' => true,
        ]);

        // Primeira requisição, como convidado: o Laravel inicia a sessão.
        $this->get('/');
        $cookieName = config('session.cookie');
        $guestSessionId = $this->app['session']->getId();

        CartItem::create([
            'session_id' => $guestSessionId,
            'user_id' => null,
            'product_id' => $product->id,
            'expositor_id' => $expositor->id,
            'quantity' => 3,
            'price_snapshot' => 25.00,
        ]);

        // Segunda requisição, com o MESMO cookie de sessão do convidado: faz login.
        // withCookie() criptografa o valor sozinho, entao passamos o ID em texto puro.
        $loginResponse = $this->withCookie($cookieName, $guestSessionId)
            ->post('/login', [
                'email' => $user->email,
                'password' => 'secret-password',
            ]);

        $loginResponse->assertRedirect();
        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
        $this->assertDatabaseMissing('cart_items', [
            'session_id' => $guestSessionId,
            'user_id' => null,
        ]);
    }

    public function test_registration_from_checkout_redirects_back_to_checkout(): void
    {
        $response = $this->post('/cadastro', [
            'name' => 'Cliente Teste',
            'email' => 'cliente@example.com',
            'whatsapp' => '(11) 99999-9999',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'redirect_to' => route('checkout'),
        ]);

        $response->assertRedirect('/checkout');
        $this->assertAuthenticated();
    }
}
