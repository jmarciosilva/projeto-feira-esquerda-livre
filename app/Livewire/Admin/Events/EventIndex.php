<?php

namespace App\Livewire\Admin\Events;

use App\Models\Event;
use App\Services\EventService;
use Livewire\Component;
use Livewire\WithPagination;

class EventIndex extends Component
{
    use WithPagination;

    public string $search        = '';
    public string $filterState   = '';
    public bool   $confirmDelete = false;
    public ?int   $deleteId      = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId      = $id;
        $this->confirmDelete = true;
    }

    public function deleteEvent(EventService $service): void
    {
        $event = Event::findOrFail($this->deleteId);
        $service->delete($event);

        $this->confirmDelete = false;
        $this->deleteId      = null;

        session()->flash('success', 'Evento removido.');
    }

    public function toggleActive(int $id): void
    {
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
