<?php

namespace Tests\Feature;

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
