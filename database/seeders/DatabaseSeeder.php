<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            SiteSettingSeeder::class,
            ContratoExpositorSeeder::class,
            BannerSeeder::class,
            EventSeeder::class,
            ExpositorSeeder::class,
            ContentCategorySeeder::class,
            ProductSeeder::class,
            PostSeeder::class,
            LojistaSeeder::class,
            ExpositorUserSeeder::class,
            ServicoSeeder::class,
            CuidadoSeeder::class,
        ]);
    }
}
