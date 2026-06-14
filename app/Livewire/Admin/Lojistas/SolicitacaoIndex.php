<?php

namespace App\Livewire\Admin\Lojistas;

use App\Enums\SolicitacaoStatus;
use App\Enums\UserRole;
use App\Mail\LojistaAprovado;
use App\Models\Expositor;
use App\Models\LojistasSolicitacao;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class SolicitacaoIndex extends Component
{
    use WithPagination;

    public string $filterStatus = 'pendente';
    public string $search       = '';

    public ?int $approveId = null;
    public ?int $blockId   = null;
    public string $motivoBloqueio = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function confirmApprove(int $id): void
    {
        $this->approveId = $id;
    }

    public function confirmBlock(int $id): void
    {
        $this->blockId        = $id;
        $this->motivoBloqueio = '';
    }

    public function approve(): void
    {
        $solicitacao = LojistasSolicitacao::findOrFail($this->approveId);

        if ($solicitacao->status !== SolicitacaoStatus::Pendente) {
            $this->approveId = null;
            return;
        }

        if (User::where('email', $solicitacao->email)->exists()) {
            $this->addError('approve', 'Já existe uma conta com o e-mail ' . $solicitacao->email . '. Verifique manualmente.');
            return;
        }

        $senha = '12345678';
        $user  = null;

        DB::transaction(function () use ($solicitacao, $senha, &$user) {
            $user = User::create([
                'name'      => $solicitacao->responsavel,
                'email'     => $solicitacao->email,
                'password'  => Hash::make($senha),
                'role'      => UserRole::Lojista,
                'is_active' => true,
            ]);

            Expositor::create([
                'user_id'          => $user->id,
                'name'             => $solicitacao->nome_loja,
                'whatsapp'         => $solicitacao->whatsapp,
                'email'            => $solicitacao->email,
                'instagram_url'    => $solicitacao->instagram_url,
                'facebook_url'     => $solicitacao->facebook_url,
                'banco_nome'       => $solicitacao->banco_nome,
                'banco_agencia'    => $solicitacao->banco_agencia,
                'banco_conta'      => $solicitacao->banco_conta,
                'banco_tipo_conta' => $solicitacao->banco_tipo_conta,
                'pix_tipo'         => $solicitacao->pix_tipo,
                'pix_chave'        => $solicitacao->pix_chave,
                'is_active'        => true,
            ]);

            $solicitacao->update([
                'status'  => SolicitacaoStatus::Aprovado,
                'user_id' => $user->id,
            ]);
        });

        $emailEnviado = false;
        try {
            Mail::to($user->email)->send(new LojistaAprovado($user, $senha, $solicitacao->nome_loja));
            $emailEnviado = true;
        } catch (\Throwable) {
            // e-mail falhou mas o cadastro está criado
        }

        $this->approveId = null;

        $msg = 'Lojista aprovado com sucesso. Uma conta foi criada para ' . $solicitacao->responsavel . '.';
        if (! $emailEnviado) {
            $msg .= ' ⚠️ Não foi possível enviar o e-mail de boas-vindas — verifique as configurações de e-mail.';
        }

        session()->flash('success', $msg);
    }

    public function block(): void
    {
        $this->validate(['motivoBloqueio' => 'required|string|max:500'], [], ['motivoBloqueio' => 'motivo']);

        $solicitacao = LojistasSolicitacao::findOrFail($this->blockId);

        $solicitacao->update([
            'status'          => SolicitacaoStatus::Bloqueado,
            'motivo_bloqueio' => $this->motivoBloqueio,
        ]);

        $this->blockId        = null;
        $this->motivoBloqueio = '';
        session()->flash('success', 'Solicitação bloqueada.');
    }

    public function cancelModals(): void
    {
        $this->approveId      = null;
        $this->blockId        = null;
        $this->motivoBloqueio = '';
        $this->resetErrorBag();
    }

    public function render(): \Illuminate\View\View
    {
        $solicitacoes = LojistasSolicitacao::query()
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('nome_loja', 'like', "%{$this->search}%")
                  ->orWhere('responsavel', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.lojistas.solicitacao-index', compact('solicitacoes'))
            ->layout('admin.layouts.app', ['title' => 'Solicitações de Lojistas']);
    }
}
