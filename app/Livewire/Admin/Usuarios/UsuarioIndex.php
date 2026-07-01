<?php

namespace App\Livewire\Admin\Usuarios;

use App\Enums\UserRole;
use App\Mail\InternalUserAccessCreated;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class UsuarioIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $role = '';

    public ?int $resetPasswordUserId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('usuarios.visualizar'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRole(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $userId): void
    {
        abort_unless(auth()->user()?->can('usuarios.gerenciar'), 403);

        $user = User::findOrFail($userId);

        if ($user->is(auth()->user())) {
            $this->addError('users', 'Você não pode desativar seu próprio acesso.');

            return;
        }

        $user->update(['is_active' => ! $user->is_active]);
        session()->flash('success', 'Status do usuário atualizado.');
    }

    public function resetPassword(int $userId): void
    {
        abort_unless(auth()->user()?->can('usuarios.gerenciar'), 403);

        $user = User::findOrFail($userId);
        $password = Str::random(12);

        $user->update([
            'password' => Hash::make($password),
        ]);

        try {
            Mail::to($user->email)->send(new InternalUserAccessCreated($user, $password));
            session()->flash('success', 'Senha redefinida e e-mail enviado ao usuário.');
        } catch (\Throwable $exception) {
            report($exception);
            session()->flash('success', "Senha redefinida para {$password}. Não foi possível enviar o e-mail.");
        }
    }

    public function render(): View
    {
        $internalRoles = collect($this->internalRoles())->pluck('value')->all();

        $users = User::query()
            ->whereIn('role', $internalRoles)
            ->with('customerProfile')
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->role, fn ($query) => $query->where('role', $this->role))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.usuarios.usuario-index', [
            'users' => $users,
            'roles' => $this->internalRoles(),
        ])->layout('admin.layouts.app', ['title' => 'Usuários Internos']);
    }

    /**
     * @return array<int, UserRole>
     */
    private function internalRoles(): array
    {
        return array_values(array_filter(
            UserRole::cases(),
            fn (UserRole $role) => $role->isInternal(),
        ));
    }
}
