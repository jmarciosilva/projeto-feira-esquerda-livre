<?php

namespace Database\Seeders;

use App\Models\Expositor;
use Illuminate\Database\Seeder;

/**
 * Preenche todas as lojas com um endereço de origem real (Av. Paulista, 1009 - São Paulo/SP),
 * necessário para cotação de frete real via Melhor Envio.
 */
class ExpositorZipcodeSeeder extends Seeder
{
    public function run(): void
    {
        $total = Expositor::query()->update([
            'zipcode'  => '01311-100',
            'street'   => 'Avenida Paulista',
            'number'   => '1009',
            'district' => 'Bela Vista',
            'city'     => 'São Paulo',
            'state'    => 'SP',
        ]);

        $this->command->info("{$total} expositores atualizados com CEP real (01311-100 - Av. Paulista, 1009).");
    }
}
