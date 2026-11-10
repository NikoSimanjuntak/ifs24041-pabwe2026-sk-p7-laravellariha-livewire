<?php

namespace Tests\Unit\Policies;

use App\Features\Todo\Models\Todo;
use App\Features\Todo\Policies\TodoPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_owner_permissions(): void
    {
        $owner = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $owner->id]);
        $policy = new TodoPolicy;

        $this->assertTrue($policy->viewAny($owner));
        $this->assertTrue($policy->create($owner));
        $this->assertTrue($policy->view($owner, $todo));
        $this->assertTrue($policy->update($owner, $todo));
        $this->assertTrue($policy->delete($owner, $todo));
    }

    public function test_other_member_is_denied(): void
    {
        $todo = Todo::factory()->create();
        $other = User::factory()->create();
        $policy = new TodoPolicy;

        $this->assertFalse($policy->view($other, $todo));
        $this->assertFalse($policy->update($other, $todo));
        $this->assertFalse($policy->delete($other, $todo));
    }

    public function test_admin_is_read_only(): void
    {
        $todo = Todo::factory()->create();
        $admin = User::factory()->admin()->create();
        $policy = new TodoPolicy;

        $this->assertTrue($policy->view($admin, $todo));
        $this->assertFalse($policy->viewAny($admin));
        $this->assertFalse($policy->create($admin));
        $this->assertFalse($policy->update($admin, $todo));
        $this->assertFalse($policy->delete($admin, $todo));
    }
}
