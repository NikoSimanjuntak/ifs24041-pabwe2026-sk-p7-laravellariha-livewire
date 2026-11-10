<?php

namespace Tests\Feature\Todo;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedTodoItem;
use App\Features\Todo\Enums\TodoStatus;
use App\Features\Todo\Livewire\TodoList;
use App\Features\Todo\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\Concerns\BuildsSpreadsheets;
use Tests\TestCase;

class TodoListLivewireTest extends TestCase
{
    use BuildsSpreadsheets;
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_shows_only_own_todos(): void
    {
        Todo::factory()->create(['user_id' => $this->user->id, 'title' => 'Milik saya']);
        Todo::factory()->create(['title' => 'Milik orang lain']);

        Livewire::test(TodoList::class)
            ->assertSee('Milik saya')
            ->assertDontSee('Milik orang lain');
    }

    public function test_empty_state(): void
    {
        Livewire::test(TodoList::class)->assertSee('Belum ada todo.');
    }

    public function test_renders_cover_and_overdue(): void
    {
        Todo::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Terlambat',
            'due_date' => '2000-01-01',
            'cover_image_path' => 'covers/x.jpg',
        ]);
        Todo::factory()->status(TodoStatus::Completed)->create(['user_id' => $this->user->id, 'title' => 'Beres']);

        Livewire::test(TodoList::class)->assertSee('Terlambat')->assertSee('covers/x.jpg', false)->assertSee('Beres');
    }

    public function test_search_and_status_filter(): void
    {
        Todo::factory()->create(['user_id' => $this->user->id, 'title' => 'Beli susu']);
        Todo::factory()->status(TodoStatus::Completed)->create(['user_id' => $this->user->id, 'title' => 'Cuci mobil']);

        Livewire::test(TodoList::class)
            ->set('search', 'susu')
            ->assertSee('Beli susu')
            ->assertDontSee('Cuci mobil')
            ->set('search', '')
            ->set('statusFilter', 'completed')
            ->assertSee('Cuci mobil')
            ->assertDontSee('Beli susu');
    }

    public function test_create_todo(): void
    {
        Livewire::test(TodoList::class)
            ->call('openCreate')
            ->assertSet('showForm', true)
            ->set('title', 'Todo baru')
            ->set('description', '**Markdown**')
            ->set('status', 'in_progress')
            ->set('dueDate', '2026-12-31')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('todos', [
            'user_id' => $this->user->id,
            'title' => 'Todo baru',
            'status' => 'in_progress',
        ]);
    }

    public function test_create_without_optional_fields(): void
    {
        Livewire::test(TodoList::class)->call('openCreate')->set('title', 'Minimal')->set('dueDate', '')->call('save');

        $todo = Todo::query()->where('title', 'Minimal')->firstOrFail();
        $this->assertNull($todo->description);
        $this->assertNull($todo->due_date);
    }

    public function test_create_validation(): void
    {
        Livewire::test(TodoList::class)
            ->call('openCreate')
            ->set('status', 'ngawur')
            ->set('dueDate', 'bukan-tanggal')
            ->call('save')
            ->assertHasErrors(['title', 'status', 'dueDate']);
    }

    public function test_close_form(): void
    {
        Livewire::test(TodoList::class)
            ->call('openCreate')
            ->set('title', 'x')
            ->call('closeForm')
            ->assertSet('showForm', false)
            ->assertSet('title', '');
    }

    public function test_edit_todo(): void
    {
        $todo = Todo::factory()->create(['user_id' => $this->user->id, 'title' => 'Lama', 'due_date' => '2026-10-10']);

        Livewire::test(TodoList::class)
            ->call('openEdit', $todo->id)
            ->assertSet('editingId', $todo->id)
            ->assertSet('title', 'Lama')
            ->assertSet('dueDate', '2026-10-10')
            ->set('title', 'Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Baru', $todo->fresh()->title);
    }

    public function test_edit_todo_without_due_date(): void
    {
        $todo = Todo::factory()->create(['user_id' => $this->user->id, 'due_date' => null]);

        Livewire::test(TodoList::class)->call('openEdit', $todo->id)->assertSet('dueDate', null);
    }

    public function test_cannot_edit_or_modify_other_users_todo(): void
    {
        $other = Todo::factory()->create(['title' => 'Orang lain']);

        Livewire::test(TodoList::class)->call('openEdit', $other->id)->assertForbidden();
        Livewire::test(TodoList::class)->call('toggleComplete', $other->id)->assertForbidden();
        Livewire::test(TodoList::class)->call('delete', $other->id)->assertForbidden();

        // update lewat state editingId yang dimanipulasi
        Livewire::test(TodoList::class)
            ->set('editingId', $other->id)
            ->set('title', 'Diretas')
            ->call('save')
            ->assertForbidden();

        $this->assertSame('Orang lain', $other->fresh()->title);
    }

    public function test_toggle_complete(): void
    {
        $todo = Todo::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(TodoList::class)->call('toggleComplete', $todo->id);
        $this->assertTrue($todo->fresh()->isCompleted());

        Livewire::test(TodoList::class)->call('toggleComplete', $todo->id);
        $this->assertFalse($todo->fresh()->isCompleted());
    }

    public function test_delete_todo(): void
    {
        $todo = Todo::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(TodoList::class)->call('delete', $todo->id);

        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    public function test_admin_cannot_create_todos(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(TodoList::class)->call('openCreate')->assertForbidden();
        Livewire::test(TodoList::class)->call('openAi')->assertForbidden();
        Livewire::test(TodoList::class)->set('title', 'x')->call('save')->assertForbidden();
    }

    public function test_export_downloads_xlsx(): void
    {
        Todo::factory()->count(2)->create(['user_id' => $this->user->id]);

        Livewire::test(TodoList::class)->call('export')->assertFileDownloaded('todolist.xlsx');
    }

    public function test_import_valid_file(): void
    {
        $path = $this->makeXlsxFile([
            ['Judul', 'Deskripsi', 'Status', 'Jatuh Tempo'],
            ['Impor satu', 'desk', 'pending', '2026-12-01'],
            ['', 'tanpa judul', 'pending', null],
        ]);
        $file = UploadedFile::fake()->createWithContent('todos.xlsx', file_get_contents($path));

        Livewire::test(TodoList::class)
            ->set('importFile', $file)
            ->call('import')
            ->assertHasNoErrors()
            ->assertSet('importFile', null)
            ->assertCount('importErrors', 1);

        $this->assertDatabaseHas('todos', ['user_id' => $this->user->id, 'title' => 'Impor satu']);
    }

    public function test_import_unreadable_file(): void
    {
        $file = UploadedFile::fake()->createWithContent('rusak.xlsx', "\x00\x01\x02\x03\xff\xfe\xfd\x00\x10");

        Livewire::test(TodoList::class)
            ->set('importFile', $file)
            ->call('import')
            ->assertCount('importErrors', 1)
            ->assertSet('importFile', null);

        $this->assertSame(0, Todo::query()->count());
    }

    public function test_import_requires_valid_file(): void
    {
        Livewire::test(TodoList::class)->call('import')->assertHasErrors(['importFile' => 'required']);

        Livewire::test(TodoList::class)
            ->set('importFile', UploadedFile::fake()->create('x.pdf', 10))
            ->call('import')
            ->assertHasErrors(['importFile']);
    }

    private function stubAi(array $items): void
    {
        $stub = $this->createStub(AiClientInterface::class);
        $stub->method('generate')->willReturn($items);
        $this->app->instance(AiClientInterface::class, $stub);
    }

    public function test_ai_generate_preview_and_save_selected(): void
    {
        $this->stubAi([
            new GeneratedTodoItem('Item A', 'Desk A', TodoStatus::Pending, '2026-12-01'),
            new GeneratedTodoItem('Item B'),
        ]);

        Livewire::test(TodoList::class)
            ->call('openAi')
            ->assertSet('showAi', true)
            ->set('aiPrompt', 'rencana belajar')
            ->call('generateAi')
            ->assertHasNoErrors()
            ->assertCount('aiItems', 2)
            ->assertSee('Item A')
            ->assertSee('Item B')
            ->set('aiSelected', ['1'])
            ->call('saveAi')
            ->assertHasNoErrors()
            ->assertSet('showAi', false);

        $this->assertDatabaseHas('todos', ['user_id' => $this->user->id, 'title' => 'Item B']);
        $this->assertDatabaseMissing('todos', ['title' => 'Item A']);
    }

    public function test_ai_generate_validation(): void
    {
        Livewire::test(TodoList::class)
            ->call('openAi')
            ->call('generateAi')
            ->assertHasErrors(['aiPrompt'])
            ->set('aiPrompt', 'valid')
            ->set('aiCount', 99)
            ->call('generateAi')
            ->assertHasErrors(['aiCount']);
    }

    public function test_ai_generate_handles_service_error(): void
    {
        $stub = $this->createStub(AiClientInterface::class);
        $stub->method('generate')->willThrowException(new RuntimeException('Layanan AI mati'));
        $this->app->instance(AiClientInterface::class, $stub);

        Livewire::test(TodoList::class)
            ->set('aiPrompt', 'apa saja')
            ->call('generateAi')
            ->assertHasErrors(['aiPrompt'])
            ->assertSet('aiItems', []);
    }

    public function test_ai_generate_handles_empty_result(): void
    {
        $this->stubAi([]);

        Livewire::test(TodoList::class)
            ->set('aiPrompt', 'apa saja')
            ->call('generateAi')
            ->assertHasErrors(['aiPrompt']);
    }

    public function test_ai_save_requires_selection_and_ignores_tampered_items(): void
    {
        Livewire::test(TodoList::class)
            ->set('aiItems', [['title' => '', 'description' => 'tanpa judul']])
            ->set('aiSelected', ['0', '5'])
            ->call('saveAi')
            ->assertHasErrors(['aiSelected']);

        $this->assertSame(0, Todo::query()->count());
    }

    public function test_close_ai_resets_state(): void
    {
        Livewire::test(TodoList::class)
            ->set('showAi', true)
            ->set('aiPrompt', 'x')
            ->set('aiItems', [['title' => 'A']])
            ->call('closeAi')
            ->assertSet('showAi', false)
            ->assertSet('aiPrompt', '')
            ->assertSet('aiItems', []);
    }
}
