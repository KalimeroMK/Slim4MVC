<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Modules\Role\Infrastructure\Models\PermissionRole;
use App\Modules\User\Infrastructure\Models\RoleUser;
use PHPUnit\Framework\TestCase;

final class AdditionalModelsTest extends TestCase
{
    public function test_permission_role_model_exists(): void
    {
        $this->assertTrue(class_exists(PermissionRole::class));
    }

    public function test_role_user_model_exists(): void
    {
        $this->assertTrue(class_exists(RoleUser::class));
    }
}
