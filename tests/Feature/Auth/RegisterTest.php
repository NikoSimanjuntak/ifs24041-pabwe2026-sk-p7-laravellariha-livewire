<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Livewire\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_is_accessible(): void
    {
        $this->get('/register')->assertOk()->assertSee('Daftar');
    }

    public function test_user_can_register_as_member(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Budi')
            ->set('username', 'budi_01')
            ->set('email', 'budi@example.com')
            ->set('password', 'rahasia123')
            ->set('password_confirmation', 'rahasia123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('todos.index'));

        $user = User::query()->where('username', 'budi_01')->firstOrFail();
        $this->assertSame('member', $user->role);
        $this->assertNotSame('rahasia123', $user->password);
        $this->assertAuthenticatedAs($user);
    }

    public function test_validation_errors(): void
    {
        User::factory()->create(['username' => 'dipakai', 'email' => 'dipakai@example.com']);

        Livewire::test(Register::class)
            ->call('register')
            ->assertHasErrors(['name', 'username', 'email', 'password']);

        Livewire::test(Register::class)
            ->set('name', 'X')
            ->set('username', 'dipakai')
            ->set('email', 'dipakai@example.com')
            ->set('password', 'rahasia123')
            ->set('password_confirmation', 'beda')
            ->call('register')
            ->assertHasErrors(['username' => 'unique', 'email' => 'unique', 'password' => 'confirmed']);
    }

    public function test_role_cannot_be_injected(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Budi')
            ->set('username', 'budi')
            ->set('email', 'budi@example.com')
            ->set('password', 'rahasia123')
            ->set('password_confirmation', 'rahasia123')
            ->call('register');

        $this->assertSame('member', User::query()->where('username', 'budi')->value('role'));
    }
}
