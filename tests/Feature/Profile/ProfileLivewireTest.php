<?php

namespace Tests\Feature\Profile;

use App\Features\Profile\Livewire\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create([
            'name' => 'Budi',
            'username' => 'budi',
            'email' => 'budi@example.com',
            'password' => 'lama12345',
        ]);
        $this->actingAs($this->user);
    }

    public function test_form_is_prefilled(): void
    {
        Livewire::test(Profile::class)
            ->assertSet('name', 'Budi')
            ->assertSet('username', 'budi')
            ->assertSet('email', 'budi@example.com');
    }

    public function test_update_profile(): void
    {
        Livewire::test(Profile::class)
            ->set('name', 'Budi Baru')
            ->set('username', 'budi_baru')
            ->set('email', 'baru@example.com')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'name' => 'Budi Baru', 'username' => 'budi_baru']);
    }

    public function test_can_keep_own_username_and_email(): void
    {
        Livewire::test(Profile::class)->set('name', 'Budi 2')->call('saveProfile')->assertHasNoErrors();
    }

    public function test_profile_validation_rejects_duplicates(): void
    {
        User::factory()->create(['username' => 'dipakai', 'email' => 'dipakai@example.com']);

        Livewire::test(Profile::class)
            ->set('username', 'dipakai')
            ->set('email', 'dipakai@example.com')
            ->call('saveProfile')
            ->assertHasErrors(['username' => 'unique', 'email' => 'unique']);

        Livewire::test(Profile::class)->set('name', '')->call('saveProfile')->assertHasErrors(['name']);
    }

    public function test_change_password(): void
    {
        Livewire::test(Profile::class)
            ->set('current_password', 'lama12345')
            ->set('password', 'baru12345')
            ->set('password_confirmation', 'baru12345')
            ->call('savePassword')
            ->assertHasNoErrors()
            ->assertSet('current_password', '')
            ->assertSet('password', '');

        $this->assertTrue(Hash::check('baru12345', $this->user->fresh()->password));
    }

    public function test_change_password_with_wrong_current_password(): void
    {
        Livewire::test(Profile::class)
            ->set('current_password', 'salah')
            ->set('password', 'baru12345')
            ->set('password_confirmation', 'baru12345')
            ->call('savePassword')
            ->assertHasErrors(['current_password']);

        $this->assertTrue(Hash::check('lama12345', $this->user->fresh()->password));
    }

    public function test_change_password_validation(): void
    {
        Livewire::test(Profile::class)
            ->set('current_password', 'lama12345')
            ->set('password', 'pendek')
            ->set('password_confirmation', 'beda')
            ->call('savePassword')
            ->assertHasErrors(['password']);
    }

    public function test_upload_and_remove_photo(): void
    {
        Livewire::test(Profile::class)
            ->set('photo', UploadedFile::fake()->image('foto.jpg'))
            ->call('savePhoto')
            ->assertHasNoErrors()
            ->assertSet('photo', null);

        $path = $this->user->fresh()->profile_photo_path;
        Storage::disk('public')->assertExists($path);

        Livewire::test(Profile::class)->assertSeeHtml($path)->call('removePhoto');

        $this->assertNull($this->user->fresh()->profile_photo_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_photo_validation(): void
    {
        Livewire::test(Profile::class)->call('savePhoto')->assertHasErrors(['photo' => 'required']);

        Livewire::test(Profile::class)
            ->set('photo', UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'))
            ->call('savePhoto')
            ->assertHasErrors(['photo']);
    }
}
