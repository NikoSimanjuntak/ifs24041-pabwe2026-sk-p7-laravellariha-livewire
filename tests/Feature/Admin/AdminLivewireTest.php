<?php

namespace Tests\Feature\Admin;

use App\Features\Admin\Livewire\AdminUserTodoList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Todo\Enums\TodoStatus;
use App\Features\Todo\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create(['name' => 'Super Admin']));
    }

    public function test_user_list_shows_users_with_todo_counts(): void
    {
        $member = User::factory()->create(['name' => 'Member Satu', 'username' => 'satu']);
        Todo::factory()->count(3)->create(['user_id' => $member->id]);

        Livewire::test(UserList::class)
            ->assertSee('Member Satu')
            ->assertSee('Super Admin')
            ->assertSee(route('admin.users.todos', $member), false);
    }

    public function test_user_list_search_and_empty_state(): void
    {
        User::factory()->create(['name' => 'Cari Aku', 'username' => 'cariaku']);
        User::factory()->create(['name' => 'Yang Lain', 'username' => 'lainnya']);

        Livewire::test(UserList::class)
            ->set('search', 'cariaku')
            ->assertSee('Cari Aku')
            ->assertDontSee('Yang Lain')
            ->set('search', 'tidak-ada-sama-sekali')
            ->assertSee('Tidak ada pengguna.');
    }

    public function test_admin_sees_user_todos_read_only(): void
    {
        $member = User::factory()->create(['name' => 'Pemilik']);
        Todo::factory()->create(['user_id' => $member->id, 'title' => 'Todo pemilik', 'description' => '**penting**', 'cover_image_path' => 'covers/z.jpg']);
        Todo::factory()->status(TodoStatus::Completed)->create(['user_id' => $member->id, 'title' => 'Selesai', 'description' => null, 'due_date' => null]);
        Todo::factory()->create(['title' => 'Bukan milik pemilik']);

        Livewire::test(AdminUserTodoList::class, ['user' => $member])
            ->assertSee('Todolist Pemilik')
            ->assertSee('Todo pemilik')
            ->assertSeeHtml('<strong>penting</strong>')
            ->assertSeeHtml('covers/z.jpg')
            ->assertDontSee('Bukan milik pemilik')
            ->assertDontSee('Hapus')
            ->assertDontSee('Edit');
    }

    public function test_admin_todo_list_filters_and_empty_state(): void
    {
        $member = User::factory()->create();
        Todo::factory()->create(['user_id' => $member->id, 'title' => 'Alpha']);
        Todo::factory()->status(TodoStatus::Completed)->create(['user_id' => $member->id, 'title' => 'Beta']);

        Livewire::test(AdminUserTodoList::class, ['user' => $member])
            ->set('search', 'Alpha')
            ->assertSee('Alpha')
            ->assertDontSee('Beta')
            ->set('search', '')
            ->set('statusFilter', 'completed')
            ->assertSee('Beta')
            ->assertDontSee('Alpha');

        Livewire::test(AdminUserTodoList::class, ['user' => User::factory()->create()])
            ->assertSee('Pengguna ini belum memiliki todo.');
    }

    public function test_admin_has_no_way_to_modify_user_todos(): void
    {
        $this->assertFalse(method_exists(AdminUserTodoList::class, 'delete'));
        $this->assertFalse(method_exists(AdminUserTodoList::class, 'save'));
        $this->assertFalse(method_exists(AdminUserTodoList::class, 'toggleComplete'));
    }
}
