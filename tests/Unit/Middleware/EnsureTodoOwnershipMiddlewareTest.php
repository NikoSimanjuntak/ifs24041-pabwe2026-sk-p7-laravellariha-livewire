<?php

namespace Tests\Unit\Middleware;

use App\Features\Todo\Models\Todo;
use App\Http\Middleware\EnsureTodoOwnership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureTodoOwnershipMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function makeRequest(?User $user, mixed $todoParam): Request
    {
        $request = Request::create('/todos/1');
        $request->setUserResolver(fn () => $user);
        $request->setRouteResolver(fn () => new class($todoParam)
        {
            public function __construct(private mixed $value) {}

            public function parameter(string $name, mixed $default = null): mixed
            {
                return $this->value;
            }
        });

        return $request;
    }

    private function dispatchRequest(Request $request): Response
    {
        return (new EnsureTodoOwnership)->handle($request, fn () => new Response('ok'));
    }

    public function test_owner_with_bound_model_passes(): void
    {
        $todo = Todo::factory()->create();

        $this->assertSame('ok', $this->dispatchRequest($this->makeRequest($todo->user, $todo))->getContent());
    }

    public function test_owner_with_raw_id_passes(): void
    {
        $todo = Todo::factory()->create();

        $this->assertSame('ok', $this->dispatchRequest($this->makeRequest($todo->user, $todo->id))->getContent());
    }

    public function test_other_user_is_forbidden(): void
    {
        $todo = Todo::factory()->create();

        try {
            $this->dispatchRequest($this->makeRequest(User::factory()->create(), $todo));
            $this->fail('Seharusnya melempar HttpException.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_guest_is_forbidden(): void
    {
        $todo = Todo::factory()->create();

        try {
            $this->dispatchRequest($this->makeRequest(null, $todo));
            $this->fail('Seharusnya melempar HttpException.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_missing_todo_returns_404(): void
    {
        try {
            $this->dispatchRequest($this->makeRequest(User::factory()->create(), 9999));
            $this->fail('Seharusnya melempar HttpException.');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
    }
}
