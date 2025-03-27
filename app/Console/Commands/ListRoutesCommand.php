<?php

namespace App\Console\Commands;

use Slim\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListRoutesCommand extends Command
{
    protected static $defaultName = 'list-routes';

    private $app;

    public function __construct(App $app)
    {
        parent::__construct();

        $this->app = $app;
    }

    protected function configure()
    {
        $this->setDescription('Lists all registered routes in the Slim application.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Access routes from the app's route collector
        $routeCollector = $this->app->getRouteCollector();
        $routes = $routeCollector->getRoutes();

        // Output the list of routes
        $output->writeln("Registered Routes:");

        foreach ($routes as $route) {
            $methods = implode(', ', $route->getMethods());
            $path = $route->getPattern();
            $output->writeln("Method: $methods | Path: $path");
        }

        return Command::SUCCESS;
    }
}
