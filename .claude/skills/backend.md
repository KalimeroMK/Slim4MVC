# Slim4MVC Backend Rules

## Architecture
This is a **modular Slim 4 MVC starter kit** using PHP-DI, Eloquent ORM, and BladeOne.

### Module Structure (MUST follow)
Each module lives in `app/Modules/{ModuleName}/` with this exact structure:

```
app/Modules/{Module}/
├── Application/
│   ├── Actions/              # Action classes (business logic)
│   ├── DTOs/                 # Data Transfer Objects
│   ├── Interfaces/           # Action interfaces for DI
│   └── Services/             # Domain services (if needed)
├── Infrastructure/
│   ├── Database/Factories/   # Eloquent model factories
│   ├── Http/
│   │   ├── Controllers/      # API controllers
│   │   ├── Controllers/Web/  # Web controllers
│   │   ├── Requests/         # API FormRequest classes
│   │   ├── Requests/Web/     # Web FormRequest classes
│   │   └── Resources/        # API Resources
│   ├── Models/               # Eloquent models
│   ├── Providers/            # Service providers
│   ├── Repositories/         # Repository classes
│   └── Routes/
│       └── api.php           # Module API routes
├── Policies/                 # Authorization policies
└── database/
    ├── factories/
    └── migrations/
```

## PHP 8.4 Standards
- Use `declare(strict_types=1);` in every file.
- Use `final readonly` for Action classes.
- Use named arguments and constructor property promotion.
- Use match expressions instead of switch where possible.

## Action Classes (Business Logic)
- **All business logic MUST live in Action classes**, never in controllers or Blade views.
- Actions are `final readonly`, implement an interface, and are injected into controllers.
- Example pattern:
  ```php
  final readonly class CreateProductAction implements CreateProductActionInterface
  {
      public function __construct(private ProductRepository $repository) {}

      public function execute(CreateProductDTO $dto): Product
      {
          $product = $this->repository->create([
              'name' => $dto->name,
              'price' => $dto->price,
          ]);

          return $product->load([]);
      }
  }
  ```
- Register interfaces in `bootstrap/dependencies.php` using `autowire()`.

## DTO Classes (Data Transfer)
- DTOs are `final` classes with public readonly properties.
- MUST have a static `fromRequest(array $validated): self` factory method.
- Example:
  ```php
  final class CreateProductDTO
  {
      public function __construct(
          public readonly string $name,
          public readonly float $price,
      ) {}

      public static function fromRequest(array $validated): self
      {
          return new self(
              name: $validated['name'],
              price: (float) $validated['price'],
          );
      }
  }
  ```

## Repository Classes (Database Layer)
- Repositories extend `App\Modules\Core\Infrastructure\Repositories\EloquentRepository`.
- MUST implement a `model(): string` method returning the Eloquent model class string.
- Keep custom query methods thin; use Eloquent scopes where possible.

## Request Classes (Validation)
- Extend `App\Modules\Core\Infrastructure\Http\Requests\FormRequest`.
- Define rules in the `rules(): array` method.
- Define custom messages in `messages(): array`.
- Use separate Web request classes in `Http/Requests/Web/` when web and API rules differ.

## Controllers
- API controllers extend `App\Modules\Core\Infrastructure\Http\Controllers\Controller` and use `HandlesCrudResponses` trait.
- Web controllers extend the same base controller but return Blade views via `view()` helper.
- Controllers MUST be thin: only delegate to Actions and format responses.

## Workflow (ALWAYS follow)
1. **Plan first**: Analyze requirements and existing stubs before writing code.
2. **Respect stubs**: Use the stub files in `stubs/Module/` as templates for new modules.
3. **Register modules**: Add new module ServiceProviders to `bootstrap/modules-register.php`.
4. **Register dependencies**: Add Action interfaces to `bootstrap/dependencies.php`.
5. **Write tests**: Create PHPUnit tests for every Action, DTO, Repository, and Controller.
6. **Run tests**: Execute `vendor/bin/phpunit` and fix failures before finishing.
7. **Review**: Do a self-review for PHPStan level 8 compliance and Pint code style.
