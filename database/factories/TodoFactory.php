<?php

namespace Database\Factories;

use App\Features\Todo\Enums\TodoStatus;
use App\Features\Todo\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Todo>
 */
class TodoFactory extends Factory
{
    protected $model = Todo::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => TodoStatus::Pending,
            'due_date' => fake()->dateTimeBetween('+1 day', '+1 month')->format('Y-m-d'),
            'cover_image_path' => null,
        ];
    }

    public function status(TodoStatus $status): static
    {
        return $this->state(fn (array $attributes): array => ['status' => $status]);
    }
}
