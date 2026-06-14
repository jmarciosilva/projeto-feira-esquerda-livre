<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expositores', function (Blueprint $table) {
            $table->string('banco_nome')->nullable()->after('facebook_url');
            $table->string('banco_agencia')->nullable()->after('banco_nome');
            $table->string('banco_conta')->nullable()->after('banco_agencia');
            $table->string('banco_tipo_conta')->nullable()->after('banco_conta');
            $table->string('pix_tipo')->nullable()->after('banco_tipo_conta');
            $table->string('pix_chave')->nullable()->after('pix_tipo');
        });
    }

    public function down(): void
    {
        Schema::table('expositores', function (Blueprint $table) {
            $table->dropColumn(['banco_nome', 'banco_agencia', 'banco_conta', 'banco_tipo_conta', 'pix_tipo', 'pix_chave']);
        });
    }
};
