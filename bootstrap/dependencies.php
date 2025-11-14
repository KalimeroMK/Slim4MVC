<?php

declare(strict_types=1);

// config/dependencies.php
use App\Actions\Auth\LoginAction;
use App\Actions\Auth\PasswordRecoveryAction;
use App\Actions\Auth\RegisterAction;
use App\Actions\Auth\ResetPasswordAction;
use App\Actions\Auth\WebLoginAction;
use App\Actions\Permission\CreatePermissionAction;
use App\Actions\Permission\UpdatePermissionAction;
use App\Actions\Role\CreateRoleAction;
use App\Actions\Role\UpdateRoleAction;
use App\Actions\User\CreateUserAction;
use App\Actions\User\UpdateUserAction;
use App\Events\Dispatcher;
use App\Interface\Auth\LoginActionInterface;
use App\Interface\Auth\PasswordRecoveryActionInterface;
use App\Interface\Auth\RegisterActionInterface;
use App\Interface\Auth\ResetPasswordActionInterface;
use App\Interface\Auth\WebLoginActionInterface;
use App\Interface\Permission\CreatePermissionActionInterface;
use App\Interface\Permission\UpdatePermissionActionInterface;
use App\Interface\Role\CreateRoleActionInterface;
use App\Interface\Role\UpdateRoleActionInterface;
use App\Interface\User\CreateUserActionInterface;
use App\Interface\User\UpdateUserActionInterface;
use App\Queue\FileQueue;
use App\Queue\Queue;
use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;

return [
    RegisterActionInterface::class => \DI\autowire(RegisterAction::class),
    LoginActionInterface::class => \DI\autowire(LoginAction::class),
    PasswordRecoveryActionInterface::class => \DI\autowire(PasswordRecoveryAction::class),
    ResetPasswordActionInterface::class => \DI\autowire(ResetPasswordAction::class),
    WebLoginActionInterface::class => \DI\autowire(WebLoginAction::class),
    CreateRoleActionInterface::class => \DI\autowire(CreateRoleAction::class),
    UpdateRoleActionInterface::class => \DI\autowire(UpdateRoleAction::class),
    CreatePermissionActionInterface::class => \DI\autowire(CreatePermissionAction::class),
    UpdatePermissionActionInterface::class => \DI\autowire(UpdatePermissionAction::class),
    CreateUserActionInterface::class => \DI\autowire(CreateUserAction::class),
    UpdateUserActionInterface::class => \DI\autowire(UpdateUserAction::class),
    // Repositories
    UserRepository::class => \DI\autowire(UserRepository::class),
    RoleRepository::class => \DI\autowire(RoleRepository::class),
    PermissionRepository::class => \DI\autowire(PermissionRepository::class),
    // Event system
    Dispatcher::class => \DI\autowire(Dispatcher::class),
    // Queue system
    Queue::class => \DI\factory(function (): FileQueue {
        return new FileQueue();
    }),
];
