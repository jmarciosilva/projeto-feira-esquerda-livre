<?php

namespace App\Livewire\Admin\Concerns;

trait AuthorizesAdminActions
{
    protected function authorizeAdminAction(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }
}
