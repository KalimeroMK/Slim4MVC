# Slim 4 MVC Starter Kit with Eloquent ORM

This project is created with the Slim 4 framework, uses Docker for configuration and development, and follows the MVC pattern. It includes support for migrations using Illuminate Database.

## Setting Up the Project

1. Clone the repository to your local machine:
    ```bash
    git clone https://github.com/KalimeroMK/Slim4MVC
    ```

2. Ensure you have Docker and Docker Compose installed on your system. For installation, check [Docker's Documentation](https://docs.docker.com/get-docker/).

3. Start the Docker containers with the command:
    ```bash
    docker-compose up -d
    ```

4. This will start the Docker container for the application and the database. The project will be available at [http://localhost:81](http://localhost:81).

## Creating model and migration

1. **Creating a New Model:**
   To create a new migration, use the following command:
    ```bash
    php slim make:model ModelName
    ```
   This will create a new model file in `app/Models/ModelName.php`.

2. **Creating a New Migration:**

   ```bash
    php slim make:model ModelName -m
    ```
   This will create a new migration file. You can edit it and add new migrations for your database.

3. **Running Migrations:**
   To run database migrations, use the same command:
    ```bash
   php migrate.php              

    ```
   ```
      php migrate.php rollback  //for rollback
      php migrate.php refresh  //for refresh 
   ```
   If the migration has already been run, the system will skip it without an error.

4. **Creating controllers:**
   To create a controller, you can use:
    ```bash
     php slim make:controller ControllerName
    ```

5. **List routes:**
   To list all routes, you can use:
    ```bash
     php slim list-routes
    ```

### Project Structure

The project has the following structure:

```
│── app/
│   ├── Actions/                  # Business logic classes
│   │   ├── Auth/
│   │   │   ├── LoginAction.php
│   │   │   ├── RegisterAction.php
│   │   │   └── PasswordRecoveryAction.php
│   │   └── ...other domains
│   ├── DTO/                      # Data Transfer Objects
│   │   ├── Auth/
│   │   │   ├── LoginDTO.php
│   │   │   ├── RegisterDTO.php
│   │   │   └── PasswordRecoveryDTO.php
│   │   └── ...other domains
│   ├── Http/
│   │   ├── Controllers/          # Thin controllers
│   │   │   ├── Api/
│   │   │   │   └── AuthController.php
│   │   │   ├── Web/
│   │   │   │   └── AuthController.php
│   │   │   └── ...other controllers
│   │   ├── Middleware/
│   │   └── Requests/             # Form request validation
│   │       ├── Auth/
│   │       │   ├── LoginRequest.php
│   │       │   └── RegisterRequest.php
│   │       └── ...other requests
│   ├── Interfaces/               # Contracts
│   │   ├── Auth/
│   │   │   ├── LoginActionInterface.php
│   │   │   └── RegisterActionInterface.php
│   │   └── ...other interfaces
│   ├── Models/                   # Eloquent models
│   ├── Providers/                # Service providers
│   ├── Support/
│   │   ├── Helpers.php
│   │   └── ...other utilities
│   ├── View/                     # Blade integration
│   │   ├── Blade.php
│   │   ├── BladeFactory.php
│   │   ├── BladeAssetsHelper.php
│   │   └── BladeViewHelper.php
│   └── config.php
│── bootstrap/
│   ├── app.php
│   ├── database.php
│   ├── ...other utilities
│── database/
│   ├── migrations/
│   └── seeders/
│── public/
│   └── index.php
│── resources/
│   ├── views/                    # Blade templates
│   │   ├── auth/
│   │   │   ├── login.blade.php
│   │   │   └── register.blade.php
│   │   └── ...other views
│   └── assets/                   # Frontend assets
│── routes/
│   ├── api.php
│   ├── web.php
│   └── console.php
│── storage/
│   ├── cache/
│   ├── logs/
│   └── sessions/
│── tests/                        # Test suites
│   ├── Feature/
│   └── Unit/
│── .env
│── composer.json

## Key Features

- MVC Architecture with Slim 4
- Docker Configuration for Development
- Database Integration with Eloquent ORM
- Migration System
- Blade Templating Engine
- CSRF Protection
- Form Request Validation (Laravel-style)
  - Automatic validation through FormRequest base class
  - ValidationException handling
  - Support for Laravel validation rules
  - DTO pattern with fromRequest constructors
- CLI Commands for Development
  - Model & Migration Generation
  - Controller Generation
  - Route Listing
- API Ready with Structured Response Handling
- Authentication System
- Middleware System
- Comprehensive Directory Structure

## Authorization System

The project includes a comprehensive authorization system with both middleware and policies.

### Using Role & Permission Middleware

```php
// routes/web.php or routes/api.php

// Single role check
$app->get('/admin/dashboard', [DashboardController::class, 'index'])
    ->add(new CheckRoleMiddleware())
    ->setArgument('roles', 'admin');

// Multiple roles check (user needs only one of these roles)
$app->get('/reports', [ReportController::class, 'index'])
    ->add(new CheckRoleMiddleware())
    ->setArgument('roles', ['admin', 'manager']);

// Permission check
$app->post('/users', [UserController::class, 'store'])
    ->add(new CheckPermissionMiddleware())
    ->setArgument('permissions', 'create-users');

// Multiple permissions check
$app->put('/posts/{id}', [PostController::class, 'update'])
    ->add(new CheckPermissionMiddleware())
    ->setArgument('permissions', ['edit-posts', 'publish-posts']);
```

### Using Policies

1. **Create a Policy:**

```php
// app/Policies/PostPolicy.php
class PostPolicy extends Policy
{
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->hasPermission('edit-posts');
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->hasRole('admin') || 
            ($user->id === $post->user_id && $user->hasPermission('delete-posts'));
    }
}
```

2. **Use in Controllers:**

```php
class PostController extends Controller
{
    public function update(Request $request, Response $response, int $id): Response
    {
        $post = Post::find($id);
        
        if (!$this->authorize('update', $post)) {
            return $this->respondUnauthorized();
        }

        // Continue with update logic...
    }

    public function delete(Request $request, Response $response, int $id): Response
    {
        $post = Post::find($id);
        
        if (!$this->authorize('delete', $post)) {
            return $this->respondUnauthorized();
        }

        // Continue with delete logic...
    }
}
```

### Super Admin Override

The base Policy class includes a `before` method that automatically allows all actions for users with the 'super-admin' role:

```php
// app/Policies/Policy.php
abstract class Policy
{
    public function before(User $user): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null; // fall through to specific policy method
    }
}
```

### Best Practices

1. Use middleware for:
   - Route-level authorization
   - Simple role/permission checks
   - Early request filtering

2. Use policies for:
   - Resource-level authorization
   - Complex permission logic
   - Business rules
   - Model-specific authorization

3. Combine both when needed:
   ```php
   // Route with middleware for basic access
   $app->group('/admin/posts', function (Group $group) {
       $group->get('', [PostController::class, 'index']);
       $group->post('', [PostController::class, 'store']);
   })->add(new CheckRoleMiddleware())
     ->setArgument('roles', ['admin', 'editor']);

   // Then use policies in the controller for specific actions
   public function store(Request $request): Response
   {
       if (!$this->authorize('create', Post::class)) {
           return $this->respondUnauthorized();
       }
       // ... store logic
   }
   ```

## License

This project is licensed under the MIT license. See `LICENSE` for more information.
