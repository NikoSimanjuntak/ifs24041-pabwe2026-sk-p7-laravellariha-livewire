<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureRoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function makeRequest(?User $user): Request
    {
        $request = Request::create('/x');
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    public function test_allows_matching_role(): void
    {
        $admin = User::factory()->admin()->make();

        $response = (new EnsureRole)->handle($this->makeRequest($admin), fn () => new Response('ok'), 'admin', 'member');

        $this->assertSame('ok', $response->getContent());
    }

    public function test_denies_other_role(): void
    {
        $this->expectException(HttpException::class);

        (new EnsureRole)->handle($this->makeRequest(User::factory()->make()), fn () => new Response('ok'), 'admin');
    }

    public function test_denies_guest(): void
    {
        try {
            (new EnsureRole)->handle($this->makeRequest(null), fn () => new Response('ok'), 'admin');
            $this->fail('Seharusnya melempar HttpException.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }
}
