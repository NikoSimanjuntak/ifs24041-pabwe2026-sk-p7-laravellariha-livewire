<?php

namespace Tests\Unit\Models;

use App\Features\Todo\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles(): void
    {
        $this->assertTrue(User::factory()->admin()->make()->isAdmin());
        $this->assertFalse(User::factory()->make()->isAdmin());
        $this->assertTrue(User::factory()->make()->isMember());
        $this->assertFalse(User::factory()->admin()->make()->isMember());
    }

    public function test_has_many_todos(): void
    {
        $user = User::factory()->create();
        Todo::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->todos);
    }

    public function test_password_is_hashed(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->assertNotSame('rahasia123', $user->password);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_profile_photo_url_and_initial(): void
    {
        $user = User::factory()->make(['name' => 'budi', 'profile_photo_path' => null]);
        $this->assertNull($user->profilePhotoUrl());
        $this->assertSame('B', $user->initial());

        $user->profile_photo_path = 'profile-photos/a.jpg';
        $this->assertStringContainsString('profile-photos/a.jpg', $user->profilePhotoUrl());
    }
}
