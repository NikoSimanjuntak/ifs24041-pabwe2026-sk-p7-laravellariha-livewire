<?php

namespace Tests\Unit\Providers;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\Services\OpenAiTodoGeneratorService;
use App\Features\Todo\Contracts\TodoRepositoryInterface;
use App\Features\Todo\Models\Todo;
use App\Features\Todo\Policies\TodoPolicy;
use App\Features\Todo\Repositories\TodoRepository;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_bindings(): void
    {
        $this->assertInstanceOf(TodoRepository::class, $this->app->make(TodoRepositoryInterface::class));
        $this->assertInstanceOf(OpenAiTodoGeneratorService::class, $this->app->make(AiClientInterface::class));
    }

    public function test_policy_is_registered(): void
    {
        $this->assertInstanceOf(TodoPolicy::class, Gate::getPolicyFor(Todo::class));
    }

    public function test_rate_limiter_is_configured(): void
    {
        $limiter = RateLimiter::limiter('1000-per-second');
        $this->assertNotNull($limiter);

        $guest = $limiter(Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '10.0.0.1']));
        $this->assertInstanceOf(Limit::class, $guest);
        $this->assertSame(1000, $guest->maxAttempts);
        $this->assertSame(1, $guest->decaySeconds);

        $request = Request::create('/');
        $request->setUserResolver(fn () => (new User)->forceFill(['id' => 7]));
        $this->assertSame(7, $limiter($request)->key);
    }

    public function test_force_https_when_enabled(): void
    {
        config(['app.force_https' => true]);

        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', URL::to('/contoh'));
    }
}
