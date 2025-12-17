<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'route:conflicts')]
class RouteConflictsCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'route:conflicts {--json : Output in JSON format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect route conflicts where multiple routes could match the same request';

    /**
     * The router instance.
     *
     * @var \Illuminate\Routing\Router
     */
    protected Router $router;

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function __construct(Router $router)
    {
        parent::__construct();

        $this->router = $router;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $routes = collect($this->router->getRoutes()->getRoutes());
        $conflicts = $this->findConflicts($routes);

        if ($conflicts->isEmpty()) {
            $this->info('No route conflicts detected.');

            return self::SUCCESS;
        }

        $this->error('Route conflicts found:');

        $this->line($this->option('json') ? $this->asJson($conflicts) : $this->forCli($conflicts));

        return self::FAILURE;
    }

    /**
     * Find route conflicts from a collection of routes.
     *
     * @param  \Illuminate\Support\Collection  $routes
     * @return \Illuminate\Support\Collection
     */
    protected function findConflicts(Collection $routes): Collection
    {
        $conflicts = collect();

        $routes->each(function (Route $earlier, $i) use ($routes, $conflicts) {
            $routes->slice($i + 1)->each(function (Route $later) use ($earlier, $conflicts) {
                if (! array_intersect($earlier->methods(), $later->methods())) {
                    return;
                }

                if ($this->routesConflict($earlier, $later)) {
                    $conflicts->push([
                        'methods' => implode(',', $earlier->methods()),
                        'earlier' => $this->routeDetails($earlier),
                        'later' => $this->routeDetails($later),
                    ]);
                }
            });
        });

        return $conflicts;
    }

    /**
     * Determine if two routes conflict.
     *
     * @param  \Illuminate\Routing\Route  $earlier
     * @param  \Illuminate\Routing\Route  $later
     * @return bool
     */
    protected function routesConflict(Route $earlier, Route $later): bool
    {
        $aParts = explode('/', $earlier->uri());
        $bParts = explode('/', $later->uri());

        if (count($aParts) !== count($bParts)) {
            return false;
        }

        foreach ($aParts as $i => $segmentA) {
            $segmentB = $bParts[$i];

            if (! $this->isParameter($segmentA) && ! $this->isParameter($segmentB) && $segmentA !== $segmentB) {
                return false;
            }

            if ($this->isParameter($segmentA) && ! $this->isParameter($segmentB)) {
                if (! $this->parameterMatchesLiteral($earlier, $segmentA, $segmentB)) {
                    return false;
                }

                continue;
            }

            if (! $this->isParameter($segmentA) && $this->isParameter($segmentB)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a route parameter matches a literal segment.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  string  $parameter
     * @param  string  $literal
     * @return bool
     */
    protected function parameterMatchesLiteral(Route $route, string $parameter, string $literal): bool
    {
        $paramName = trim($parameter, '{}');
        $regex = $route->wheres[$paramName] ?? null;

        if (! $regex) {
            return true;
        }

        return preg_match('/^'.$regex.'$/', $literal) === 1;
    }

    /**
     * Determine if a segment is a route parameter.
     *
     * @param  string  $segment
     * @return bool
     */
    protected function isParameter(string $segment): bool
    {
        return preg_match('/^\{[^}]+\}$/', $segment) === 1;
    }

    /**
     * Get detailed route information.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return array
     */
    protected function routeDetails(Route $route): array
    {
        return [
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'action' => $route->getActionName(),
        ];
    }

    /**
     * Convert conflicts to JSON format.
     *
     * @param  \Illuminate\Support\Collection  $conflicts
     * @return string
     */
    protected function asJson(Collection $conflicts): string
    {
        return $conflicts->values()->toJson(JSON_PRETTY_PRINT);
    }

    /**
     * Format conflicts for CLI output.
     *
     * @param  \Illuminate\Support\Collection  $conflicts
     * @return string
     */
    protected function forCli(Collection $conflicts): string
    {
        return $conflicts->map(function ($c) {
            return "[{$c['methods']}]\n"
                ."  Earlier: {$c['earlier']['uri']} ({$c['earlier']['action']})\n"
                ."  Later:   {$c['later']['uri']} ({$c['later']['action']})";
        })->implode("\n\n");
    }
}
