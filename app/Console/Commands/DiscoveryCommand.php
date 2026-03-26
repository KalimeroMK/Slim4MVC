<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Core\Infrastructure\DI\OptimizedDiscovery;
use App\Modules\Core\Infrastructure\Validation\EnvironmentValidator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command for managing dependency discovery.
 *
 * Usage:
 *   php slim discovery --stats      # Show discovery statistics
 *   php slim discovery --refresh    # Refresh the cache
 *   php slim discovery --clear      # Clear the cache
 *   php slim discovery --validate   # Validate environment
 *   php slim discovery --warm       # Warm the cache
 */
class DiscoveryCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('discovery')
            ->setDescription('Manage dependency auto-discovery')
            ->setHelp(<<<'HELP'
The <info>discovery</info> command manages the auto-discovery system for dependency injection.

<comment>Examples:</comment>
  <info>php slim discovery --stats</info>      Show discovery statistics
  <info>php slim discovery --refresh</info>    Regenerate the cache
  <info>php slim discovery --clear</info>      Clear the cache
  <info>php slim discovery --validate</info>   Validate environment configuration
  <info>php slim discovery --warm</info>       Warm the cache (production)
HELP
            )
            ->addOption('stats', 's', InputOption::VALUE_NONE, 'Show discovery statistics')
            ->addOption('refresh', 'r', InputOption::VALUE_NONE, 'Refresh the cache')
            ->addOption('clear', 'c', InputOption::VALUE_NONE, 'Clear the cache')
            ->addOption('validate', null, InputOption::VALUE_NONE, 'Validate environment')
            ->addOption('warm', 'w', InputOption::VALUE_NONE, 'Warm the cache (production)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Validate environment if requested
        if ($input->getOption('validate')) {
            return $this->handleValidate($output);
        }

        $discovery = new OptimizedDiscovery();

        // Handle cache warming (for production deployments)
        if ($input->getOption('warm')) {
            return $this->handleWarm($discovery, $output);
        }

        // Handle cache clearing
        if ($input->getOption('clear')) {
            return $this->handleClear($discovery, $output);
        }

        // Handle cache refresh
        if ($input->getOption('refresh')) {
            return $this->handleRefresh($discovery, $output);
        }

        // Default: show stats
        return $this->handleStats($discovery, $output);
    }

    /**
     * Handle environment validation.
     */
    private function handleValidate(OutputInterface $output): int
    {
        $output->writeln('');
        $output->writeln('🔒 <comment>Environment Validation</comment>');
        $output->writeln('═══════════════════════════════════════════════════════════════');
        $output->writeln('');

        try {
            EnvironmentValidator::validate();
        } catch (\App\Modules\Core\Infrastructure\Validation\ConfigurationException $e) {
            $output->writeln('<error>' . $e->getDetailedMessage() . '</error>');
            $output->writeln('');

            return Command::FAILURE;
        }

        $summary = EnvironmentValidator::getSummary();
        $warnings = EnvironmentValidator::getWarnings();

        $output->writeln('✅ <info>Environment configuration is valid</info>');
        $output->writeln('');

        // Show configuration table
        $table = new Table($output);
        $table->setHeaders(['Setting', 'Value']);
        $table->setRows([
            ['Environment', $summary['environment']],
            ['Is Production', $summary['is_production'] ? 'Yes' : 'No'],
            ['JWT Configured', $summary['jwt_configured'] ? 'Yes' : 'No'],
            ['JWT Secret Length', $summary['jwt_secret_length'] . ' chars'],
            ['DB Connection', $summary['db_connection']],
            ['DB Configured', $summary['db_configured'] ? 'Yes' : 'No'],
            ['Cache Driver', $summary['cache_driver']],
            ['Session Driver', $summary['session_driver']],
        ]);
        $table->render();

        // Show warnings if any
        if (!empty($warnings)) {
            $output->writeln('');
            $output->writeln('⚠️  <comment>Warnings:</comment>');
            foreach ($warnings as $warning) {
                $output->writeln("   • {$warning}");
            }
        }

        $output->writeln('');

        return Command::SUCCESS;
    }

    /**
     * Handle cache warming.
     */
    private function handleWarm(OptimizedDiscovery $discovery, OutputInterface $output): int
    {
        $output->writeln('');
        $output->writeln('🔥 <comment>Warming Discovery Cache...</comment>');
        $output->writeln('');

        $result = $discovery->warmCache();

        $output->writeln(sprintf(
            '✅ <info>Cache warmed successfully in %sms</info>',
            $result['duration']
        ));
        $output->writeln(sprintf(
            '   Found <info>%d</info> interface implementations',
            $result['count']
        ));
        $output->writeln('');

        return Command::SUCCESS;
    }

    /**
     * Handle cache clearing.
     */
    private function handleClear(OptimizedDiscovery $discovery, OutputInterface $output): int
    {
        $output->writeln('');
        $output->writeln('🗑️  <comment>Clearing Discovery Cache...</comment>');
        $output->writeln('');

        $success = $discovery->clearCache();

        if ($success) {
            $output->writeln('✅ <info>Cache cleared successfully</info>');
        } else {
            $output->writeln('⚠️  <comment>No cache file to clear</comment>');
        }

        $output->writeln('');

        return Command::SUCCESS;
    }

    /**
     * Handle cache refresh.
     */
    private function handleRefresh(OptimizedDiscovery $discovery, OutputInterface $output): int
    {
        $output->writeln('');
        $output->writeln('🔄 <comment>Refreshing Discovery Cache...</comment>');
        $output->writeln('');

        $discovery->clearCache();
        $result = $discovery->warmCache();

        $output->writeln(sprintf(
            '✅ <info>Cache refreshed successfully in %sms</info>',
            $result['duration']
        ));
        $output->writeln(sprintf(
            '   Found <info>%d</info> interface implementations',
            $result['count']
        ));
        $output->writeln('');

        return Command::SUCCESS;
    }

    /**
     * Handle stats display.
     */
    private function handleStats(OptimizedDiscovery $discovery, OutputInterface $output): int
    {
        $stats = $discovery->getStats();

        $output->writeln('');
        $output->writeln('🔍 <comment>Auto-Discovery Statistics</comment>');
        $output->writeln('═══════════════════════════════════════════════════════════════');
        $output->writeln('');

        // Status table
        $table = new Table($output);
        $table->setHeaders(['Metric', 'Value']);
        $table->setRows([
            ['Total Bindings', $stats['total_bindings']],
            ['Cache Enabled', $stats['cache_enabled'] ? 'Yes (production)' : 'No (development)'],
            ['Cache File', $stats['cache_file']],
            ['Cache Exists', $stats['cache_exists'] ? 'Yes' : 'No'],
            ['Cache Valid', $stats['cache_valid'] ? 'Yes' : 'No'],
            ['Environment', $stats['environment']],
        ]);
        $table->render();

        // Show sample bindings if any
        if (!empty($stats['sample_bindings'])) {
            $output->writeln('');
            $output->writeln('📋 <comment>Discovered Bindings (first 20):</comment>');
            $output->writeln('');

            $bindingsTable = new Table($output);
            $bindingsTable->setHeaders(['Interface', 'Implementation']);

            foreach ($stats['sample_bindings'] as $interface => $definition) {
                if (is_string($definition)) {
                    $implementation = $definition;
                } elseif (is_object($definition)) {
                    $implementation = $definition instanceof \ReflectionClass
                        ? $definition->getName()
                        : get_class($definition);
                } else {
                    $implementation = gettype($definition);
                }
                $shortInterface = $this->shortenClassName($interface);
                $shortImplementation = $this->shortenClassName($implementation);

                $bindingsTable->addRow([$shortInterface, $shortImplementation]);
            }

            $bindingsTable->render();

            if ($stats['total_bindings'] > 20) {
                $output->writeln('');
                $output->writeln(sprintf(
                    '   <comment>... and %d more bindings</comment>',
                    $stats['total_bindings'] - 20
                ));
            }
        }

        $output->writeln('');

        return Command::SUCCESS;
    }

    /**
     * Shorten class name for display.
     */
    private function shortenClassName(string $className): string
    {
        $parts = explode('\\', $className);

        if (count($parts) <= 3) {
            return $className;
        }

        return implode('\\', array_slice($parts, -3));
    }
}
