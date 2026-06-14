<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::updateOrCreate(
            ['id' => 1],
            [
                'site_name'        => 'Feira Esquerda Livre',
                'site_description' => 'O maior portal de feiras populares do Brasil.',
                'whatsapp'         => '(11) 94893-2064',
                'email'            => 'contato@feiraesquerdalivre.com.br',
                'address'          => 'Avenida das Flores - Jardim Florestal',
                'footer_text'      => '© ' . date('Y') . ' Feira Esquerda Livre. Todos os direitos reservados.',
                'maintenance_mode' => false,
            ]
        );
    }
}
