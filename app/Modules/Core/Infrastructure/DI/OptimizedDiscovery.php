<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\DI;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use RegexIterator;

/**
 * Optimized Auto-Discovery for Dependency Injection.
 *
 * Automatically discovers and registers Interface → Implementation bindings.
 * Features:
 * - File-based caching for production performance
 * - PSR-4 namespace support
 * - Configurable scan paths
 * - Statistics and reporting
 */
final class OptimizedDiscovery
{
    private const CACHE_FILE = __DIR__.'/../../../../storage/cache/autowiring.php';
    private const CACHE_TTL_DEV = 3600; // 1 hour in development
    private const DEFAULT_SCAN_PATHS = [
        __DIR__.'/../../../../../app/Modules',
    ];

    /**
     * @var array<int, string>
     */
    private array $scanPaths;

    /**
     * @param array<int, string> $scanPaths
     */
    public function __construct(array $scanPaths = self::DEFAULT_SCAN_PATHS)
    {
        $this->scanPaths = $scanPaths;
    }

    /**
     * Build DI definitions from discovered interfaces.
     *
     * @return array<class-string, \DI\Definition\HelperDefinition|\DI\Definition\AutowireDefinition>
     */
    public function buildDefinitions(): array
    {
        // Check cache first (production optimization)
        if ($this->shouldUseCache()) {
            return require self::CACHE_FILE;
        }

        // Scan and build definitions
        $definitions = $this->scanModules();

        // Write to cache
        $this->writeCache($definitions);

        return $definitions;
    }

    /**
     * Warm the cache by scanning and caching definitions.
     *
     * @return array{count: int, duration: float}
     */
    public function warmCache(): array
    {
        $start = microtime(true);

        $definitions = $this->scanModules();
        $this->writeCache($definitions);

        return [
            'count' => count($definitions),
            'duration' => round((microtime(true) - $start) * 1000, 2),
        ];
    }

    /**
     * Clear the discovery cache.
     */
    public function clearCache(): bool
    {
        if (file_exists(self::CACHE_FILE)) {
            return unlink(self::CACHE_FILE);
        }

        return true;
    }

    /**
     * Check if cache is valid and should be used.
     */
    public function shouldUseCache(): bool
    {
        if (!file_exists(self::CACHE_FILE)) {
            return false;
        }

        // In production: always use cache if it exists
        if ($this->isProduction()) {
            return true;
        }

        // In development: check TTL
        $age = time() - filemtime(self::CACHE_FILE);

        return $age < self::CACHE_TTL_DEV;
    }

    /**
     * Get discovery statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $isCached = file_exists(self::CACHE_FILE);
        
        // Get bindings from scan (not definitions to avoid serialization issues)
        $bindings = [];
        foreach ($this->scanPaths as $path) {
            if (!is_dir($path)) {
                continue;
            }
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            $phpFiles = new RegexIterator($iterator, '/\.php$/');
            
            foreach ($phpFiles as $file) {
                $className = $this->getClassFromFile($file->getPathname());
                
                if ($className === null) {
                    continue;
                }
                
                try {
                    $reflection = new ReflectionClass($className);
                    
                    if ($reflection->isInterface()) {
                        $implementation = $this->findImplementation($className);
                        if ($implementation !== null) {
                            $bindings[$className] = $implementation;
                        }
                    }
                } catch (ReflectionException) {
                    continue;
                }
            }
        }

        return [
            'total_bindings' => count($bindings),
            'cache_enabled' => $this->isProduction(),
            'cache_file' => self::CACHE_FILE,
            'cache_exists' => $isCached,
            'cache_valid' => $this->shouldUseCache(),
            'environment' => $_ENV['APP_ENV'] ?? 'unknown',
            'sample_bindings' => array_slice($bindings, 0, 20, true),
        ];
    }

    /**
     * Get cache file path.
     */
    public static function getCacheFile(): string
    {
        return self::CACHE_FILE;
    }

    /**
     * Scan modules for interfaces and their implementations.
     *
     * @return array<class-string, \DI\Definition\HelperDefinition|\DI\Definition\AutowireDefinition>
     */
    private function scanModules(): array
    {
        $definitions = [];
        $interfaces = [];

        // Step 1: Find all interface files
        foreach ($this->scanPaths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            $phpFiles = new RegexIterator($iterator, '/\.php$/');

            foreach ($phpFiles as $file) {
                $className = $this->getClassFromFile($file->getPathname());

                if ($className === null) {
                    continue;
                }

                try {
                    $reflection = new ReflectionClass($className);

                    if ($reflection->isInterface()) {
                        $interfaces[$className] = $file->getPathname();
                    }
                } catch (ReflectionException) {
                    // Skip classes that can't be reflected
                    continue;
                }
            }
        }

        // Step 2: Find implementations for each interface
        foreach ($interfaces as $interfaceName => $interfaceFile) {
            $implementation = $this->findImplementation($interfaceName);

            if ($implementation !== null) {
                $definitions[$interfaceName] = \DI\autowire($implementation);
            }
        }

        return $definitions;
    }

