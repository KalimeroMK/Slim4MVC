# Slim 4 MVC Starter Kit

A modern, production-ready starter kit for building web applications with Slim Framework 4, featuring Eloquent ORM, Blade templating, comprehensive authentication, and a robust testing suite.

## 🚀 Features

- **MVC Architecture** - Clean separation of concerns with Slim 4
- **Eloquent ORM** - Laravel's powerful database toolkit
- **Blade Templating** - Laravel's elegant templating engine
- **Authentication System** - JWT-based API auth and session-based web auth
- **Authorization** - Role and permission-based access control with middleware and policies
- **Form Request Validation** - Laravel-style validation with automatic error handling
- **Rate Limiting** - Built-in protection against brute force attacks
- **CORS Support** - Configurable CORS middleware for API endpoints
- **Error Logging** - PSR-3 compatible logging with Monolog
- **API Resources** - Consistent API response formatting with Resource classes
- **Consistent API Responses** - Standardized JSON responses with enums and helper methods
- **Repository Pattern** - Clean data access layer abstraction for better testability and maintainability
- **Exception Handling** - Custom exception classes with centralized exception handling middleware
- **Testing Suite** - Comprehensive test coverage with PHPUnit (96 tests, 223 assertions)
- **CLI Commands** - Artisan-like commands for scaffolding (modules, models, controllers, requests)
- **Modular Architecture** - Feature-based module organization for better scalability
- **Automatic Dependency Registration** - Dependencies automatically registered when creating modules
- **Docker Ready** - Complete Docker setup for development

## 📋 Requirements

- PHP >= 8.3
- Composer
- Docker & Docker Compose (for development)
- MySQL/MariaDB (or SQLite for testing)

## 🛠️ Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/KalimeroMK/Slim4MVC
   cd Slim4MVC
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   ```
   
   Edit `.env` and configure:
   - Database credentials
   - JWT_SECRET (generate a strong secret key)
   - Mail settings
   - CORS origins (if needed)

4. **Start Docker containers:**
   ```bash
   docker-compose up -d
   ```

5. **Run migrations:**
   ```bash
   php run_migrations.php
   ```

6. **Seed database (optional):**
   ```bash
   php slim seed:database
   ```

7. **Setup storage permissions:**
   ```bash
   # Using PHP script (recommended)
   php setup-storage.php
   
   # Or using bash script
   ./setup-storage.sh
   
   # Or manually
   chmod -R 775 storage
   chown -R www-data:www-data storage  # Adjust user/group as needed
   ```

The application will be available at [http://localhost:81](http://localhost:81)

## 📁 Project Structure

The project uses a **modular architecture** where each feature is organized as an independent module:

```
├── app/
│   ├── Console/               # CLI Commands
│   │   └── Commands/         # Console commands (make:module, make:request, etc.)
│   ├── Modules/              # Feature modules
│   │   ├── Core/             # Core module (base classes, middleware, support)
│   │   │   ├── Application/
│   │   │   │   ├── Actions/  # Core actions (Auth actions)
│   │   │   │   ├── DTOs/     # Core DTOs
│   │   │   │   ├── Enums/    # Enums (HttpStatusCode, ApiResponseStatus)
│   │   │   │   └── Interfaces/
│   │   │   └── Infrastructure/
│   │   │       ├── Events/   # Event system
│   │   │       ├── Exceptions/ # Custom exceptions
│   │   │       ├── Http/
│   │   │       │   ├── Controllers/ # Base controllers
│   │   │       │   ├── Middleware/  # Middleware
│   │   │       │   ├── Requests/    # Base FormRequest
│   │   │       │   └── Resources/   # Base Resource
│   │   │       ├── Jobs/     # Queue jobs
│   │   │       ├── Policies/ # Base Policy
│   │   │       ├── Providers/ # Service providers
│   │   │       ├── Queue/    # Queue system
│   │   │       ├── Repositories/ # Base repositories
│   │   │       ├── Support/  # Helper classes (Auth, Logger, Mailer)
│   │   │       └── View/     # Blade integration
│   │   ├── Auth/             # Authentication module
│   │   │   ├── Application/
│   │   │   │   ├── Actions/Auth/  # Login, Register, PasswordRecovery, etc.
│   │   │   │   ├── DTOs/Auth/    # Auth DTOs
│   │   │   │   └── Interfaces/Auth/
│   │   │   └── Infrastructure/
│   │   │       ├── Http/
│   │   │       │   ├── Controllers/
│   │   │       │   │   ├── Api/    # API AuthController (JWT)
│   │   │       │   │   └── Web/    # Web AuthController (Session)
│   │   │       │   └── Requests/Auth/
│   │   │       ├── Providers/ # AuthServiceProvider
│   │   │       └── Routes/    # API and Web routes
│   │   ├── User/             # User module
│   │   ├── Role/             # Role module
│   │   └── Permission/      # Permission module
│   └── Support/             # Legacy support (backward compatibility)
├── bootstrap/                # Application bootstrap files
│   ├── modules.php          # Module loader
│   └── modules-register.php # Module registration
├── database/
│   ├── migrations/           # Database migrations
│   └── seed/                 # Database seeders
├── public/                   # Web root
├── resources/
│   ├── views/                # Blade templates
│   └── lang/                 # Translation files
├── routes/                   # Main route files (web.php, api.php)
├── stubs/                    # Code generation stubs
│   └── Module/              # Module structure stubs
├── storage/
│   └── logs/                 # Application logs
└── tests/                    # PHPUnit tests
    ├── Unit/                  # Unit tests
    └── Feature/               # Feature tests
