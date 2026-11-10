<?php

namespace App\Features\Todo\Repositories;

use App\Features\Todo\Contracts\TodoRepositoryInterface;
use App\Features\Todo\Models\Todo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TodoRepository implements TodoRepositoryInterface
{
    public function paginateForUser(int $userId, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return Todo::query()
            ->where('user_id', $userId)
            ->when($search, function (Builder $query, string $term): void {
                $query->where(function (Builder $inner) use ($term): void {
                    $inner->where('title', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->when($status, function (Builder $query, string $value): void {
                $query->where('status', $value);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function allForUser(int $userId): Collection
    {
        return Todo::query()->where('user_id', $userId)->orderBy('id')->get();
    }

    public function find(int $id): Todo
    {
        return Todo::query()->findOrFail($id);
    }

    public function create(array $data): Todo
    {
        return Todo::query()->create($data);
    }

    public function update(Todo $todo, array $data): Todo
    {
        $todo->update($data);

        return $todo->refresh();
    }

    public function delete(Todo $todo): bool
    {
        return (bool) $todo->delete();
    }

    public function createMany(int $userId, array $rows): int
    {
        return DB::transaction(function () use ($userId, $rows): int {
            foreach ($rows as $row) {
                Todo::query()->create(array_merge($row, ['user_id' => $userId]));
            }

            return count($rows);
        });
    }
}
