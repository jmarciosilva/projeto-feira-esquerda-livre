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
            DemoExpositorImageSeeder::class,
            ContentCategorySeeder::class,
            ProductSeeder::class,
            PostSeeder::class,
            LojistaSeeder::class,
            ExpositorUserSeeder::class,
            ServicoSeeder::class,
            CuidadoSeeder::class,
            DemoProductSeeder::class,
            DemoProductImageSeeder::class,
            DemoAvaCourseSeeder::class,
            DemoFeedPostSeeder::class,
            ExpositorZipcodeSeeder::class,
            ProductLogisticDataSeeder::class,

            // CAT-03: base de conhecimento do catálogo. Idempotente e
            // independente dos itens — não lê nem altera `products`.
            CatalogKnowledgeSeeder::class,
        ]);
    }
}
