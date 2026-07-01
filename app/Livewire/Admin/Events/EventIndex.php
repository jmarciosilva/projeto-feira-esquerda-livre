<?php

namespace App\Livewire\Admin\Events;

use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Models\Event;
use App\Services\EventService;
use Livewire\Component;
use Livewire\WithPagination;

class EventIndex extends Component
{
    use AuthorizesAdminActions, WithPagination;

    public string $search      = '';
    public string $filterState = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function deleteEvent(EventService $service, int $id): void
    {
        $this->authorizeAdminAction('cms.editar');

        $service->delete(Event::findOrFail($id));

        session()->flash('success', 'Evento removido.');
    }

    public function toggleActive(int $id): void
    {
        $this->authorizeAdminAction('cms.editar');

        $event = Event::findOrFail($id);
        $event->update(['is_active' => !$event->is_active]);
    }

    public function render(): \Illuminate\View\View
    {
        $events = Event::query()
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->filterState, fn ($q) => $q->where('state', $this->filterState))
            ->orderBy('start_date')
            ->paginate(15);

        $states = Event::select('state')->distinct()->orderBy('state')->pluck('state')->filter();

        return view('livewire.admin.events.event-index', compact('events', 'states'))
            ->layout('admin.layouts.app', ['title' => 'Eventos']);
    }
}
