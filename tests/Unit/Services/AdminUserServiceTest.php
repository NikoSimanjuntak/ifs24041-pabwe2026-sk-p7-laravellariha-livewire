<?php

namespace Tests\Unit\Services;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Todo\Models\Todo;
use App\Features\Todo\Repositories\TodoRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminUserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdminUserService(new TodoRepository);
    }

    public function test_paginate_users_with_todo_count_and_search(): void
    {
        $alice = User::factory()->create(['name' => 'Alice', 'username' => 'alice', 'email' => 'alice@example.com']);
        User::factory()->create(['name' => 'Bob', 'username' => 'bobby', 'email' => 'bob@example.com']);
        Todo::factory()->count(2)->create(['user_id' => $alice->id]);

        $all = $this->service->paginateUsers();
        $this->assertSame(2, $all->total());
        $this->assertSame(2, $all->items()[0]->todos_count);

        $this->assertSame(1, $this->service->paginateUsers('alice')->total());
        $this->assertSame(1, $this->service->paginateUsers('bobby')->total());
        $this->assertSame(1, $this->service->paginateUsers('bob@example')->total());
        $this->assertSame(0, $this->service->paginateUsers('zzz')->total());
    }

    public function test_paginate_todos_of_user(): void
    {
        $user = User::factory()->create();
        Todo::factory()->create(['user_id' => $user->id, 'title' => 'Target']);
        Todo::factory()->create();

        $this->assertSame(1, $this->service->paginateTodos($user)->total());
        $this->assertSame(1, $this->service->paginateTodos($user, 'Target', 'pending')->total());
    }
}
