<?php

namespace Tests\Unit\Repositories;

use App\Features\Todo\Enums\TodoStatus;
use App\Features\Todo\Models\Todo;
use App\Features\Todo\Repositories\TodoRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TodoRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new TodoRepository;
    }

    public function test_paginate_only_returns_own_todos(): void
    {
        $user = User::factory()->create();
        Todo::factory()->count(3)->create(['user_id' => $user->id]);
        Todo::factory()->count(2)->create();

        $page = $this->repo->paginateForUser($user->id, null, null, 2);

        $this->assertSame(3, $page->total());
        $this->assertCount(2, $page->items());
    }

    public function test_paginate_search_and_status_filter(): void
    {
        $user = User::factory()->create();
        Todo::factory()->create(['user_id' => $user->id, 'title' => 'Beli susu', 'description' => 'x']);
        Todo::factory()->create(['user_id' => $user->id, 'title' => 'Lain', 'description' => 'cari kata kunci']);
        Todo::factory()->status(TodoStatus::Completed)->create(['user_id' => $user->id, 'title' => 'Selesai']);

        $this->assertSame(1, $this->repo->paginateForUser($user->id, 'susu')->total());
        $this->assertSame(1, $this->repo->paginateForUser($user->id, 'kunci')->total());
        $this->assertSame(1, $this->repo->paginateForUser($user->id, null, 'completed')->total());
        $this->assertSame(0, $this->repo->paginateForUser($user->id, 'susu', 'completed')->total());
    }

    public function test_all_for_user(): void
    {
        $user = User::factory()->create();
        Todo::factory()->count(2)->create(['user_id' => $user->id]);
        Todo::factory()->create();

        $this->assertCount(2, $this->repo->allForUser($user->id));
    }

    public function test_find(): void
    {
        $todo = Todo::factory()->create();

        $this->assertTrue($this->repo->find($todo->id)->is($todo));

        $this->expectException(ModelNotFoundException::class);
        $this->repo->find(9999);
    }

    public function test_create_update_delete(): void
    {
        $user = User::factory()->create();

        $todo = $this->repo->create(['user_id' => $user->id, 'title' => 'Baru']);
        $this->assertDatabaseHas('todos', ['title' => 'Baru']);

        $updated = $this->repo->update($todo, ['title' => 'Diubah']);
        $this->assertSame('Diubah', $updated->title);

        $this->assertTrue($this->repo->delete($todo));
        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    public function test_create_many(): void
    {
        $user = User::factory()->create();

        $count = $this->repo->createMany($user->id, [
            ['title' => 'A', 'status' => TodoStatus::Pending],
            ['title' => 'B', 'status' => 'completed'],
        ]);

        $this->assertSame(2, $count);
        $this->assertSame(2, Todo::query()->where('user_id', $user->id)->count());
    }
}
