<?php

namespace App\Features\Todo\Models;

use App\Features\Todo\Enums\TodoStatus;
use App\Models\User;
use Database\Factories\TodoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Todo extends Model
{
    /** @use HasFactory<TodoFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'due_date',
        'cover_image_path',
    ];

    protected function casts(): array
    {
        return [
            'status' => TodoStatus::class,
            'due_date' => 'date',
        ];
    }

    protected static function newFactory(): TodoFactory
    {
        return TodoFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === TodoStatus::Completed;
    }

    public function isOverdue(): bool
    {
        if ($this->due_date === null || $this->isCompleted()) {
            return false;
        }

        return $this->due_date->copy()->endOfDay()->isPast();
    }

    public function coverUrl(): ?string
    {
        if ($this->cover_image_path === null) {
            return null;
        }

        return Storage::disk('public')->url($this->cover_image_path);
    }

    public function descriptionHtml(): string
    {
        if (blank($this->description)) {
            return '';
        }

        return Str::markdown($this->description, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
