<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    public const PERMISSIONS = [
        'admin.acessar',
        'cms.visualizar',
        'cms.editar',
        'lojistas.visualizar',
        'lojistas.aprovar',
        'clientes.visualizar',
        'clientes.gerenciar',
        'produtos.visualizar',
        'produtos.moderar',
        'pedidos.visualizar',
        'pedidos.atualizar_status',
        'configuracoes.visualizar',
        'configuracoes.editar',
        'usuarios.visualizar',
        'usuarios.gerenciar',
        'permissoes.gerenciar',
        'feed.moderar',
        'relatorios.visualizar',
        'email-marketing.gerenciar',
        'expositores.visibilidade',
    ];

    /**
     * @return array<string, array<int, string>>
     */
    public static function rolePermissions(): array
    {
        return [
            'administrador' => self::PERMISSIONS,
            'gerente' => [
                'admin.acessar',
                'cms.visualizar',
                'cms.editar',
                'lojistas.visualizar',
                'lojistas.aprovar',
                'clientes.visualizar',
                'clientes.gerenciar',
                'produtos.visualizar',
                'produtos.moderar',
                'pedidos.visualizar',
                'pedidos.atualizar_status',
                'configuracoes.visualizar',
                'feed.moderar',
                'relatorios.visualizar',
                'email-marketing.gerenciar',
                'expositores.visibilidade',
            ],
            'supervisor' => [
                'admin.acessar',
                'cms.visualizar',
                'lojistas.visualizar',
                'clientes.visualizar',
                'produtos.visualizar',
                'produtos.moderar',
                'pedidos.visualizar',
                'pedidos.atualizar_status',
                'feed.moderar',
                'relatorios.visualizar',
            ],
            'editor' => [
                'admin.acessar',
                'cms.visualizar',
                'cms.editar',
                'configuracoes.visualizar',
            ],
            'lojista' => [],
            'cliente' => [],
        ];
    }

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (self::rolePermissions() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }

        User::query()->each(function (User $user) {
            $role = $user->role?->spatieRole();

            if ($role) {
                $user->syncRoles([$role]);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
