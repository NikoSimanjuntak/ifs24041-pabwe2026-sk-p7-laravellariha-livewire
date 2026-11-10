<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedTodoItem;
use App\Features\Todo\Enums\TodoStatus;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiTodoGeneratorService implements AiClientInterface
{
    public function generate(string $prompt, int $count = 5): array
    {
        $key = config('openai.key');

        if (blank($key)) {
            throw new RuntimeException('OPENAI_KEY belum dikonfigurasi.');
        }

        try {
            $response = Http::withToken((string) $key)
                ->acceptJson()
                ->timeout((int) config('openai.timeout'))
                ->post(rtrim((string) config('openai.endpoint'), '/').'/chat/completions', [
                    'model' => config('openai.model'),
                    'temperature' => 0.4,
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt($count)],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException('Tidak dapat terhubung ke layanan AI.');
        }

        if ($response->failed()) {
            throw new RuntimeException('Layanan AI mengembalikan kesalahan (HTTP '.$response->status().').');
        }

        $content = (string) data_get($response->json(), 'choices.0.message.content', '');

        return $this->parse($content, $count);
    }

    private function systemPrompt(int $count): string
    {
        $statuses = implode(', ', TodoStatus::values());

        return "Kamu adalah asisten perencana tugas. Buat maksimal {$count} item todolist dalam bahasa Indonesia "
            .'berdasarkan permintaan pengguna. Balas HANYA dengan JSON valid tanpa teks lain, dengan format: '
            .'{"todos":[{"title":"...","description":"... (boleh Markdown)","status":"'.$statuses.'","due_date":"YYYY-MM-DD atau null"}]}.';
    }

    /**
     * @return list<GeneratedTodoItem>
     */
    private function parse(string $content, int $count): array
    {
        $json = trim((string) preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($content)));
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Respons AI tidak dapat dibaca sebagai JSON.');
        }

        $rows = $decoded['todos'] ?? $decoded['items'] ?? $decoded;

        if (! is_array($rows)) {
            throw new RuntimeException('Format respons AI tidak sesuai.');
        }

        $items = [];

        foreach (array_slice(array_values($rows), 0, $count) as $row) {
            $item = is_array($row) ? GeneratedTodoItem::fromArray($row) : null;

            if ($item !== null) {
                $items[] = $item;
            }
        }

        return $items;
    }
}
