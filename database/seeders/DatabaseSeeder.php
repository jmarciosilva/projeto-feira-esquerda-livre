<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            SiteSettingSeeder::class,
            BannerSeeder::class,
            EventSeeder::class,
            ExpositorSeeder::class,
            ContentCategorySeeder::class,
            ProductSeeder::class,
            PostSeeder::class,
            LojistaSeeder::class,
        ]);
    }
}
