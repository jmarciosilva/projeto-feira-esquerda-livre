<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Expositor;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Cria usuários lojistas para os expositores sem dono.
 * Expositores que já possuem user_id são ignorados.
 *
 * Todas as senhas: password
 *
 * ┌─────────────────────┬────────────────────────┬──────────┐
 * │ Loja                │ E-mail                 │ Senha    │
 * ├─────────────────────┼────────────────────────┼──────────┤
 * │ Cerâmica Viva       │ ceramica@teste.com     │ password │
 * │ Mel do Cerrado      │ mel@teste.com          │ password │
 * │ Raízes da Terra     │ raizes@teste.com       │ password │
 * │ Pincéis Livres      │ pinceis@teste.com      │ password │
 * │ Fios e Saberes      │ fios@teste.com         │ password │
 * └─────────────────────┴────────────────────────┴──────────┘
 *
 * Nota: "Ateliê das Mãos" já tem usuário pelo LojistaSeeder
 *       → lojista@teste.com / password
 */
class ExpositorUserSeeder extends Seeder
{
    private array $lojistas = [
        [
            'expositor_name' => 'Cerâmica Viva',
            'user_name'      => 'Cerâmica Viva',
            'email'          => 'ceramica@teste.com',
        ],
        [
            'expositor_name' => 'Mel do Cerrado',
            'user_name'      => 'Mel do Cerrado',
            'email'          => 'mel@teste.com',
        ],
        [
            'expositor_name' => 'Raízes da Terra',
            'user_name'      => 'Raízes da Terra',
            'email'          => 'raizes@teste.com',
        ],
        [
            'expositor_name' => 'Pincéis Livres',
            'user_name'      => 'Pincéis Livres',
            'email'          => 'pinceis@teste.com',
        ],
        [
            'expositor_name' => 'Fios e Saberes',
            'user_name'      => 'Fios e Saberes',
            'email'          => 'fios@teste.com',
        ],
    ];

    public function run(): void
    {
        $this->command->info('Criando usuários para expositores...');
        $this->command->newLine();

        foreach ($this->lojistas as $dados) {
            $expositor = Expositor::where('name', $dados['expositor_name'])->first();

            if (! $expositor) {
                $this->command->warn("  ✗ Expositor \"{$dados['expositor_name']}\" não encontrado — rode ExpositorSeeder primeiro.");
                continue;
            }

            // Cria ou atualiza o usuário — senha sempre redefinida para "password"
            $user = User::updateOrCreate(
                ['email' => $dados['email']],
                [
                    'name'      => $dados['user_name'],
                    'password'  => 'password',
                    'role'      => UserRole::Lojista,
                    'is_active' => true,
                ]
            );

            // Vincula ao expositor (somente se ainda não tiver dono)
            if (! $expositor->user_id) {
                $expositor->update([
                    'user_id' => $user->id,
                    'email'   => $dados['email'],
                ]);
            }

            $this->command->line("  ✓ {$dados['expositor_name']}");
            $this->command->line("    e-mail : {$dados['email']}");
            $this->command->line("    senha  : password");
            $this->command->newLine();
        }

        $this->command->line('──────────────────────────────────────────');
        $this->command->info('Ateliê das Mãos → lojista@teste.com / password  (LojistaSeeder)');
    }
}
