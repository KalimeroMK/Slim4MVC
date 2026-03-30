<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Core\Infrastructure\Cache\CacheManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to clear application cache.
 */
final class CacheClearCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Clear the application cache')
            ->setHelp('This command clears all cached data or by specific tags')
            ->addOption(
                'tag',
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Clear cache by tag (can be used multiple times)'
            )
            ->addOption(
                'driver',
                'd',
                InputOption::VALUE_REQUIRED,
                'Cache driver to clear (file, redis, null)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $driver = $input->getOption('driver');
        $tags = $input->getOption('tag');

        try {
            $cache = new CacheManager()->driver($driver);

            if ($tags !== []) {
                // Clear by tags
                foreach ($tags as $tag) {
                    $cache->flushByTag($tag);
                    $output->writeln(sprintf('<info>Cache cleared for tag: %s</info>', $tag));
                }
            } else {
                // Clear all
                $cache->clear();
                $output->writeln('<info>Application cache cleared successfully.</info>');
            }

            return Command::SUCCESS;
        } catch (\Throwable $throwable) {
            $output->writeln(sprintf('<error>Failed to clear cache: %s</error>', $throwable->getMessage()));

            return Command::FAILURE;
        }
    }
}
