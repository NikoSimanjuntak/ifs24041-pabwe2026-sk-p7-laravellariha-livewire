<?php

namespace App\Features\Todo\Livewire;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedTodoItem;
use App\Features\Todo\Enums\TodoStatus;
use App\Features\Todo\Models\Todo;
use App\Features\Todo\Services\TodoExcelService;
use App\Features\Todo\Services\TodoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Todolist')]
class TodoList extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $description = '';

    public string $status = 'pending';

    public ?string $dueDate = null;

    public bool $showAi = false;

    public string $aiPrompt = '';

    public int $aiCount = 5;

    /** @var list<array<string, mixed>> */
    public array $aiItems = [];

    /** @var list<string|int> */
    public array $aiSelected = [];

    public $importFile = null;

    /** @var list<string> */
    public array $importErrors = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        Gate::authorize('create', Todo::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $todo = $this->service()->find($id);
        Gate::authorize('update', $todo);

        $this->resetValidation();
        $this->editingId = $todo->id;
        $this->title = $todo->title;
        $this->description = (string) $todo->description;
        $this->status = $todo->status->value;
        $this->dueDate = $todo->due_date?->format('Y-m-d');
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(TodoStatus::class)],
            'dueDate' => ['nullable', 'date'],
        ]);

        $data = [
            'title' => $this->title,
            'description' => $this->description === '' ? null : $this->description,
            'status' => $this->status,
            'due_date' => $this->dueDate ?: null,
        ];

        if ($this->editingId !== null) {
            $todo = $this->service()->find($this->editingId);
            Gate::authorize('update', $todo);
            $this->service()->update($todo, $data);
            session()->flash('status', 'Todo berhasil diperbarui.');
        } else {
            Gate::authorize('create', Todo::class);
            $this->service()->create((int) Auth::id(), $data);
            session()->flash('status', 'Todo berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function toggleComplete(int $id): void
    {
        $todo = $this->service()->find($id);
        Gate::authorize('update', $todo);

        $this->service()->toggleComplete($todo);
    }

    public function delete(int $id): void
    {
        $todo = $this->service()->find($id);
        Gate::authorize('delete', $todo);

        $this->service()->delete($todo);
        session()->flash('status', 'Todo berhasil dihapus.');
    }

    public function export(TodoExcelService $excel): StreamedResponse
    {
        $content = $excel->export($this->service()->all((int) Auth::id()));

        return response()->streamDownload(function () use ($content): void {
            echo $content;
        }, 'todolist.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(TodoExcelService $excel): void
    {
        $this->importErrors = [];

        $this->validate([
            'importFile' => ['required', 'file', 'max:2048', 'extensions:xlsx,xls,csv'],
        ]);

        try {
            $result = $excel->import($this->importFile->getRealPath());
        } catch (Throwable) {
            $this->importErrors = ['File tidak dapat dibaca. Pastikan formatnya .xlsx, .xls, atau .csv yang valid.'];
            $this->importFile = null;

            return;
        }

        $count = $this->service()->createMany((int) Auth::id(), $result['rows']);

        $this->importErrors = $result['errors'];
        $this->importFile = null;
        session()->flash('status', "{$count} todo berhasil diimpor.");
    }

    public function openAi(): void
    {
        Gate::authorize('create', Todo::class);

        $this->showAi = true;
    }

    public function closeAi(): void
    {
        $this->showAi = false;
        $this->aiPrompt = '';
        $this->aiItems = [];
        $this->aiSelected = [];
        $this->resetValidation();
    }

    public function generateAi(AiClientInterface $ai): void
    {
        $this->validate([
            'aiPrompt' => ['required', 'string', 'min:3', 'max:500'],
            'aiCount' => ['required', 'integer', 'between:1,10'],
        ]);

        try {
            $items = $ai->generate($this->aiPrompt, $this->aiCount);
        } catch (RuntimeException $e) {
            $this->addError('aiPrompt', $e->getMessage());

            return;
        }

        if ($items === []) {
            $this->addError('aiPrompt', 'AI tidak menghasilkan item. Coba ubah permintaan Anda.');

            return;
        }

        $this->aiItems = array_map(static fn (GeneratedTodoItem $item): array => $item->toArray(), $items);
        $this->aiSelected = array_keys($this->aiItems);
    }

    public function saveAi(): void
    {
        Gate::authorize('create', Todo::class);

        $rows = [];

        foreach ($this->aiSelected as $index) {
            $item = GeneratedTodoItem::fromArray($this->aiItems[(int) $index] ?? []);

            if ($item !== null) {
                $rows[] = $item->toArray();
            }
        }

        if ($rows === []) {
            $this->addError('aiSelected', 'Pilih minimal satu item untuk disimpan.');

            return;
        }

        $count = $this->service()->createMany((int) Auth::id(), $rows);
        $this->closeAi();
        session()->flash('status', "{$count} todo hasil AI berhasil disimpan.");
    }

    public function render()
    {
        $todos = $this->service()->paginate(
            (int) Auth::id(),
            $this->search !== '' ? $this->search : null,
            $this->statusFilter !== '' ? $this->statusFilter : null,
        );

        return view('livewire.todo.todo-list', [
            'todos' => $todos,
            'statuses' => TodoStatus::cases(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['showForm', 'editingId', 'title', 'description', 'status', 'dueDate']);
        $this->resetValidation();
    }

    private function service(): TodoService
    {
        return app(TodoService::class);
    }
}
