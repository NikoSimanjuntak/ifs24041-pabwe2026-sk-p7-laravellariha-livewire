<?php

namespace App\Features\Todo\Contracts;

use App\Features\Todo\Models\Todo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TodoRepositoryInterface
{
    public function paginateForUser(int $userId, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator;

    /**
     * @return Collection<int, Todo>
     */
    public function allForUser(int $userId): Collection;

    public function find(int $id): Todo;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Todo;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Todo $todo, array $data): Todo;

    public function delete(Todo $todo): bool;

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function createMany(int $userId, array $rows): int;
}
