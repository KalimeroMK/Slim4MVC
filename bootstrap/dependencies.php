<?php

declare(strict_types=1);

// config/dependencies.php
use App\Modules\Auth\Application\Actions\Auth\LoginAction;
use App\Modules\Auth\Application\Actions\Auth\PasswordRecoveryAction;
use App\Modules\Auth\Application\Actions\Auth\RegisterAction;
use App\Modules\Auth\Application\Actions\Auth\ResetPasswordAction;
use App\Modules\Auth\Application\Actions\Auth\WebLoginAction;
use App\Modules\Auth\Application\Interfaces\Auth\LoginActionInterface;
use App\Modules\Auth\Application\Interfaces\Auth\PasswordRecoveryActionInterface;
use App\Modules\Auth\Application\Interfaces\Auth\RegisterActionInterface;
use App\Modules\Auth\Application\Interfaces\Auth\ResetPasswordActionInterface;
use App\Modules\Auth\Application\Interfaces\Auth\WebLoginActionInterface;
use App\Modules\Core\Infrastructure\Events\Dispatcher;
use App\Modules\Core\Infrastructure\Queue\FileQueue;
use App\Modules\Core\Infrastructure\Queue\Queue;
use App\Modules\Permission\Application\Actions\CreatePermissionAction;
use App\Modules\Permission\Application\Actions\UpdatePermissionAction;
use App\Modules\Permission\Application\Interfaces\CreatePermissionActionInterface;
use App\Modules\Permission\Application\Interfaces\UpdatePermissionActionInterface;
use App\Modules\Permission\Infrastructure\Repositories\PermissionRepository;
use App\Modules\Role\Application\Actions\CreateRoleAction;
use App\Modules\Role\Application\Actions\UpdateRoleAction;
use App\Modules\Role\Application\Interfaces\CreateRoleActionInterface;
use App\Modules\Role\Application\Interfaces\UpdateRoleActionInterface;
use App\Modules\Role\Infrastructure\Repositories\RoleRepository;
use App\Modules\User\Application\Actions\CreateUserAction;
use App\Modules\User\Application\Actions\UpdateUserAction;
use App\Modules\User\Application\Interfaces\CreateUserActionInterface;
use App\Modules\User\Application\Interfaces\UpdateUserActionInterface;
use App\Modules\User\Infrastructure\Repositories\UserRepository;

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
    }),    UpdateItemActionInterface::class => \DI\autowire(UpdateItemAction::class),];
