<?php

namespace App\Http\Middleware;

use App\Features\Todo\Models\Todo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTodoOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $todo = $request->route('todo');

        if (! $todo instanceof Todo) {
            $todo = Todo::query()->find($todo);
        }

        abort_if($todo === null, 404);
        abort_if($request->user() === null || $request->user()->id !== $todo->user_id, 403, 'Todo ini bukan milik Anda.');

        return $next($request);
    }
}