    /**
     * Extract fully-qualified class name from file.
     */
    private function getClassFromFile(string $filePath): ?string
    {
        $contents = file_get_contents($filePath);

        if ($contents === false) {
            return null;
        }

        // Extract namespace
        $namespace = '';
        if (preg_match('/namespace\s+([^;]+);/', $contents, $matches)) {
            $namespace = $matches[1];
        }

        // Extract class name
        $class = '';
        if (preg_match('/\b(?:class|interface|trait|enum)\s+(\w+)/', $contents, $matches)) {
            $class = $matches[1];
        }

        if ($class === '') {
            return null;
        }

        return $namespace ? $namespace . '\\' . $class : $class;
    }

    /**
     * Find implementation class for an interface.
     *
     * Naming convention: InterfaceName → InterfaceNameImpl or remove 'Interface' suffix
     *
     * @param class-string $interfaceName
     * @return class-string|null
     */
    private function findImplementation(string $interfaceName): ?string
    {
        // Strategy 1: Look for class with same name minus 'Interface' suffix
        if (str_ends_with($interfaceName, 'Interface')) {
            $possibleClass = substr($interfaceName, 0, -9); // Remove 'Interface'

            if (class_exists($possibleClass)) {
                $reflection = new ReflectionClass($possibleClass);

                if ($reflection->implementsInterface($interfaceName)) {
                    return $possibleClass;
                }
            }
        }

        // Strategy 2: Look in same namespace for class with similar name
        $parts = explode('\\', $interfaceName);
        $interfaceShortName = array_pop($parts);
        $namespace = implode('\\', $parts);

        // Remove 'Interface' suffix if present
        $baseName = str_ends_with($interfaceShortName, 'Interface')
            ? substr($interfaceShortName, 0, -9)
            : $interfaceShortName;

        $possibleClass = $namespace . '\\' . $baseName;

        if (class_exists($possibleClass)) {
            $reflection = new ReflectionClass($possibleClass);

            if ($reflection->implementsInterface($interfaceName) && !$reflection->isAbstract()) {
                return $possibleClass;
            }
        }

        // Strategy 3: Scan directory for classes implementing this interface
        $interfaceDir = $this->getInterfaceDirectory($interfaceName);

        if ($interfaceDir !== null && is_dir($interfaceDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($interfaceDir, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            $phpFiles = new RegexIterator($iterator, '/\.php$/');

            foreach ($phpFiles as $file) {
                $className = $this->getClassFromFile($file->getPathname());

                if ($className === null || $className === $interfaceName) {
                    continue;
                }

                try {
                    $reflection = new ReflectionClass($className);

                    if ($reflection->implementsInterface($interfaceName) && !$reflection->isAbstract()) {
                        return $className;
                    }
                } catch (ReflectionException) {
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * Get directory containing the interface file.
     */
    private function getInterfaceDirectory(string $interfaceName): ?string
    {
        try {
            $reflection = new ReflectionClass($interfaceName);
            $file = $reflection->getFileName();

            return $file ? dirname($file) : null;
        } catch (ReflectionException) {
            return null;
        }
    }

    /**
     * Write definitions to cache file.
     *
     * @param array<class-string, \DI\Definition\HelperDefinition> $definitions
     */
    private function writeCache(array $definitions): void
    {
        $cacheDir = dirname(self::CACHE_FILE);

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Convert definitions to serializable format
        $serializable = [];
        foreach ($definitions as $interface => $definition) {
            // Store the class name that implements the interface
            $reflection = new \ReflectionClass($interface);
            $implementation = $this->findImplementation($interface);
            
            if ($implementation !== null) {
                $serializable[$interface] = $implementation;
            }
        }

        // Generate PHP code
        $code = "<?php\n\n";
        $code .= "// Auto-generated by OptimizedDiscovery\n";
        $code .= "// Generated at: " . date('Y-m-d H:i:s') . "\n";
        $code .= "// Total bindings: " . count($serializable) . "\n\n";
        $code .= "return [\n";

        foreach ($serializable as $interface => $implementation) {
            $code .= "    \\{$interface}::class => \\DI\\autowire(\\{$implementation}::class),\n";
        }

        $code .= "];\n";

        file_put_contents(self::CACHE_FILE, $code);
    }

    /**
     * Check if running in production.
     */
    private function isProduction(): bool
    {
        return in_array($_ENV['APP_ENV'] ?? '', ['production', 'staging'], true);
    }
}
