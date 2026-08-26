<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Expositor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Mínimo necessário para a ProductFactory ter um dono válido.
 *
 * O expositor nasce ativo e ligado a um usuário com papel de lojista, que é a
 * combinação exigida pelo LojistaMiddleware — sem isso, todo teste de painel
 * teria de montar a dupla à mão.
 *
 * @extends Factory<Expositor>
 */
class ExpositorFactory extends Factory
{
    protected $model = Expositor::class;

    public function definition(): array
    {
        $name = 'Loja '.$this->faker->unique()->words(2, true);

        return [
            'user_id' => User::factory()->state(['role' => UserRole::Lojista, 'is_active' => true]),
            'name' => Str::ucfirst($name),
            'slug' => Str::slug($name).'-'.Str::random(6),
            'is_active' => true,
        ];
    }

    public function inativo(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
