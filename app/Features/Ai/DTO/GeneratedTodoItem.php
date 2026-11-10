<?php

namespace App\Features\Ai\DTO;

use App\Features\Todo\Enums\TodoStatus;
use Carbon\Carbon;
use Throwable;

final readonly class GeneratedTodoItem
{
    public function __construct(
        public string $title,
        public ?string $description = null,
        public TodoStatus $status = TodoStatus::Pending,
        public ?string $dueDate = null,
    ) {}

    /**
     * Membuat DTO dari array mentah. Mengembalikan null jika judul kosong.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): ?self
    {
        $title = trim((string) ($data['title'] ?? ''));

        if ($title === '') {
            return null;
        }

        $description = trim((string) ($data['description'] ?? ''));

        return new self(
            title: mb_substr($title, 0, 255),
            description: $description === '' ? null : mb_substr($description, 0, 5000),
            status: TodoStatus::tryFrom((string) ($data['status'] ?? '')) ?? TodoStatus::Pending,
            dueDate: self::normalizeDate($data['due_date'] ?? null),
        );
    }

    /**
     * @return array{title: string, description: ?string, status: string, due_date: ?string}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'due_date' => $this->dueDate,
        ];
    }

    private static function normalizeDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
