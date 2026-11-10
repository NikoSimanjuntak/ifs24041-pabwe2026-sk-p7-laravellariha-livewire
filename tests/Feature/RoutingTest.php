<?php

namespace Tests\Feature;

use App\Features\Todo\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_by_role(): void
    {
        $this->get('/')->assertRedirect(route('login'));

        $this->actingAs(User::factory()->admin()->create())->get('/')->assertRedirect(route('admin.users.index'));
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('todos.index'));
    }

    public function test_guest_pages_and_guest_redirects(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();

        foreach (['/todos', '/profile', '/admin/users'] as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
    }

    public function test_authenticated_users_are_redirected_away_from_guest_pages(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect(route('todos.index'));
        $this->actingAs(User::factory()->admin()->create())->get('/register')->assertRedirect(route('admin.users.index'));
    }

    public function test_member_pages(): void
    {
        $member = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $member->id]);

        $this->actingAs($member);
        $this->get('/todos')->assertOk()->assertSee('Todolist Saya');
        $this->get('/todos/'.$todo->id)->assertOk()->assertSee($todo->title);
        $this->get('/profile')->assertOk();
        $this->get('/admin/users')->assertForbidden();
    }

    public function test_member_cannot_open_other_members_todo(): void
    {
        $other = Todo::factory()->create();

        $this->actingAs(User::factory()->create())->get('/todos/'.$other->id)->assertForbidden();
    }

    public function test_missing_todo_returns_404(): void
    {
        $this->actingAs(User::factory()->create())->get('/todos/99999')->assertNotFound();
    }

    public function test_admin_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $member->id]);

        $this->actingAs($admin);
        $this->get('/admin/users')->assertOk();
        $this->get('/admin/users/'.$member->id.'/todos')->assertOk()->assertSee($todo->title);
        $this->get('/profile')->assertOk();
        $this->get('/todos')->assertForbidden();
        $this->get('/todos/'.$todo->id)->assertForbidden();
    }

    public function test_logout(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_layout_renders_sidebar_for_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Siti']))
            ->get('/todos')
            ->assertSee('Siti')
            ->assertSee('id="logout-button"', false);

        $this->actingAs(User::factory()->admin()->create(['profile_photo_path' => 'profile-photos/a.jpg']))
            ->get('/admin/users')
            ->assertSee('profile-photos/a.jpg', false);
    }

    public function test_health_endpoint(): void
    {
        $this->get('/up')->assertOk();
    }
}