```

### Module Structure

Each module follows a consistent structure:

```
app/Modules/Example/
├── Application/              # Business logic layer
│   ├── Actions/             # Business logic actions
│   ├── DTOs/                # Data Transfer Objects
│   ├── Services/            # Service classes
│   └── Interfaces/          # Service contracts
├── Infrastructure/          # Infrastructure layer
│   ├── Models/              # Eloquent models
│   ├── Repositories/        # Data access layer
│   ├── Http/
│   │   ├── Controllers/     # Request handlers
│   │   ├── Requests/       # Form request validation
│   │   └── Resources/      # API resource transformers
│   ├── Providers/          # Service providers
│   └── Routes/             # Module routes (api.php, web.php)
├── Exceptions/             # Module-specific exceptions
├── Observers/              # Eloquent observers
└── Policies/               # Authorization policies
```

## 🎯 Usage

### Creating Modules

The recommended way to create new features is using the **modular architecture**:

```bash
# Create a new module
php slim make:module Product

# Create module with custom model name
php slim make:module Product --model=Item

# Create module with migration
php slim make:module Product --migration
```

This will automatically create:
- Complete module structure (Application, Infrastructure layers)
- Actions (Create, Update, Delete, Get, List)
- DTOs (Create, Update)
- Interfaces (CreateActionInterface, UpdateActionInterface)
- Model and Repository
- Controller with CRUD methods
- Form Requests (Create, Update)
- API Resource
- Policy
- Service Provider
- API Routes
- **Automatic dependency registration** in `bootstrap/dependencies.php`
- **Automatic module registration** in `bootstrap/modules-register.php`

**Example:**
```bash
php slim make:module Blog --model=Post --migration
```

This creates:
- `app/Modules/Blog/` with complete structure
- `CreatePostAction`, `UpdatePostAction`, etc.
- `PostRepository` automatically registered in Service Provider
- `CreatePostActionInterface` and `UpdatePostActionInterface` automatically registered in `bootstrap/dependencies.php`
- Module automatically registered in `bootstrap/modules-register.php`

### Creating Models and Migrations

```bash
# Create a model
php slim make:model Product

# Create a model with migration
php slim make:model Product -m
```

### Creating Controllers

```bash
php slim make:controller Product
```

This will create:
- Controller
- Actions (Create, Update, Delete, Get, List)
- DTOs (Create, Update)
- Form Requests (Create, Update)

### Creating Form Requests

```bash
# Create a basic request
php slim make:request User/CreateUserRequest

# Create request with auto-generated rules from model
php slim make:request User/CreateUserRequest --model=User

# Create update request with model
php slim make:request User/UpdateUserRequest --model=User --type=update

# Short syntax
php slim make:request User/CreateUserRequest -m User -t create
```

The `--model` option automatically generates validation rules based on the model's `$fillable` fields and `$casts`.

### Running Migrations

```bash
# Run migrations
php run_migrations.php

# Rollback last migration
php run_migrations.php rollback

# Refresh all migrations
php run_migrations.php refresh
```

### Available Commands

```bash
# Module creation
php slim make:module <ModuleName> [--model=<ModelName>] [--migration]

# Model creation
php slim make:model <ModelName> [-m]

# Controller creation
php slim make:controller <ControllerName>

