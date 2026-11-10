<?php

namespace Tests\Unit\DTO;

use App\Features\Ai\DTO\GeneratedTodoItem;
use App\Features\Todo\Enums\TodoStatus;
use PHPUnit\Framework\TestCase;

class GeneratedTodoItemTest extends TestCase
{
    public function test_from_array_with_complete_data(): void
    {
        $item = GeneratedTodoItem::fromArray([
            'title' => '  Belajar Livewire ',
            'description' => 'Baca **dokumentasi**',
            'status' => 'in_progress',
            'due_date' => '2026-12-31',
        ]);

        $this->assertInstanceOf(GeneratedTodoItem::class, $item);
        $this->assertSame('Belajar Livewire', $item->title);
        $this->assertSame('Baca **dokumentasi**', $item->description);
        $this->assertSame(TodoStatus::InProgress, $item->status);
        $this->assertSame('2026-12-31', $item->dueDate);
    }

    public function test_from_array_returns_null_when_title_is_empty(): void
    {
        $this->assertNull(GeneratedTodoItem::fromArray(['title' => '   ']));
        $this->assertNull(GeneratedTodoItem::fromArray([]));
    }

    public function test_defaults_for_invalid_values(): void
    {
        $item = GeneratedTodoItem::fromArray([
            'title' => 'X',
            'description' => '  ',
            'status' => 'tidak-ada',
            'due_date' => 'bukan-tanggal',
        ]);

        $this->assertNull($item->description);
        $this->assertSame(TodoStatus::Pending, $item->status);
        $this->assertNull($item->dueDate);
    }

    public function test_due_date_must_be_a_string(): void
    {
        $this->assertNull(GeneratedTodoItem::fromArray(['title' => 'X', 'due_date' => 123])->dueDate);
        $this->assertNull(GeneratedTodoItem::fromArray(['title' => 'X', 'due_date' => ''])->dueDate);
    }

    public function test_long_values_are_truncated(): void
    {
        $item = GeneratedTodoItem::fromArray([
            'title' => str_repeat('a', 300),
            'description' => str_repeat('b', 6000),
        ]);

        $this->assertSame(255, mb_strlen($item->title));
        $this->assertSame(5000, mb_strlen($item->description));
    }

    public function test_to_array(): void
    {
        $item = new GeneratedTodoItem('Judul', 'Desk', TodoStatus::Completed, '2026-01-02');

        $this->assertSame([
            'title' => 'Judul',
            'description' => 'Desk',
            'status' => 'completed',
            'due_date' => '2026-01-02',
        ], $item->toArray());
    }
}
