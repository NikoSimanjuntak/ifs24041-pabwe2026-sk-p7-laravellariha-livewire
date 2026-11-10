<?php

namespace Tests\Unit\Enums;

use App\Features\Todo\Enums\TodoStatus;
use PHPUnit\Framework\TestCase;

class TodoStatusTest extends TestCase
{
    public function test_labels(): void
    {
        $this->assertSame('Menunggu', TodoStatus::Pending->label());
        $this->assertSame('Dikerjakan', TodoStatus::InProgress->label());
        $this->assertSame('Selesai', TodoStatus::Completed->label());
    }

    public function test_badge_classes(): void
    {
        $this->assertStringContainsString('yellow', TodoStatus::Pending->badgeClass());
        $this->assertStringContainsString('blue', TodoStatus::InProgress->badgeClass());
        $this->assertStringContainsString('green', TodoStatus::Completed->badgeClass());
    }

    public function test_values(): void
    {
        $this->assertSame(['pending', 'in_progress', 'completed'], TodoStatus::values());
    }
}
