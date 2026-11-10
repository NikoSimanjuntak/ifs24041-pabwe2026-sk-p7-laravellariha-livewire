<?php

namespace Tests\Unit\Services;

use App\Features\Todo\Enums\TodoStatus;
use App\Features\Todo\Models\Todo;
use App\Features\Todo\Repositories\TodoRepository;
use App\Features\Todo\Services\TodoService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TodoServiceTest extends TestCase
{
    use RefreshDatabase;

    private TodoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = new TodoService(new TodoRepository);
    }

    public function test_paginate_all_and_find(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id, 'title' => 'Cari aku']);
        Todo::factory()->create(['user_id' => $user->id]);

        $this->assertSame(1, $this->service->paginate($user->id, 'Cari')->total());
        $this->assertCount(2, $this->service->all($user->id));
        $this->assertTrue($this->service->find($todo->id)->is($todo));
    }

    public function test_create_and_update(): void
    {
        $user = User::factory()->create();

        $todo = $this->service->create($user->id, ['title' => 'Satu', 'status' => 'pending']);
        $this->assertSame($user->id, $todo->user_id);

        $updated = $this->service->update($todo, ['title' => 'Dua']);
        $this->assertSame('Dua', $updated->title);
    }

    public function test_toggle_complete_both_directions(): void
    {
        $todo = Todo::factory()->create();

        $done = $this->service->toggleComplete($todo);
        $this->assertSame(TodoStatus::Completed, $done->status);

        $undone = $this->service->toggleComplete($done);
        $this->assertSame(TodoStatus::Pending, $undone->status);
    }

    public function test_store_cover_replaces_old_file(): void
    {
        $todo = Todo::factory()->create();

        $todo = $this->service->storeCover($todo, UploadedFile::fake()->image('a.jpg'));
        $first = $todo->cover_image_path;
        Storage::disk('public')->assertExists($first);

        $todo = $this->service->storeCover($todo, UploadedFile::fake()->image('b.jpg'));
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($todo->cover_image_path);
    }

    public function test_remove_cover(): void
    {
        $todo = $this->service->storeCover(Todo::factory()->create(), UploadedFile::fake()->image('a.jpg'));
        $path = $todo->cover_image_path;

        $todo = $this->service->removeCover($todo);

        $this->assertNull($todo->cover_image_path);
        Storage::disk('public')->assertMissing($path);

        // tanpa cover -> tidak error
        $this->assertNull($this->service->removeCover($todo)->cover_image_path);
    }

    public function test_delete_removes_record_and_cover(): void
    {
        $todo = $this->service->storeCover(Todo::factory()->create(), UploadedFile::fake()->image('a.jpg'));
        $path = $todo->cover_image_path;

        $this->service->delete($todo);

        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_delete_without_cover(): void
    {
        $todo = Todo::factory()->create();

        $this->service->delete($todo);

        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    public function test_create_many(): void
    {
        $user = User::factory()->create();

        $this->assertSame(2, $this->service->createMany($user->id, [['title' => 'A'], ['title' => 'B']]));
    }
}
