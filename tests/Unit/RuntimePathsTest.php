<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Core\Infrastructure\Cache\FileCache;
use App\Modules\Core\Infrastructure\DI\OptimizedDiscovery;
use App\Modules\Core\Infrastructure\Queue\FileQueue;
use App\Modules\Core\Infrastructure\Support\Paths;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionProperty;
use SplFileInfo;

/**
 * Filesystem paths must resolve inside the project, and writable ones under storage/.
 *
 * Every one of these used to be built by counting `../` segments or dirname() levels
 * at the call site, and the count was wrong five separate times: the file queue wrote
 * into app/Modules/Core/storage, the autowiring cache into app/storage, the file cache
 * one level *above* the project root, the queued mailer at a views directory that does
 * not exist, and `db:seed` / `swagger:generate` at files outside the checkout. Counting
 * by hand is the failure mode, so the depth now lives in Paths alone and is asserted
 * here.
 */
final class RuntimePathsTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = dirname(__DIR__, 2);
    }

    /**
     * Directories the application derives from Paths and expects to be present in a
     * fresh checkout.
     *
     * @return array<string, array{string}>
     */
    public static function expectedDirectories(): array
    {
        return [
            'storage' => [Paths::storage()],
            'view cache' => [Paths::storage('cache/view')],
            'resources' => [Paths::resources()],
            'views' => [Paths::resources('views')],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function writablePaths(): array
    {
        $fileCache = new ReflectionProperty(FileCache::class, 'cachePath');
        $fileQueue = new ReflectionProperty(FileQueue::class, 'queueFile');

        return [
            'file cache' => [(string) $fileCache->getValue(new FileCache())],
            'file queue' => [(string) $fileQueue->getValue(new FileQueue())],
            'autowiring cache' => [OptimizedDiscovery::getCacheFile()],
        ];
    }

    public function test_paths_root_is_the_project_root(): void
    {
        $this->assertSame($this->projectRoot, Paths::root());
        $this->assertFileExists(Paths::root().'/composer.json');
    }

    #[DataProvider('expectedDirectories')]
    public function test_derived_directories_exist(string $path): void
    {
        $this->assertDirectoryExists($path);
    }

    #[DataProvider('writablePaths')]
    public function test_writable_paths_live_under_storage(string $path): void
    {
        $this->assertStringStartsWith(
            $this->projectRoot.'/storage/',
            $this->normalise($path),
            sprintf('%s is written at runtime and must sit under storage/.', $path)
        );
    }

    #[DataProvider('writablePaths')]
    public function test_writable_paths_are_not_inside_the_source_tree(string $path): void
    {
        $this->assertStringNotContainsString(
            $this->projectRoot.'/app/',
            $this->normalise($path),
            sprintf('%s would write into app/, which is source and is not gitignored.', $path)
        );
    }

    /**
     * Paths is the only place allowed to know how deep it sits.
     *
     * Anything else walking up to the project root reintroduces the bug class, so the
     * pattern is banned outright rather than re-verified per call site. Module-local
     * `__DIR__.'/../Routes/api.php'` style lookups stay allowed: they address a sibling
     * directory, not the root.
     */
    public function test_no_source_file_counts_levels_up_to_the_project_root(): void
    {
        $allowed = Paths::root().'/app/Modules/Core/Infrastructure/Support/Paths.php';
        $offenders = [];

        /** @var SplFileInfo $file */
        foreach ($this->phpFilesUnder(Paths::root().'/app') as $file) {
            $path = $file->getPathname();

            if ($path === $allowed) {
                continue;
            }

            $contents = (string) file_get_contents($path);

            // dirname(__DIR__, N) or a chain of at least two `../` segments.
            if (preg_match('/dirname\(__DIR__|__DIR__\s*\.\s*.\/(?:\.\.\/){2,}/', $contents) === 1) {
                $offenders[] = str_replace(Paths::root().'/', '', $path);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "These files walk up to the project root by hand; use Paths instead:\n  ".
            implode("\n  ", $offenders)
        );
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function phpFilesUnder(string $directory): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
                yield $file;
            }
        }
    }

    /**
     * Collapse `.` and `..` lexically — the target may not exist yet, so realpath()
     * cannot be used.
     */
    private function normalise(string $path): string
    {
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '..') {
                array_pop($segments);

                continue;
            }

            if ($segment !== '.' && $segment !== '') {
                $segments[] = $segment;
            }
        }

        return '/'.implode('/', $segments);
    }
}
