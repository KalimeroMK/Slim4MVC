<?php

declare(strict_types=1);

namespace Tests\Unit\Resources;

use App\Modules\Core\Infrastructure\Http\Resources\Resource;
use PHPUnit\Framework\TestCase;

class ResourceTest extends TestCase
{
    public function test_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(Resource::class));
    }

    public function test_resource_has_make_method(): void
    {
        $this->assertTrue(method_exists(Resource::class, 'make'));
    }

    public function test_resource_has_collection_method(): void
    {
        $this->assertTrue(method_exists(Resource::class, 'collection'));
    }

    public function test_resource_has_when_method(): void
    {
        $this->assertTrue(method_exists(Resource::class, 'when'));
    }
}
