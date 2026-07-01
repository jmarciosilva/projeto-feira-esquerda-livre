<?php

namespace App\Livewire\Admin\Usuarios;

use App\Enums\UserRole;
use App\Mail\InternalUserAccessCreated;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class UsuarioForm extends Component
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $whatsapp = '';

    public string $role = 'supervisor';

    public bool $is_active = true;

    public string $password = '';

    public bool $send_credentials = true;

    public function mount(?User $user = null): void
    {
        abort_unless(auth()->user()?->can('usuarios.gerenciar'), 403);

        if ($user && $user->exists) {
            abort_unless($user->isInternalUser(), 404);

            $this->user = $user;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->whatsapp = $user->whatsapp ?? '';
            $this->role = $user->role?->value ?? 'supervisor';
            $this->is_active = $user->is_active;
            $this->send_credentials = false;
        }
    }

    public function save(): mixed
    {
        abort_unless(auth()->user()?->can('usuarios.gerenciar'), 403);

        $internalRoleValues = collect($this->internalRoles())->pluck('value')->all();

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user?->id),
            ],
            'whatsapp' => 'nullable|string|max:20',
            'role' => ['required', Rule::in($internalRoleValues)],
            'is_active' => 'boolean',
            'password' => [$this->user ? 'nullable' : 'nullable', 'string', 'min:8', 'max:100'],
            'send_credentials' => 'boolean',
        ]);

        $password = $validated['password'] ?: Str::random(12);
        $role = UserRole::from($validated['role']);
        $isCreating = ! $this->user;

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'whatsapp' => $validated['whatsapp'] ?: null,
            'role' => $role,
            'is_active' => $validated['is_active'],
        ];

        if ($isCreating || $validated['password']) {
            $data['password'] = Hash::make($password);
        }

        $user = $this->user
            ? tap($this->user)->update($data)
            : User::create($data);

        $user->syncRoles([$role->spatieRole()]);

        if (($isCreating || $validated['password']) && $validated['send_credentials']) {
            try {
                Mail::to($user->email)->send(new InternalUserAccessCreated($user, $password));
            } catch (\Throwable $exception) {
                report($exception);
                session()->flash('success', "Usuário salvo. Senha temporária: {$password}. Não foi possível enviar o e-mail.");

                return redirect()->route('admin.usuarios.index');
            }
        }

        session()->flash('success', 'Usuário interno salvo com sucesso.');

        return redirect()->route('admin.usuarios.index');
    }

    public function render(): View
    {
        return view('livewire.admin.usuarios.usuario-form', [
            'roles' => $this->internalRoles(),
        ])->layout('admin.layouts.app', [
            'title' => $this->user ? 'Editar Usuário Interno' : 'Novo Usuário Interno',
        ]);
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
