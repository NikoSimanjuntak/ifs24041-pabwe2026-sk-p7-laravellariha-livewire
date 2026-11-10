<?php

namespace App\Features\Ai\Contracts;

use App\Features\Ai\DTO\GeneratedTodoItem;

interface AiClientInterface
{
    /**
     * Membuat daftar todo dari permintaan pengguna.
     *
     * @return list<GeneratedTodoItem>
     */
    public function generate(string $prompt, int $count = 5): array;
}
