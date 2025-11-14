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
- **CLI Commands** - Artisan-like commands for scaffolding (models, controllers, requests)
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

```
├── app/
│   ├── Actions/              # Business logic layer
│   ├── DTO/                  # Data Transfer Objects
│   ├── Exceptions/            # Custom exception classes
│   ├── Http/
│   │   ├── Controllers/      # Request handlers
│   │   ├── Middleware/       # HTTP middleware
│   │   └── Requests/         # Form request validation
│   ├── Interface/            # Service contracts
│   ├── Models/               # Eloquent models
│   ├── Policies/            # Authorization policies
│   ├── Repositories/          # Data access layer (Repository pattern)
│   ├── Support/              # Helper classes (Auth, Logger, Mailer)
│   └── View/                 # Blade integration
├── bootstrap/                # Application bootstrap files
├── database/
│   ├── migrations/           # Database migrations
│   └── seed/                 # Database seeders
├── public/                   # Web root
├── resources/
│   ├── views/                # Blade templates
│   └── lang/                 # Translation files
├── routes/                   # Route definitions
├── storage/
│   └── logs/                 # Application logs
└── tests/                    # PHPUnit tests
    ├── Unit/                  # Unit tests
    └── Feature/               # Feature tests
```

## 🎯 Usage

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

### Listing Routes

```bash
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
use App\Http\Middleware\RateLimitMiddleware;

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
use App\Support\Logger;

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
- Console commands (MakeRequestCommand)
- API Resources
- Event system (Dispatcher, Listeners)
- Queue system (FileQueue, Jobs)

**Test coverage:**
- ✅ 96 tests
- ✅ 223 assertions
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
use App\Http\Resources\UserResource;

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

The project follows a clean architecture pattern:

1. **Controllers** - Thin controllers that delegate to Actions
2. **Actions** - Business logic layer
3. **Repositories** - Data access layer abstraction (Repository pattern)
4. **DTOs** - Data Transfer Objects for type-safe data handling
5. **Models** - Eloquent models for database interaction
6. **Middleware** - Request/response processing
7. **Policies** - Authorization logic
8. **Form Requests** - Input validation
9. **Exceptions** - Custom exception classes for better error handling

### Repository Pattern

The application uses the Repository pattern to abstract data access logic:

```php
use App\Repositories\UserRepository;

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

Available Repositories:
- `UserRepository` - User data access with methods like `findByEmail()`, `findByPasswordResetToken()`
- `RoleRepository` - Role data access with methods like `findByName()`, `paginateWithPermissions()`
- `PermissionRepository` - Permission data access with methods like `findByName()`, `paginateWithRoles()`

### Exception Handling

Custom exception classes provide consistent error handling:

```php
use App\Exceptions\NotFoundException;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\BadRequestException;

// In Actions
throw new NotFoundException('User not found');
throw new InvalidCredentialsException('Invalid email or password');
```

The `ExceptionHandlerMiddleware` automatically converts exceptions to appropriate API responses.

## 🔒 Security Features

- ✅ Password hashing with bcrypt
- ✅ JWT token authentication
- ✅ CSRF protection for web routes
- ✅ Rate limiting on auth endpoints
- ✅ Input validation with FormRequest
- ✅ SQL injection protection (Eloquent ORM)
- ✅ XSS protection (Blade templating)
- ✅ Secure session handling
- ✅ Centralized exception handling with proper error responses

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
