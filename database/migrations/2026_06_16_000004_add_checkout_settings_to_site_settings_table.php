<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Frete — modo manual ativo no MVP; campos do Melhor Envio ficam prontos para uso futuro
            $table->string('frete_modo')->default('manual')->after('contrato_expositor');
            $table->text('frete_mensagem_manual')->nullable()->after('frete_modo');
            $table->decimal('frete_valor_padrao', 10, 2)->nullable()->after('frete_mensagem_manual');
            $table->boolean('melhor_envio_ativo')->default(false)->after('frete_valor_padrao');
            $table->string('melhor_envio_client_id')->nullable()->after('melhor_envio_ativo');
            $table->text('melhor_envio_client_secret')->nullable()->after('melhor_envio_client_id');
            $table->text('melhor_envio_token')->nullable()->after('melhor_envio_client_secret');
            $table->boolean('melhor_envio_sandbox')->default(true)->after('melhor_envio_token');

            // Pagamento — modo manual ativo no MVP; campos do Mercado Pago ficam prontos para uso futuro
            $table->string('pagamento_modo')->default('manual')->after('melhor_envio_sandbox');
            $table->decimal('comissao_percentual', 5, 2)->default(0)->after('pagamento_modo');
            $table->boolean('mercado_pago_ativo')->default(false)->after('comissao_percentual');
            $table->string('mercado_pago_public_key')->nullable()->after('mercado_pago_ativo');
            $table->text('mercado_pago_access_token')->nullable()->after('mercado_pago_public_key');
            $table->boolean('mercado_pago_sandbox')->default(true)->after('mercado_pago_access_token');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'frete_modo', 'frete_mensagem_manual', 'frete_valor_padrao',
                'melhor_envio_ativo', 'melhor_envio_client_id', 'melhor_envio_client_secret',
                'melhor_envio_token', 'melhor_envio_sandbox',
                'pagamento_modo', 'comissao_percentual',
                'mercado_pago_ativo', 'mercado_pago_public_key', 'mercado_pago_access_token', 'mercado_pago_sandbox',
            ]);
        });
    }
};
