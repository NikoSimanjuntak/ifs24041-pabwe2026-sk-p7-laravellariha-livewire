<?php

namespace Tests\Unit\Services;

use App\Features\Profile\Services\ProfileService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = new ProfileService;
    }

    public function test_update_profile(): void
    {
        $user = User::factory()->create();

        $this->service->updateProfile($user, ['name' => 'Baru', 'username' => 'baru', 'email' => 'baru@example.com']);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Baru', 'username' => 'baru', 'email' => 'baru@example.com']);
    }

    public function test_update_password(): void
    {
        $user = User::factory()->create(['password' => 'lama12345']);

        $this->assertFalse($this->service->updatePassword($user, 'salah', 'baru12345'));
        $this->assertTrue(Hash::check('lama12345', $user->fresh()->password));

        $this->assertTrue($this->service->updatePassword($user, 'lama12345', 'baru12345'));
        $this->assertTrue(Hash::check('baru12345', $user->fresh()->password));
    }

    public function test_update_and_remove_photo(): void
    {
        $user = User::factory()->create();

        $this->service->updatePhoto($user, UploadedFile::fake()->image('a.jpg'));
        $first = $user->fresh()->profile_photo_path;
        Storage::disk('public')->assertExists($first);

        $this->service->updatePhoto($user, UploadedFile::fake()->image('b.jpg'));
        Storage::disk('public')->assertMissing($first);

        $second = $user->fresh()->profile_photo_path;
        $this->service->removePhoto($user);
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($user->fresh()->profile_photo_path);

        // tanpa foto -> tidak error
        $this->service->removePhoto($user);
        $this->assertNull($user->fresh()->profile_photo_path);
    }
}