# Request creation
php slim make:request <Namespace/RequestName> [--model=<ModelName>] [--type=<create|update>]

# Database seeding
php slim seed:database

# Queue processing
php slim queue:work [--stop-when-empty] [--max-jobs=<number>]

# List all routes
php slim list-routes
```

### Running Tests

```bash
# Run all tests
./vendor/bin/phpunit
# or
composer test

# Run with detailed output
./vendor/bin/phpunit --testdox

# Run specific test suite
./vendor/bin/phpunit tests/Unit
```

## 🔐 Authentication

The application includes a dedicated **Auth module** that handles both API and Web authentication.

### Auth Module

The Auth module (`app/Modules/Auth/`) provides:

- **API Authentication** - JWT-based authentication for API endpoints
- **Web Authentication** - Session-based authentication for web routes
- **Password Recovery** - Token-based password reset functionality
- **Event-driven** - Dispatches events for user registration and password reset

### API Authentication (JWT)

The API uses JWT tokens for authentication. After successful login, include the token in requests:

```bash
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" http://localhost:81/api/v1/users
```

### Web Authentication (Session)

Web routes use session-based authentication. The `AuthWebMiddleware` handles authentication for web routes.

## 🛡️ Authorization

### Using Middleware

**Role-based access:**
```php
$app->get('/admin/dashboard', [DashboardController::class, 'index'])
    ->add(new CheckRoleMiddleware())
    ->setArgument('roles', 'admin');

// Multiple roles (user needs one of these)
$app->get('/reports', [ReportController::class, 'index'])
    ->add(new CheckRoleMiddleware())
    ->setArgument('roles', ['admin', 'manager']);
```

**Permission-based access:**
```php
$app->post('/users', [UserController::class, 'store'])
    ->add(new CheckPermissionMiddleware())
    ->setArgument('permissions', 'create-users');

// Multiple permissions
$app->put('/posts/{id}', [PostController::class, 'update'])
    ->add(new CheckPermissionMiddleware())
    ->setArgument('permissions', ['edit-posts', 'publish-posts']);
```

### Using Policies

Create a policy:
```php
// app/Policies/PostPolicy.php
class PostPolicy extends Policy
{
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || 
               $user->hasPermission('edit-posts');
    }
}
```

Use in controller:
```php
public function update(Request $request, Response $response, int $id): Response
{
    $post = Post::find($id);
    
    if (!$this->authorize('update', $post)) {
        return $this->respondUnauthorized();
    }
    
    // Update logic...
}
```

## ⚡ Rate Limiting

Rate limiting is automatically applied to authentication endpoints (5 requests per minute). You can apply it to any route:

```php
use App\Modules\Core\Infrastructure\Http\Middleware\RateLimitMiddleware;

$rateLimit = new RateLimitMiddleware(10, 60); // 10 requests per 60 seconds
$app->post('/api/endpoint', [Controller::class, 'method'])
    ->add($rateLimit);
```

## 🌐 CORS Configuration

CORS is configured globally in `bootstrap/middleware.php`. Configure allowed origins in `.env`:

```env
CORS_ORIGINS=*
# or specific origins
CORS_ORIGINS=http://localhost:3000,https://example.com
```

## 📝 Logging

The application uses Monolog for logging. Use the Logger helper:

```php
use App\Modules\Core\Infrastructure\Support\Logger;

Logger::error('Something went wrong', ['user_id' => 123]);
Logger::warning('Suspicious activity detected');
Logger::info('User logged in', ['email' => $email]);
Logger::debug('Debug information', $data);
```

Logs are written to `storage/logs/slim.log`. Log level is automatically set based on `APP_ENV`:
- `production`: Warning and above
- `local`/`development`: Debug and above

## 🧪 Testing

The project includes a comprehensive test suite covering:

- Authentication (Auth class, LoginAction, RegisterAction)
- User management (CreateUserAction, GetUserAction, DeleteUserAction)
- Password reset (ResetPasswordAction)
- Middleware (AuthMiddleware, RateLimitMiddleware)
- Models (User, Role, Permission relationships)
- Repositories (UserRepository, RoleRepository, PermissionRepository)
- Exception handling (Custom exceptions)
- Form request validation
- Console commands (MakeModuleCommand, MakeRequestCommand)
- API Resources
- Event system (Dispatcher, Listeners)
- Queue system (FileQueue, Jobs)
- Module creation and dependency registration

**Test coverage:**
- ✅ 106+ tests
- ✅ 280+ assertions
- ✅ All tests passing

Run tests:
```bash
composer test
# or
./vendor/bin/phpunit --testdox
```

## 🔧 Configuration

### Environment Variables

Key environment variables in `.env`:

```env
# Application
APP_ENV=local
APP_URL=http://localhost:81

