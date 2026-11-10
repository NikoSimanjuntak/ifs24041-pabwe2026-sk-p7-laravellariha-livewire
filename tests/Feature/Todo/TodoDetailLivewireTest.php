<?php

namespace Tests\Feature\Todo;

use App\Features\Todo\Enums\TodoStatus;
use App\Features\Todo\Livewire\TodoDetail;
use App\Features\Todo\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TodoDetailLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Todo $todo;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create();
        $this->todo = Todo::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Detail saya',
            'description' => '**tebal**',
            'due_date' => '2026-10-10',
        ]);
        $this->actingAs($this->user);
    }

    public function test_shows_detail_with_rendered_markdown(): void
    {
        Livewire::test(TodoDetail::class, ['todo' => $this->todo])
            ->assertSee('Detail saya')
            ->assertSeeHtml('<strong>tebal</strong>')
            ->assertSet('title', 'Detail saya')
            ->assertSet('dueDate', '2026-10-10');
    }

    public function test_shows_placeholder_without_description_or_due_date(): void
    {
        $todo = Todo::factory()->create(['user_id' => $this->user->id, 'description' => null, 'due_date' => null]);

        Livewire::test(TodoDetail::class, ['todo' => $todo])->assertSee('Belum ada deskripsi.');
    }

    public function test_shows_cover_and_overdue_date(): void
    {
        $todo = Todo::factory()->create([
            'user_id' => $this->user->id,
            'due_date' => '2000-01-01',
            'cover_image_path' => 'covers/c.jpg',
        ]);

        Livewire::test(TodoDetail::class, ['todo' => $todo])->assertSeeHtml('covers/c.jpg');
    }

    public function test_other_member_cannot_open(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(TodoDetail::class, ['todo' => $this->todo])->assertForbidden();
    }

    public function test_admin_can_view_but_not_edit(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(TodoDetail::class, ['todo' => $this->todo])->assertOk()->call('startEdit')->assertForbidden();
        Livewire::test(TodoDetail::class, ['todo' => $this->todo])->call('delete')->assertForbidden();
    }

    public function test_edit_flow(): void
    {
        Livewire::test(TodoDetail::class, ['todo' => $this->todo])
            ->call('startEdit')
            ->assertSet('editing', true)
            ->set('title', 'Judul baru')
            ->set('description', '# Heading')
            ->set('status', 'in_progress')
            ->set('dueDate', '')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('editing', false);

        $fresh = $this->todo->fresh();
        $this->assertSame('Judul baru', $fresh->title);
        $this->assertSame(TodoStatus::InProgress, $fresh->status);
        $this->assertNull($fresh->due_date);
    }

    public function test_save_with_empty_description(): void
    {
        Livewire::test(TodoDetail::class, ['todo' => $this->todo])
            ->call('startEdit')
            ->set('description', '')
            ->call('save');

        $this->assertNull($this->todo->fresh()->description);
    }

    public function test_edit_validation_and_cancel(): void
    {
        Livewire::test(TodoDetail::class, ['todo' => $this->todo])
            ->call('startEdit')
            ->set('title', '')
            ->set('status', 'ngawur')
            ->call('save')
            ->assertHasErrors(['title', 'status'])
            ->call('cancelEdit')
            ->assertSet('editing', false);
    }

    public function test_toggle_complete(): void
    {
        Livewire::test(TodoDetail::class, ['todo' => $this->todo])
            ->call('toggleComplete')
            ->assertSet('status', 'completed')
            ->call('toggleComplete')
            ->assertSet('status', 'pending');
    }

    public function test_upload_and_remove_cover(): void
    {
        Livewire::test(TodoDetail::class, ['todo' => $this->todo])
            ->set('cover', UploadedFile::fake()->image('cover.jpg'))
            ->call('uploadCover')
            ->assertHasNoErrors()
            ->assertSet('cover', null);

        $path = $this->todo->fresh()->cover_image_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        Livewire::test(TodoDetail::class, ['todo' => $this->todo->fresh()])->call('removeCover');

        $this->assertNull($this->todo->fresh()->cover_image_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_cover_validation(): void
    {
        Livewire::test(TodoDetail::class, ['todo' => $this->todo])->call('uploadCover')->assertHasErrors(['cover' => 'required']);

        Livewire::test(TodoDetail::class, ['todo' => $this->todo])
            ->set('cover', UploadedFile::fake()->create('dok.pdf', 10, 'application/pdf'))
            ->call('uploadCover')
            ->assertHasErrors(['cover']);
    }

    public function test_delete_redirects_to_list(): void
    {
        Livewire::test(TodoDetail::class, ['todo' => $this->todo])
            ->call('delete')
            ->assertRedirect(route('todos.index'));

        $this->assertDatabaseMissing('todos', ['id' => $this->todo->id]);
    }
}
