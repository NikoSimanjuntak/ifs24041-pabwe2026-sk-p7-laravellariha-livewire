<?php

namespace Tests\Unit\Services;

use App\Features\Ai\DTO\GeneratedTodoItem;
use App\Features\Ai\Services\OpenAiTodoGeneratorService;
use App\Features\Todo\Enums\TodoStatus;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiTodoGeneratorServiceTest extends TestCase
{
    private function fakeContent(string $content, int $status = 200): void
    {
        Http::fake([
            '*' => Http::response(['choices' => [['message' => ['content' => $content]]]], $status),
        ]);
    }

    public function test_generates_items_and_sends_expected_request(): void
    {
        $this->fakeContent(json_encode(['todos' => [
            ['title' => 'Belajar', 'description' => 'Desk', 'status' => 'in_progress', 'due_date' => '2026-12-01'],
            ['title' => '', 'description' => 'tanpa judul'],
            'bukan-array',
            ['title' => 'Kedua'],
        ]]));

        $items = (new OpenAiTodoGeneratorService)->generate('rencana belajar', 5);

        $this->assertCount(2, $items);
        $this->assertInstanceOf(GeneratedTodoItem::class, $items[0]);
        $this->assertSame(TodoStatus::InProgress, $items[0]->status);
        $this->assertSame('Kedua', $items[1]->title);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.openai.test/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $request['messages'][1]['content'] === 'rencana belajar'
                && $request['model'] === config('openai.model');
        });
    }

    public function test_respects_count_limit(): void
    {
        $this->fakeContent(json_encode(['todos' => [['title' => 'A'], ['title' => 'B'], ['title' => 'C']]]));

        $this->assertCount(2, (new OpenAiTodoGeneratorService)->generate('x', 2));
    }

    public function test_accepts_code_fenced_json_and_alternative_keys(): void
    {
        $this->fakeContent("```json\n".json_encode(['items' => [['title' => 'Dari fence']]])."\n```");

        $this->assertSame('Dari fence', (new OpenAiTodoGeneratorService)->generate('x')[0]->title);
    }

    public function test_accepts_plain_list(): void
    {
        $this->fakeContent(json_encode([['title' => 'Langsung list']]));

        $this->assertSame('Langsung list', (new OpenAiTodoGeneratorService)->generate('x')[0]->title);
    }

    public function test_throws_when_key_missing(): void
    {
        config(['openai.key' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OPENAI_KEY');

        (new OpenAiTodoGeneratorService)->generate('x');
    }

    public function test_throws_on_http_error(): void
    {
        $this->fakeContent('{}', 500);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 500');

        (new OpenAiTodoGeneratorService)->generate('x');
    }

    public function test_throws_on_connection_error(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('terhubung');

        (new OpenAiTodoGeneratorService)->generate('x');
    }

    public function test_throws_on_non_json_content(): void
    {
        $this->fakeContent('maaf saya tidak bisa');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JSON');

        (new OpenAiTodoGeneratorService)->generate('x');
    }

    public function test_throws_on_unexpected_structure(): void
    {
        $this->fakeContent(json_encode(['todos' => 'bukan array']));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Format');

        (new OpenAiTodoGeneratorService)->generate('x');
    }
}
