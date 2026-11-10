<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Livewire\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_has_required_element_ids(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('id="login-username"', false)
            ->assertSee('id="login-password"', false)
            ->assertSee('id="login-submit"', false);
    }

    public function test_member_can_login_with_username(): void
    {
        $user = User::factory()->create(['username' => 'budi', 'password' => 'rahasia123']);

        Livewire::test(Login::class)
            ->set('username', 'budi')
            ->set('password', 'rahasia123')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(route('todos.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_member_can_login_with_email(): void
    {
        $user = User::factory()->create(['email' => 'budi@example.com', 'password' => 'rahasia123']);

        Livewire::test(Login::class)
            ->set('username', 'budi@example.com')
            ->set('password', 'rahasia123')
            ->set('remember', true)
            ->call('authenticate')
            ->assertRedirect(route('todos.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_is_redirected_to_admin_users(): void
    {
        User::factory()->admin()->create(['username' => 'admin', 'password' => 'admin']);

        Livewire::test(Login::class)
            ->set('username', 'admin')
            ->set('password', 'admin')
            ->call('authenticate')
            ->assertRedirect(route('admin.users.index'));
    }

    public function test_wrong_credentials_show_error(): void
    {
        User::factory()->create(['username' => 'budi', 'password' => 'rahasia123']);

        Livewire::test(Login::class)
            ->set('username', 'budi')
            ->set('password', 'salah')
            ->call('authenticate')
            ->assertHasErrors(['username']);

        $this->assertGuest();
    }

    public function test_fields_are_required(): void
    {
        Livewire::test(Login::class)
            ->call('authenticate')
            ->assertHasErrors(['username' => 'required', 'password' => 'required']);
    }

    public function test_too_many_attempts_are_throttled(): void
    {
        User::factory()->create(['username' => 'budi', 'password' => 'rahasia123']);
        $component = Livewire::test(Login::class)->set('username', 'budi')->set('password', 'salah');

        for ($i = 0; $i < 5; $i++) {
            $component->call('authenticate');
        }

        // Percobaan ke-6 diblokir meskipun password benar.
        $component->set('password', 'rahasia123')->call('authenticate')->assertHasErrors(['username']);
        $this->assertStringContainsString('Terlalu banyak', $component->errors()->first('username'));
        $this->assertGuest();
    }
}
