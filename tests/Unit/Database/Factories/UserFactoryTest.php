<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Modules\User\Infrastructure\Database\Factories\UserFactory;
use App\Modules\User\Infrastructure\Models\User;
use Tests\TestCase;

class UserFactoryTest extends TestCase
{
    public function test_factory_creates_user_with_default_attributes(): void
    {
        $factory = new UserFactory();
        $user = $factory->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->id);
        $this->assertNotNull($user->name);
        $this->assertNotNull($user->email);
        $this->assertNotNull($user->password);
        $this->assertTrue(password_verify('password', $user->password));
    }

    public function test_factory_creates_user_with_custom_attributes(): void
    {
        $factory = new UserFactory();
        $user = $factory->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
    }

    public function test_factory_unverified_state(): void
    {
        $factory = new UserFactory();
        $user = $factory->unverified()->create();

        $this->assertNull($user->email_verified_at);
    }

    public function test_factory_with_custom_password(): void
    {
        $factory = new UserFactory();
        $user = $factory->withPassword('custom-password')->create();

        $this->assertTrue(password_verify('custom-password', $user->password));
    }

    public function test_factory_with_role(): void
    {
        $role = $this->createRole(['name' => 'admin']);

        $factory = new UserFactory();
        $user = $factory->withRole($role->id);

        $this->assertNotNull($user);
        $user->load('roles');
        $this->assertTrue($user->roles->contains('id', $role->id));
    }

    public function test_factory_with_role_by_name(): void
    {
        $role = $this->createRole(['name' => 'manager']);

        $factory = new UserFactory();
        $user = $factory->withRole('manager');

        $this->assertNotNull($user);
        $user->load('roles');
        $this->assertTrue($user->roles->contains('id', $role->id));
    }

    public function test_factory_creates_many_users(): void
    {
        $factory = new UserFactory();
        $users = $factory->createMany(5);

        $this->assertCount(5, $users);
        foreach ($users as $user) {
            $this->assertInstanceOf(User::class, $user);
        }
    }
}
