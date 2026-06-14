<?php

namespace Database\Seeders;

use App\Enums\SolicitacaoStatus;
use App\Enums\UserRole;
use App\Models\Expositor;
use App\Models\LojistasSolicitacao;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LojistaSeeder extends Seeder
{
    public function run(): void
    {
        // Lojista aprovado — acesso: lojista@teste.com / password
        $user = User::firstOrCreate(
            ['email' => 'lojista@teste.com'],
            [
                'name'      => 'Maria das Mãos',
                'password'  => Hash::make('password'),
                'role'      => UserRole::Lojista,
                'is_active' => true,
            ]
        );

        // Vincula ao expositor existente ou cria um novo
        $expositor = Expositor::where('slug', 'atelie-das-maos')->first()
            ?? Expositor::where('name', 'like', '%Ateliê das Mãos%')->first();

        if ($expositor) {
            $expositor->update(['user_id' => $user->id, 'email' => 'lojista@teste.com']);
        } else {
            Expositor::create([
                'user_id'       => $user->id,
                'name'          => 'Ateliê das Mãos',
                'description'   => 'Artesanato sustentável feito à mão com materiais recicláveis e da natureza.',
                'whatsapp'      => '(11) 9 9000-0001',
                'instagram_url' => 'https://instagram.com/ateliedasmaos',
                'email'         => 'lojista@teste.com',
                'city'          => 'São Paulo',
                'state'         => 'SP',
                'is_active'     => true,
            ]);
        }

        LojistasSolicitacao::firstOrCreate(
            ['email' => 'lojista@teste.com'],
            [
                'nome_loja'   => 'Ateliê das Mãos',
                'responsavel' => 'Maria das Mãos',
                'cpf_cnpj'    => '123.456.789-00',
                'whatsapp'    => '(11) 9 9000-0001',
                'descricao'   => 'Artesanato sustentável feito à mão.',
                'status'      => SolicitacaoStatus::Aprovado,
                'user_id'     => $user->id,
            ]
        );

        // Solicitações pendentes para testar o painel admin
        $pendentes = [
            [
                'nome_loja'   => 'Cooperativa Terra Viva',
                'responsavel' => 'João Terra',
                'cpf_cnpj'    => '987.654.321-00',
                'whatsapp'    => '(21) 9 8000-0001',
                'email'       => 'terraviva@exemplo.com',
                'descricao'   => 'Produtos orgânicos e agroecológicos da cooperativa.',
            ],
            [
                'nome_loja'   => 'Resistência Criativa',
                'responsavel' => 'Ana Lima',
                'cpf_cnpj'    => '111.222.333-44',
                'whatsapp'    => '(31) 9 7000-0002',
                'email'       => 'resistencia@exemplo.com',
                'descricao'   => 'Arte urbana e serigrafias de temática política.',
            ],
        ];

        foreach ($pendentes as $dados) {
            LojistasSolicitacao::firstOrCreate(
                ['email' => $dados['email']],
                $dados
            );
        }
    }
}
