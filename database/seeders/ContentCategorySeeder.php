<?php

namespace Database\Seeders;

use App\Models\ContentCategory;
use Illuminate\Database\Seeder;

class ContentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Artesanato',
            'Roupas e Acessórios',
            'Alimentos e Bebidas',
            'Bijuterias e Joias',
            'Decoração',
            'Livros e Zines',
            'Cosméticos e Cuidados',
            'Arte e Ilustração',
            'Brinquedos e Jogos',
            'Plantas e Jardim',
            'Música e Instrumentos',
            'Outros',
        ];

        foreach ($categories as $name) {
            ContentCategory::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($name)],
                ['name' => $name, 'is_active' => true]
            );
        }
    }
}
