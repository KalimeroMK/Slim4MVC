<?php

declare(strict_types=1);

// config/dependencies.php
use App\Actions\Auth\LoginAction;
use App\Actions\Auth\PasswordRecoveryAction;
use App\Actions\Auth\RegisterAction;
use App\Actions\Auth\ResetPasswordAction;
use App\Actions\Auth\WebLoginAction;
use App\DTO\Permission\CreatePermissionDTO;
use App\DTO\Role\CreateRoleDTO;
use App\DTO\Role\UpdateRoleDTO;
use App\Interface\Auth\LoginActionInterface;
use App\Interface\Auth\PasswordRecoveryActionInterface;
use App\Interface\Auth\RegisterActionInterface;
use App\Interface\Auth\ResetPasswordActionInterface;
use App\Interface\Auth\WebLoginActionInterface;
use App\Interface\Permission\CreatePermissionActionInterface;
use App\Interface\Role\CreateRoleActionInterface;
use App\Interface\Role\UpdateRoleActionInterface;

return [
    // Bind interfaces to implementations
    RegisterActionInterface::class => \DI\autowire(RegisterAction::class),
    LoginActionInterface::class => \DI\autowire(LoginAction::class),
    PasswordRecoveryActionInterface::class => \DI\autowire(PasswordRecoveryAction::class),
    ResetPasswordActionInterface::class => \DI\autowire(ResetPasswordAction::class),
    WebLoginActionInterface::class => \DI\autowire(WebLoginAction::class),
    CreateRoleActionInterface::class => \DI\autowire((CreateRoleDTO::class)),
    UpdateRoleActionInterface::class => \DI\autowire((UpdateRoleDTO::class)),
    CreatePermissionActionInterface::class => \DI\autowire((CreatePermissionDTO::class)),

];
