<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Modules\User\Infrastructure\Database\Factories\UserFactory;
use App\Modules\User\Infrastructure\Models\User;
use Tests\TestCase;

final class UserFactoryTest extends TestCase
{
    public function test_factory_creates_user_with_default_attributes(): void
    {
        $userFactory = new UserFactory();
        $model = $userFactory->create();

        $this->assertInstanceOf(User::class, $model);
        $this->assertNotNull($model->id);
        $this->assertNotNull($model->name);
        $this->assertNotNull($model->email);
        $this->assertNotNull($model->password);
        $this->assertTrue(password_verify('password', $model->password));
    }

    public function test_factory_creates_user_with_custom_attributes(): void
    {
        $userFactory = new UserFactory();
        $model = $userFactory->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertEquals('John Doe', $model->name);
        $this->assertEquals('john@example.com', $model->email);
    }

    public function test_factory_unverified_state(): void
    {
        $userFactory = new UserFactory();
        $model = $userFactory->unverified()->create();

        $this->assertNull($model->email_verified_at);
    }

    public function test_factory_with_custom_password(): void
    {
        $userFactory = new UserFactory();
        $model = $userFactory->withPassword('custom-password')->create();

        $this->assertTrue(password_verify('custom-password', $model->password));
    }

    public function test_factory_with_role(): void
    {
        $role = $this->createRole(['name' => 'admin']);

        $userFactory = new UserFactory();
        $user = $userFactory->withRole($role->id);

        $this->assertNotNull($user);
        $user->load('roles');
        $this->assertTrue($user->roles->contains('id', $role->id));
    }

    public function test_factory_with_role_by_name(): void
    {
        $role = $this->createRole(['name' => 'manager']);

        $userFactory = new UserFactory();
        $user = $userFactory->withRole('manager');

        $this->assertNotNull($user);
        $user->load('roles');
        $this->assertTrue($user->roles->contains('id', $role->id));
    }

    public function test_factory_creates_many_users(): void
    {
        $userFactory = new UserFactory();
        $users = $userFactory->createMany(5);

        $this->assertCount(5, $users);
        $this->assertContainsOnlyInstancesOf(User::class, $users);
    }
}
