<?php

namespace App\Models;

use App\Enums\SolicitacaoStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LojistasSolicitacao extends Model
{
    protected $table = 'lojista_solicitacoes';

    protected $fillable = [
        'nome_loja',
        'responsavel',
        'cpf_cnpj',
        'whatsapp',
        'email',
        'instagram_url',
        'facebook_url',
        'banco_nome',
        'banco_agencia',
        'banco_conta',
        'banco_tipo_conta',
        'pix_tipo',
        'pix_chave',
        'descricao',
        'eixos',
        'status',
        'motivo_bloqueio',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => SolicitacaoStatus::class,
            'eixos'  => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePendentes(Builder $query): Builder
    {
        return $query->where('status', SolicitacaoStatus::Pendente);
    }
}
