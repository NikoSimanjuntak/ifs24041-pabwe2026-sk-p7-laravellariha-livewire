<?php

namespace Database\Seeders;

use App\Features\Todo\Enums\TodoStatus;
use App\Features\Todo\Models\Todo;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->create([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'admin',
            'role' => User::ROLE_ADMIN,
        ]);

        $member = User::query()->create([
            'name' => 'Member Demo',
            'username' => 'member',
            'email' => 'member@example.com',
            'password' => 'member',
            'role' => User::ROLE_MEMBER,
        ]);

        $samples = [
            ['Belajar Laravel 13', "## Target\n- Routing\n- Eloquent\n- **Livewire 4**", TodoStatus::InProgress],
            ['Kerjakan latihan praktikum', 'Selesaikan *Todolist* Feature-First.', TodoStatus::Pending],
            ['Setup environment', 'PHP, Composer, Node.js, MySQL.', TodoStatus::Completed],
        ];

        foreach ($samples as [$title, $description, $status]) {
            Todo::query()->create([
                'user_id' => $member->id,
                'title' => $title,
                'description' => $description,
                'status' => $status,
                'due_date' => now()->addDays(3)->toDateString(),
            ]);
        }
    }
}