# Database
DB_CONNECTION=mysql
DB_HOST=slim_db
DB_PORT=3306
DB_DATABASE=slim
DB_USERNAME=slim
DB_PASSWORD=secret

# JWT
JWT_SECRET=your-secret-key-here

# Mail
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your_email@example.com
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Your App Name"
MAIL_ENCRYPTION=tls

# CORS
CORS_ORIGINS=*
```

## 📦 API Resources

The application uses Resource classes to format API responses consistently:

```php
use App\Modules\User\Infrastructure\Http\Resources\UserResource;

// Single resource
return ApiResponse::success(UserResource::make($user));

// Collection
return ApiResponse::success(UserResource::collection($users));
```

Available Resources:
- `UserResource` - Formats user data (hides password, includes roles)
- `RoleResource` - Formats role data (includes permissions)
- `PermissionResource` - Formats permission data (includes roles)

## 📊 API Response Format

All API responses follow a consistent format using the `ApiResponse` helper:

**Success Response:**
```json
{
  "status": "success",
  "data": {...},
  "message": "Optional message"
}
```

**Error Response:**
```json
{
  "status": "error",
  "message": "Error message",
  "code": "ERROR_CODE",
  "errors": {
    "field": ["Error message"]
  }
}
```

**Usage in Controllers:**
```php
use App\Support\ApiResponse;
use App\Enums\HttpStatusCode;

// Success
return ApiResponse::success($data);
return ApiResponse::success($user, HttpStatusCode::CREATED);

// Errors
return ApiResponse::error('Error message');
return ApiResponse::unauthorized();
return ApiResponse::notFound('User not found');
return ApiResponse::validationError(['email' => ['Invalid email']]);
```

## 📚 API Endpoints

### Authentication
- `POST /api/v1/register` - Register new user
- `POST /api/v1/login` - Login and get JWT token
- `POST /api/v1/password-recovery` - Request password reset
- `POST /api/v1/reset-password` - Reset password with token

### Users (requires authentication)
- `GET /api/v1/users` - List all users
- `POST /api/v1/users` - Create user
- `GET /api/v1/users/{id}` - Get user
- `PUT /api/v1/users/{id}` - Update user
- `DELETE /api/v1/users/{id}` - Delete user

### Roles (requires authentication)
- `GET /api/v1/roles` - List all roles
- `POST /api/v1/roles` - Create role
- `GET /api/v1/roles/{id}` - Get role
- `PUT /api/v1/roles/{id}` - Update role
- `DELETE /api/v1/roles/{id}` - Delete role

### Permissions (requires authentication)
- `GET /api/v1/permissions` - List all permissions
- `POST /api/v1/permissions` - Create permission
- `GET /api/v1/permissions/{id}` - Get permission
- `PUT /api/v1/permissions/{id}` - Update permission
- `DELETE /api/v1/permissions/{id}` - Delete permission

## 🏗️ Architecture

The project follows a **modular clean architecture** pattern:

### Modular Architecture

The application is organized into **independent modules**, each containing:

1. **Application Layer** - Business logic
   - **Actions** - Business logic operations
   - **DTOs** - Data Transfer Objects for type-safe data handling
   - **Interfaces** - Service contracts for dependency injection
   - **Services** - Complex business logic services

2. **Infrastructure Layer** - Technical implementation
   - **Models** - Eloquent models for database interaction
   - **Repositories** - Data access layer abstraction (Repository pattern)
   - **Controllers** - Thin controllers that delegate to Actions
   - **Requests** - Form request validation
   - **Resources** - API response transformers
   - **Providers** - Service providers for dependency registration
   - **Routes** - Module-specific routes

3. **Cross-cutting Concerns**
   - **Middleware** - Request/response processing
   - **Policies** - Authorization logic
   - **Exceptions** - Custom exception classes for better error handling
   - **Events** - Event-driven architecture
   - **Jobs** - Asynchronous task processing

### Module Registration

Modules are automatically registered via `bootstrap/modules-register.php`:

```php
return [
    // Core module must be loaded first
    App\Modules\Core\Infrastructure\Providers\CoreServiceProvider::class,
    
    // Auth module
    App\Modules\Auth\Infrastructure\Providers\AuthServiceProvider::class,
    
    // Feature modules
    App\Modules\User\Infrastructure\Providers\UserServiceProvider::class,
    App\Modules\Role\Infrastructure\Providers\RoleServiceProvider::class,
    App\Modules\Permission\Infrastructure\Providers\PermissionServiceProvider::class,
];
```

### Dependency Injection

The application uses **PHP-DI** with automatic dependency registration:

- **Repositories** - Automatically registered in Service Providers
- **Action Interfaces** - Automatically registered in `bootstrap/dependencies.php` when using `make:module`
- **Autowiring** - PHP-DI automatically resolves constructor dependencies

**How it works:**

1. **When creating a module** with `make:module`:
   - Repository is registered in `ServiceProvider::register()`
   - Action Interfaces are registered in `bootstrap/dependencies.php`
   - Use statements are automatically added

2. **PHP-DI Autowiring**:
   - Automatically resolves concrete classes (no registration needed)
   - Resolves constructor dependencies via type hints
   - Example: `LoginAction` needs `UserRepository` → automatically injected

3. **Interface-based injection**:
   - Controllers use interfaces (e.g., `CreateUserActionInterface`)
   - PHP-DI resolves to implementation via `dependencies.php`
   - Allows for easy mocking in tests

### Repository Pattern

The application uses the Repository pattern to abstract data access logic:

```php
use App\Modules\User\Infrastructure\Repositories\UserRepository;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepository $repository
    ) {}
    
    public function show(int $id): Response
    {
        $user = $this->repository->findOrFail($id);
        return ApiResponse::success(UserResource::make($user));
    }
}
```

**Automatic Registration:**
When you create a module with `make:module`, the Repository is automatically registered in the Service Provider:

```php
// ServiceProvider::register()
$container->set(UserRepository::class, \DI\autowire(UserRepository::class));
```

Available Repositories:
- `UserRepository` - User data access with methods like `findByEmail()`, `findByPasswordResetToken()`
- `RoleRepository` - Role data access with methods like `findByName()`, `paginateWithPermissions()`
- `PermissionRepository` - Permission data access with methods like `findByName()`, `paginateWithRoles()`

### Action Pattern with Interfaces

Actions implement interfaces for better testability and flexibility:

```php
// Interface
interface CreateUserActionInterface
{
    public function execute(CreateUserDTO $dto): User;
}

