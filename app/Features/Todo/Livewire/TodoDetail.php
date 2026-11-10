<?php

namespace App\Features\Todo\Livewire;

use App\Features\Todo\Enums\TodoStatus;
use App\Features\Todo\Models\Todo;
use App\Features\Todo\Services\TodoService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Detail Todo')]
class TodoDetail extends Component
{
    use WithFileUploads;

    public Todo $todo;

    public bool $editing = false;

    public string $title = '';

    public string $description = '';

    public string $status = 'pending';

    public ?string $dueDate = null;

    public $cover = null;

    public function mount(Todo $todo): void
    {
        Gate::authorize('view', $todo);

        $this->todo = $todo;
        $this->fillForm();
    }

    public function startEdit(): void
    {
        Gate::authorize('update', $this->todo);

        $this->fillForm();
        $this->editing = true;
    }

    public function cancelEdit(): void
    {
        $this->editing = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        Gate::authorize('update', $this->todo);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(TodoStatus::class)],
            'dueDate' => ['nullable', 'date'],
        ]);

        $this->todo = $this->service()->update($this->todo, [
            'title' => $this->title,
            'description' => $this->description === '' ? null : $this->description,
            'status' => $this->status,
            'due_date' => $this->dueDate ?: null,
        ]);

        $this->editing = false;
        session()->flash('status', 'Todo berhasil diperbarui.');
    }

    public function toggleComplete(): void
    {
        Gate::authorize('update', $this->todo);

        $this->todo = $this->service()->toggleComplete($this->todo);
        $this->fillForm();
    }

    public function uploadCover(): void
    {
        Gate::authorize('update', $this->todo);

        $this->validate([
            'cover' => ['required', 'image', 'max:2048'],
        ]);

        $this->todo = $this->service()->storeCover($this->todo, $this->cover);
        $this->cover = null;
        session()->flash('status', 'Cover berhasil diunggah.');
    }

    public function removeCover(): void
    {
        Gate::authorize('update', $this->todo);

        $this->todo = $this->service()->removeCover($this->todo);
    }

    public function delete()
    {
        Gate::authorize('delete', $this->todo);

        $this->service()->delete($this->todo);
        session()->flash('status', 'Todo berhasil dihapus.');

        return $this->redirectRoute('todos.index');
    }

    public function render()
    {
        return view('livewire.todo.todo-detail', [
            'statuses' => TodoStatus::cases(),
        ]);
    }

    private function fillForm(): void
    {
        $this->title = $this->todo->title;
        $this->description = (string) $this->todo->description;
        $this->status = $this->todo->status->value;
        $this->dueDate = $this->todo->due_date?->format('Y-m-d');
    }

    private function service(): TodoService
    {
        return app(TodoService::class);
    }
}
