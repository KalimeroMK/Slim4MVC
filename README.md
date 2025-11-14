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
- **Testing Suite** - Comprehensive test coverage with PHPUnit (42 tests, 105 assertions)
- **CLI Commands** - Artisan-like commands for scaffolding
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

The application will be available at [http://localhost:81](http://localhost:81)

## 📁 Project Structure

```
├── app/
│   ├── Actions/              # Business logic layer
│   ├── DTO/                  # Data Transfer Objects
│   ├── Http/
│   │   ├── Controllers/      # Request handlers
│   │   ├── Middleware/       # HTTP middleware
│   │   └── Requests/         # Form request validation
│   ├── Interface/            # Service contracts
│   ├── Models/               # Eloquent models
│   ├── Policies/            # Authorization policies
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
php slim make:controller ProductController
```

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

The project includes a comprehensive test suite with 42 tests covering:

- Authentication (Auth class, LoginAction, RegisterAction)
- User management (CreateUserAction, GetUserAction, DeleteUserAction)
- Password reset (ResetPasswordAction)
- Middleware (AuthMiddleware, RateLimitMiddleware)
- Models (User, Role, Permission relationships)
- Form request validation

**Test coverage:**
- ✅ 42 tests
- ✅ 105 assertions
- ✅ All tests passing

Run tests:
```bash
composer test
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
3. **DTOs** - Data Transfer Objects for type-safe data handling
4. **Models** - Eloquent models for database interaction
5. **Middleware** - Request/response processing
6. **Policies** - Authorization logic
7. **Form Requests** - Input validation

## 🔒 Security Features

- ✅ Password hashing with bcrypt
- ✅ JWT token authentication
- ✅ CSRF protection for web routes
- ✅ Rate limiting on auth endpoints
- ✅ Input validation with FormRequest
- ✅ SQL injection protection (Eloquent ORM)
- ✅ XSS protection (Blade templating)
- ✅ Secure session handling

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

### Development
- `phpunit/phpunit` - Testing
- `laravel/pint` - Code formatting
- `rector/rector` - Code refactoring

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
