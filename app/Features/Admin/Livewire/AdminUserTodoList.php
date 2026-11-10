<?php

namespace App\Features\Admin\Livewire;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Todo\Enums\TodoStatus;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Todolist Pengguna')]
class AdminUserTodoList extends Component
{
    use WithPagination;

    public User $user;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $service = app(AdminUserService::class);

        return view('livewire.admin.admin-user-todo-list', [
            'todos' => $service->paginateTodos(
                $this->user,
                $this->search !== '' ? $this->search : null,
                $this->statusFilter !== '' ? $this->statusFilter : null,
            ),
            'statuses' => TodoStatus::cases(),
        ]);
    }
}
