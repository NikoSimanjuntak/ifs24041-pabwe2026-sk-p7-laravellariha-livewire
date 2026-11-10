<?php

namespace App\Features\Admin\Services;

use App\Features\Todo\Contracts\TodoRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminUserService
{
    public function __construct(private readonly TodoRepositoryInterface $todos) {}

    public function paginateUsers(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return User::query()
            ->withCount('todos')
            ->when($search, function (Builder $query, string $term): void {
                $query->where(function (Builder $inner) use ($term): void {
                    $inner->where('name', 'like', "%{$term}%")
                        ->orWhere('username', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function paginateTodos(User $user, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->todos->paginateForUser($user->id, $search, $status, $perPage);
    }
}
