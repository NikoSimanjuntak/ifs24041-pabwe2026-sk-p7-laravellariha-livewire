<?php

namespace App\Features\Todo\Services;

use App\Features\Todo\Contracts\TodoRepositoryInterface;
use App\Features\Todo\Enums\TodoStatus;
use App\Features\Todo\Models\Todo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TodoService
{
    public function __construct(private readonly TodoRepositoryInterface $todos) {}

    public function paginate(int $userId, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->todos->paginateForUser($userId, $search, $status, $perPage);
    }

    /**
     * @return Collection<int, Todo>
     */
    public function all(int $userId): Collection
    {
        return $this->todos->allForUser($userId);
    }

    public function find(int $id): Todo
    {
        return $this->todos->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $userId, array $data): Todo
    {
        return $this->todos->create(array_merge($data, ['user_id' => $userId]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Todo $todo, array $data): Todo
    {
        return $this->todos->update($todo, $data);
    }

    public function delete(Todo $todo): void
    {
        $this->deleteCoverFile($todo);
        $this->todos->delete($todo);
    }

    public function toggleComplete(Todo $todo): Todo
    {
        $next = $todo->isCompleted() ? TodoStatus::Pending : TodoStatus::Completed;

        return $this->todos->update($todo, ['status' => $next]);
    }

    public function storeCover(Todo $todo, UploadedFile $file): Todo
    {
        $this->deleteCoverFile($todo);
        $path = $file->store('covers', 'public');

        return $this->todos->update($todo, ['cover_image_path' => $path]);
    }

    public function removeCover(Todo $todo): Todo
    {
        $this->deleteCoverFile($todo);

        return $this->todos->update($todo, ['cover_image_path' => null]);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function createMany(int $userId, array $items): int
    {
        return $this->todos->createMany($userId, $items);
    }

    private function deleteCoverFile(Todo $todo): void
    {
        if ($todo->cover_image_path !== null) {
            Storage::disk('public')->delete($todo->cover_image_path);
        }
    }
}
