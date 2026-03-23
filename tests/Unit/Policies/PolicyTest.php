<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Modules\Core\Infrastructure\Policies\Policy;
use PHPUnit\Framework\TestCase;

class PolicyTest extends TestCase
{
    public function test_policy_class_exists(): void
    {
        $this->assertTrue(class_exists(Policy::class));
    }

    public function test_policy_has_before_method(): void
    {
        $this->assertTrue(method_exists(Policy::class, 'before'));
    }
}
