<?php

namespace Tests\Unit\Models;

use App\Features\Todo\Enums\TodoStatus;
use App\Features\Todo\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_and_relation(): void
    {
        $todo = Todo::factory()->create(['due_date' => '2026-12-31']);

        $this->assertSame(TodoStatus::Pending, $todo->status);
        $this->assertSame('2026-12-31', $todo->due_date->format('Y-m-d'));
        $this->assertInstanceOf(User::class, $todo->user);
    }

    public function test_factory_status_state(): void
    {
        $this->assertTrue(Todo::factory()->status(TodoStatus::Completed)->create()->isCompleted());
        $this->assertFalse(Todo::factory()->create()->isCompleted());
    }

    public function test_is_overdue(): void
    {
        $this->assertFalse(Todo::factory()->make(['due_date' => null])->isOverdue());
        $this->assertFalse(Todo::factory()->status(TodoStatus::Completed)->make(['due_date' => '2000-01-01'])->isOverdue());
        $this->assertTrue(Todo::factory()->make(['due_date' => '2000-01-01'])->isOverdue());
        $this->assertFalse(Todo::factory()->make(['due_date' => now()->addDays(5)->toDateString()])->isOverdue());
    }

    public function test_cover_url(): void
    {
        $this->assertNull(Todo::factory()->make()->coverUrl());
        $this->assertStringContainsString('covers/a.jpg', Todo::factory()->make(['cover_image_path' => 'covers/a.jpg'])->coverUrl());
    }

    public function test_description_html(): void
    {
        $this->assertSame('', Todo::factory()->make(['description' => null])->descriptionHtml());

        $html = Todo::factory()->make(['description' => "**tebal**\n\n<script>alert(1)</script>"])->descriptionHtml();

        $this->assertStringContainsString('<strong>tebal</strong>', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }
}
