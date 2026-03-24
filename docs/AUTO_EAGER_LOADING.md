# Auto Eager Loading for Eloquent

This feature enables automatic eager loading of relationships in Eloquent models without manually adding `->with()` to every query.

## 📋 Contents

- [Quick Start](#quick-start)
- [Usage Methods](#usage-methods)
- [Performance](#performance)
- [Debugging N+1](#debugging-n1)
- [API Reference](#api-reference)
- [Examples](#examples)

## 🚀 Quick Start

### 1. Add the trait to your model

```php
use App\Modules\Core\Infrastructure\Database\Eloquent\AutoEloquentRelations;

class User extends Model
{
    use AutoEloquentRelations;
    
    // Define which relations to auto-load
    protected array $autoWith = ['roles', 'profile'];
    
    // Or exclude specific relations from auto-loading
    protected array $excludeAutoWith = ['passwordResets'];
}
```

### 2. Now you can use:

```php
// Relations are automatically loaded!
$user = User::find(1);
$user->roles; // Already loaded, no N+1

// Without auto-loading
$user = User::queryWithoutAutoWith()->find(1);

// Manually using helper function
$users = User::all();
$users = preload($users, ['roles', 'permissions']);
```

## 🔧 Usage Methods

### Method 1: Explicit `autoWith` (Recommended)

Fastest and most predictable method. Define relations in the model:

```php
class User extends Model
{
    use AutoEloquentRelations;
    
    protected array $autoWith = ['roles', 'profile'];
}
```

### Method 2: `preload()` helper function

Use the `preload()` function for on-demand loading:

```php
// For a single model
$user = preload(User::find(1), ['roles']);

// For a collection
$users = User::all();
$users = preload($users, ['roles', 'profile']);

// Only missing relations
$users = preload_missing($users, ['roles']);
```

### Method 3: Auto-detection (Caution!)

This scans all model methods via reflection and can be slow:

```php
// In config/eloquent.php or bootstrap
AutoRelationConfig::enableGlobally();

// Or for a specific model
AutoRelationConfig::enableFor(User::class);
```

## ⚡ Performance

### Caching

Relations are cached per model on first scan:

```php
// First load scans (once)
$relations = User::detectRelations();

// Subsequent calls are from cache
$relations = User::detectRelations(); // Fast!

// Clear cache if you add a new relation
User::clearRelationCache();
// or
AutoEloquentRelations::clearRelationCache();
```

### Limiting Relations

```php
// Maximum 10 relations by default
AutoRelationConfig::setMaxAutoLoadRelations(5);
```

### Disable for Specific Models

```php
// Don't auto-load for User
AutoRelationConfig::disableFor(User::class);

// Or in the model
class User extends Model
{
    use AutoEloquentRelations;
    
    protected array $autoWith = []; // Empty = disabled
}
```

## 🐛 Debugging N+1

In development environment, you can enable lazy loading detection:

```php
// .env
ELOQUENT_LAZY_LOADING_DETECTION=true
```

Or programmatically:

```php
// Throws exception when accessing non-loaded relation
detect_lazy_loading();

// Or just log
AutoRelationConfig::enableLazyLoadingDetection();
```

When N+1 occurs, you'll get an exception:
```
Illuminate\Database\LazyLoadingViolationException: 
Attempted to lazy load [roles] on model [User] but lazy loading is disabled.
```

## 📚 API Reference

### Global Helper Functions

```php
// Preload relations
preload($modelOrCollection, ['relation1', 'relation2']);

// Preload only missing
preload_missing($collection, ['relation1']);

// Enable/disable auto eager loading
enable_auto_eager_loading();
disable_auto_eager_loading();

// Detect lazy loading (development only) - throws exception on N+1
detect_lazy_loading();

// Clear relation cache
clear_relation_cache(User::class);
clear_relation_cache(); // All models
```

### AutoRelationConfig

```php
use App\Modules\Core\Infrastructure\Database\Eloquent\AutoRelationConfig;

// Global settings
AutoRelationConfig::enableGlobally();
AutoRelationConfig::disableGlobally();

// Per-model settings
AutoRelationConfig::enableFor([User::class, Post::class]);
AutoRelationConfig::disableFor(User::class);

// Lazy loading detection
AutoRelationConfig::enableLazyLoadingDetection(log: true);
AutoRelationConfig::disableLazyLoadingDetection();

// Configuration
AutoRelationConfig::setMaxAutoLoadRelations(10);
AutoRelationConfig::configure([
    'enabled' => true,
    'mode' => 'explicit',
    'lazy_loading_detection' => true,
]);
AutoRelationConfig::reset(); // Reset all
```

### RelationPreloader Service

```php
use App\Modules\Core\Infrastructure\Database\Eloquent\RelationPreloader;

// Preload on single model
$user = RelationPreloader::load($user, ['roles']);

// Preload on collection
$users = RelationPreloader::loadMany($users, ['roles', 'profile']);

// Preload only missing
$users = RelationPreloader::loadMissing($users, ['roles']);

// Check if loaded
$loaded = RelationPreloader::hasLoaded($users, ['roles']);
$missing = RelationPreloader::getMissingRelations($users, ['roles']);

// With query builder
$query = RelationPreloader::with(User::query(), ['roles']);
$users = $query->get();
```

## 🎯 Examples

### Example 1: API Response with Relations

```php
class UserController extends Controller
{
    public function index()
    {
        // If User has autoWith = ['roles'], relations are already loaded
        $users = User::paginate(10);
        
        return UserResource::collection($users);
    }
}
```

### Example 2: On-demand Loading in Service

```php
class UserService
{
    public function getUsersWithDetails()
    {
        $users = User::all();
        
        // Load additional relations if needed
        if ($this->needsPermissions()) {
            $users = preload($users, ['roles.permissions']);
        }
        
        return $users;
    }
}
```

### Example 3: Query Builder with Includes

```php
// This already works via QueryBuilder
// GET /api/users?include=roles,profile

$users = (new QueryBuilder($request, [
    'searchable' => ['name', 'email'],
    'filterable' => ['status'],
]))->paginate(User::class);
```

### Example 4: Disabling Auto-load for Specific Queries

```php
// Sometimes you don't need relations
$users = User::queryWithoutAutoWith()
    ->select('id', 'name', 'email')
    ->get();
```

### Example 5: Nested Relations

```php
class User extends Model
{
    use AutoEloquentRelations;
    
    // Auto-load nested relations
    protected array $autoWith = ['roles', 'roles.permissions'];
}
```

## ⚠️ Notes

1. **Don't use auto-detection in production** - reflection is slow
2. **Use `autoWith` for control** - explicit is better
3. **Enable lazy loading detection in dev** - catch N+1 early
4. **Cache your relations** - first scan is slow

## 🔗 Integration with Existing QueryBuilder

The existing `QueryBuilder` already supports eager loading via the `?include=` parameter. This new feature is a complement for automatic loading without API parameters.

```php
// Combination:
// 1. User has autoWith = ['profile']
// 2. API request: ?include=roles

$user = User::find(1);
// Loaded: profile (auto) + roles (from include)
```

## 🛠️ Troubleshooting

### Relations not loading automatically

1. Check if trait is added: `use AutoEloquentRelations;`
2. Check if `autoWith` is defined and not empty
3. Check if auto-loading is enabled in config
4. Try clearing relation cache: `clear_relation_cache()`

### Too many queries

1. Reduce `autoWith` to only necessary relations
2. Use `queryWithoutAutoWith()` for simple queries
3. Enable lazy loading detection to catch N+1 issues

### Memory issues

1. Limit relations in `autoWith`
2. Use pagination instead of loading all records
3. Set `max_relations` limit in config
