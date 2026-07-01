<?php

namespace App\Livewire\Admin\Usuarios;

use App\Enums\MarketplaceStatus;
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

    public string $name             = '';
    public string $email            = '';
    public string $whatsapp         = '';
    public string $role             = 'supervisor';
    public bool   $is_active        = true;
    public string $password         = '';
    public bool   $send_credentials = true;

    // Perfil de cliente (modo edição)
    public bool $isAlsoCustomer = false;

    // Fluxo: promover cliente existente a usuário interno
    public bool   $showPromoteModal = false;
    public ?int   $candidateUserId  = null;
    public string $promoteRole      = 'supervisor';

    public function mount(?User $user = null): void
    {
        abort_unless(auth()->user()?->can('usuarios.gerenciar'), 403);

        if ($user && $user->exists) {
            abort_unless($user->isInternalUser(), 404);

            $this->user           = $user;
            $this->name           = $user->name;
            $this->email          = $user->email;
            $this->whatsapp       = $user->whatsapp ?? '';
            $this->role           = $user->role?->value ?? 'supervisor';
            $this->is_active      = $user->is_active;
            $this->isAlsoCustomer = $user->customerProfile !== null;
            $this->send_credentials = false;
        }
    }

    public function save(): mixed
    {
        abort_unless(auth()->user()?->can('usuarios.gerenciar'), 403);

        // Pre-flight: e-mail já pertence a algum usuário?
        if (! $this->user) {
            $existingUser = User::where('email', $this->email)->first();

            if ($existingUser) {
                if ($existingUser->isInternalUser()) {
                    $this->addError('email', 'Este e-mail já pertence a um usuário com acesso interno ao sistema.');
                    return null;
                }

                // Usuário não-interno (cliente, lojista) → oferecer promoção preservando tudo
                $this->candidateUserId  = $existingUser->id;
                $this->promoteRole      = $this->role;
                $this->showPromoteModal = true;

                return null;
            }
        }

        $internalRoleValues = collect($this->internalRoles())->pluck('value')->all();

        $validated = $this->validate(
            [
                'name'             => 'required|string|max:255',
                'email'            => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($this->user?->id),
                ],
                'whatsapp'         => 'nullable|string|max:20',
                'role'             => ['required', Rule::in($internalRoleValues)],
                'is_active'        => 'boolean',
                'password'         => ['nullable', 'string', 'min:8', 'max:100'],
                'send_credentials' => 'boolean',
            ],
            [
                'email.unique' => 'Este e-mail já está em uso por outro usuário do sistema.',
            ]
        );

        $password   = $validated['password'] ?: Str::random(12);
        $role       = UserRole::from($validated['role']);
        $isCreating = ! $this->user;

        $data = [
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'whatsapp'  => $validated['whatsapp'] ?: null,
            'role'      => $role,
            'is_active' => $validated['is_active'],
        ];

        if ($isCreating || $validated['password']) {
            $data['password'] = Hash::make($password);
        }

        $user = $this->user
            ? tap($this->user)->update($data)
            : User::create($data);

        $user->syncRoles([$role->spatieRole()]);

        $this->syncCustomerProfile($user);

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

    public function cancelPromote(): void
    {
        $this->showPromoteModal = false;
        $this->candidateUserId  = null;
    }

    public function promoteToInternal(): mixed
    {
        abort_unless(auth()->user()?->can('usuarios.gerenciar'), 403);

        $internalRoleValues = collect($this->internalRoles())->pluck('value')->all();

        $this->validate(
            ['promoteRole' => ['required', Rule::in($internalRoleValues)]],
            [],
            ['promoteRole' => 'papel']
        );

        $user = User::findOrFail($this->candidateUserId);
        $role = UserRole::from($this->promoteRole);

        $user->update([
            'role'      => $role,
            'is_active' => true,
        ]);

        $user->syncRoles([$role->spatieRole()]);

        $this->showPromoteModal = false;
        $this->candidateUserId  = null;

        session()->flash('success', "{$user->name} agora tem acesso interno como {$role->label()}. Perfil de cliente e histórico preservados.");

        return redirect()->route('admin.usuarios.index');
    }

    public function render(): View
    {
        $candidateUser = $this->candidateUserId
            ? User::find($this->candidateUserId)
            : null;

        return view('livewire.admin.usuarios.usuario-form', [
            'roles'         => $this->internalRoles(),
            'candidateUser' => $candidateUser,
        ])->layout('admin.layouts.app', [
            'title' => $this->user ? 'Editar Usuário Interno' : 'Novo Usuário Interno',
        ]);
    }

    private function syncCustomerProfile(User $user): void
    {
        $user->load('customerProfile');

        if ($this->isAlsoCustomer && ! $user->customerProfile) {
            $user->customerProfile()->create(['marketplace_status' => MarketplaceStatus::Active]);
        } elseif (! $this->isAlsoCustomer && $user->customerProfile) {
            $user->customerProfile()->delete();
        }
    }

    /** @return array<int, UserRole> */
    private function internalRoles(): array
    {
        return array_values(array_filter(
            UserRole::cases(),
            fn (UserRole $r) => $r->isInternal(),
        ));
    }
}