// Implementation
final class CreateUserAction implements CreateUserActionInterface
{
    public function __construct(
        private readonly UserRepository $repository
    ) {}
    
    public function execute(CreateUserDTO $dto): User
    {
        return $this->repository->create([...]);
    }
}
```

**Automatic Registration:**
When you create a module, Action Interfaces are automatically registered in `bootstrap/dependencies.php`:

```php
CreateUserActionInterface::class => \DI\autowire(CreateUserAction::class),
```

### Working with Modules

**Creating a new module:**

```bash
php slim make:module Product --migration
```

This creates a complete module structure. After creation:

1. **Update the Model** (`app/Modules/Product/Infrastructure/Models/Product.php`):
   - Add `$fillable` fields
   - Add `$casts` for type casting
   - Add relationships if needed

2. **Update the DTOs** (`app/Modules/Product/Application/DTOs/`):
   - Add properties to `CreateProductDTO`
   - Add optional properties to `UpdateProductDTO`

3. **Update the Actions** (`app/Modules/Product/Application/Actions/`):
   - Map DTO properties to model attributes in `CreateProductAction`
   - Add business logic as needed

4. **Update the Controller** (`app/Modules/Product/Infrastructure/Http/Controllers/ProductController.php`):
   - Map request data to DTOs in `store()` and `update()` methods

5. **Update the Resource** (`app/Modules/Product/Infrastructure/Http/Resources/ProductResource.php`):
   - Add fields to the resource output

6. **Update the Requests** (`app/Modules/Product/Infrastructure/Http/Requests/`):
   - Add validation rules as needed

7. **Update the Policy** (`app/Modules/Product/Policies/ProductPolicy.php`):
   - Add authorization logic

**Module is ready to use!** Routes are automatically loaded from `app/Modules/Product/Infrastructure/Routes/api.php`.

### Exception Handling

Custom exception classes provide consistent error handling:

```php
use App\Modules\Core\Infrastructure\Exceptions\NotFoundException;
use App\Modules\Core\Infrastructure\Exceptions\InvalidCredentialsException;
use App\Modules\Core\Infrastructure\Exceptions\UnauthorizedException;
use App\Modules\Core\Infrastructure\Exceptions\ForbiddenException;
use App\Modules\Core\Infrastructure\Exceptions\BadRequestException;

