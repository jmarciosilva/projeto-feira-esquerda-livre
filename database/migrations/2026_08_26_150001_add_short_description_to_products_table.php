<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CAT-02 — resumo comercial curto do item de catálogo.
 *
 * Fica ANTES de `description` na tabela porque é a leitura natural do par:
 * primeiro o resumo, depois o texto completo.
 *
 * VARCHAR(500), e não TEXT: é um resumo, não um corpo de texto. O limite
 * comporta com folga os ~160 caracteres que a meta description do produto já
 * consome hoje (`loja/produto.blade.php`) e um card com duas ou três linhas,
 * sem virar um segundo `description`. VARCHAR também mantém a porta aberta
 * para índice com prefixo, que TEXT dificultaria.
 *
 * Nullable: existem itens já cadastrados, e nenhum deles é inválido por não
 * ter resumo. Preencher o campo é trabalho do lojista — ou, mais adiante, do
 * assistente da CAT-05 — nunca de um backfill automático que copiaria a
 * descrição longa e nasceria errado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('short_description', 500)
                ->nullable()
                ->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('short_description');
        });
    }
};
