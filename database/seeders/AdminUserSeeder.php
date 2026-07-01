<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@feiraesquerdalivre.com.br'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Admin@2026!'),
                'role' => UserRole::Admin,
                'is_active' => true,
            ]
        );

        if (method_exists($user, 'syncRoles')) {
            $user->syncRoles([UserRole::Admin->spatieRole()]);
        }

        $this->command->info('Admin criado: admin@feiraesquerdalivre.com.br / Admin@2026!');
    }
}