// In Actions
throw new NotFoundException('User not found');
throw new InvalidCredentialsException('Invalid email or password');
```

The `ExceptionHandlerMiddleware` automatically converts exceptions to appropriate API responses.

## 🔒 Security Features

- ✅ Password hashing with bcrypt
- ✅ JWT token authentication (API)
- ✅ Session-based authentication (Web)
- ✅ CSRF protection for web routes
- ✅ Rate limiting on auth endpoints (5 requests/minute)
- ✅ Rate limiting on all API endpoints (configurable)
- ✅ Input validation with FormRequest
- ✅ SQL injection protection (Eloquent ORM)
- ✅ XSS protection (Blade templating)
- ✅ Secure session handling
- ✅ Centralized exception handling with proper error responses
- ✅ Modular architecture for better code organization
- ✅ Automatic dependency registration

## 📦 Dependencies

### Core
- `slim/slim` - Slim Framework 4
- `illuminate/database` - Eloquent ORM
- `illuminate/validation` - Validation
- `illuminate/view` - Blade templating
- `php-di/slim-bridge` - Dependency injection

### Authentication & Security
- `firebase/php-jwt` - JWT tokens
- `slim/csrf` - CSRF protection
- `tuupola/cors-middleware` - CORS support

### Utilities
- `monolog/monolog` - Logging
- `phpmailer/phpmailer` - Email sending
- `vlucas/phpdotenv` - Environment variables
- `illuminate/pagination` - Pagination support
- `illuminate/support` - Laravel support package

### Development
- `phpunit/phpunit` - Testing
- `laravel/pint` - Code formatting
- `rector/rector` - Code refactoring

## 🚀 Deployment

### Storage Directory Setup

The application requires write access to the `storage/` directory for logs, cache, and queue files.

#### Setup

**For Nginx:**
```bash
# Set ownership
sudo chown -R www-data:www-data storage

# Set permissions
sudo chmod -R 775 storage
```

**For Apache:**
```bash
# Set ownership
sudo chown -R www-data:www-data storage

# Set permissions
sudo chmod -R 775 storage
```

**For Docker:**
The storage directory is automatically mounted and permissions are handled by the container.

### Web Server Configuration

#### Nginx Configuration

The nginx configuration is located in `docker/nginx/app.conf`. For production, ensure:

1. **Document root** points to `/var/www/html/public`
2. **PHP-FPM** is configured correctly
3. **Storage directory** is protected (denied access in nginx config)
4. **Security headers** are set

Example production nginx configuration:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/public;
    
    # Deny access to storage
    location ~ ^/storage/ {
        deny all;
    }
    
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

#### Apache Configuration

The `.htaccess` file in `public/` directory handles URL rewriting. Ensure:

1. **mod_rewrite** is enabled:
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

2. **AllowOverride** is set to All in Apache config:
   ```apache
   <Directory /var/www/html/public>
       Options -Indexes +FollowSymLinks
       AllowOverride All
       Require all granted
   </Directory>
   ```

3. **Storage directory** is protected (already configured in `.htaccess`)

### Permissions Reference

| Directory | Permissions | Owner | Purpose |
|-----------|-------------|-------|---------|
| `storage/` | 775 | www-data | Root storage directory |
| `storage/logs/` | 775 | www-data | Application logs |
| `storage/cache/` | 775 | www-data | Blade view cache |
| `storage/queue/` | 775 | www-data | Queue job files |

**Note:** Adjust user/group (`www-data`, `nginx`, `apache`) based on your server configuration.

### Security Considerations

1. **Storage directory** should never be accessible via web browser
2. **Environment files** (`.env`) should be protected
3. **Composer files** should not be accessible
4. **Git files** should not be accessible
5. **File uploads** (if implemented) should be stored outside `public/` directory

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This project is licensed under the MIT License.

## 🙏 Acknowledgments

- [Slim Framework](https://www.slimframework.com/)
- [Laravel](https://laravel.com/) for Eloquent, Blade, and Validation
- All the amazing open-source contributors

---

**Made with ❤️ for the PHP community**
