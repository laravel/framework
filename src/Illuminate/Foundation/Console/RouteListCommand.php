<?php

namespace Illuminate\Foundation\Console;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class RouteListCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'route:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all registered routes';

    /**
     * The router instance.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * The table headers for the command.
     *
     * @var string[]
     */
    protected $headers = ['Domain', 'Method', 'URI', 'Name', 'Action', 'Middleware'];

    /**
     * The columns to display when using the "compact" flag.
     *
     * @var string[]
     */
    protected $compactColumns = ['method', 'uri', 'action'];

    /**
     * Create a new route command instance.
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
     * @return void
     */
    public function handle()
    {
        $this->router->flushMiddlewareGroups();

        if (empty($this->router->getRoutes())) {
            return $this->error("Your application doesn't have any routes.");
        }

        if (empty($routes = $this->getRoutes())) {
            return $this->error("Your application doesn't have any routes matching the given criteria.");
        }

        $this->displayRoutes($routes);
    }

    /**
     * Compile the routes into a displayable format.
     *
     * @return array
     */
    protected function getRoutes()
    {
        $routes = collect($this->router->getRoutes())->map(function ($route) {
            return $this->getRouteInformation($route);
        })->filter()->all();

        if (($sort = $this->option('sort')) !== 'precedence') {
            $routes = $this->sortRoutes($sort, $routes);
        }

        if ($this->option('reverse')) {
            $routes = array_reverse($routes);
        }

        return $this->pluckColumns($routes);
    }

    /**
     * Get the route information for a given route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return array
     */
    protected function getRouteInformation(Route $route)
    {
        return $this->filterRoute([
            'domain' => $route->domain(),
            'method' => implode('|', $route->methods()),
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'action' => ltrim($route->getActionName(), '\\'),
            'middleware' => $this->getMiddleware($route),
        ]);
    }

    /**
     * Sort the routes by a given element.
     *
     * @param  string  $sort
     * @param  array  $routes
     * @return array
     */
    protected function sortRoutes($sort, array $routes)
    {
        return Arr::sort($routes, function ($route) use ($sort) {
            return $route[$sort];
        });
    }

    /**
     * Remove unnecessary columns from the routes.
     *
     * @param  array  $routes
     * @return array
     */
    protected function pluckColumns(array $routes)
    {
        return array_map(function ($route) {
            return Arr::only($route, $this->getColumns());
        }, $routes);
    }

    /**
     * Display the route information on the console.
     *
     * @param  array  $routes
     * @return void
     */
    protected function displayRoutes(array $routes)
    {
        if ($this->option('json')) {
            $this->line(json_encode(array_values($routes)));

            return;
        }

        $this->table($this->getHeaders(), $routes);
    }

    /**
     * Get the middleware for the route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return string
     */
    protected function getMiddleware($route)
    {
        return collect($this->router->gatherRouteMiddleware($route))->map(function ($middleware) {
            return $middleware instanceof Closure ? 'Closure' : $middleware;
        })->implode("\n");
    }

    /**
     * Filter the route by URI and / or name.
     *
     * @param  array  $route
     * @return array|null
     */
    protected function filterRoute(array $route)
    {
        if (($this->option('name') && ! Str::contains($route['name'], $this->option('name'))) ||
             $this->option('path') && ! Str::contains($route['uri'], $this->option('path')) ||
             $this->option('method') && ! Str::contains($route['method'], strtoupper($this->option('method')))) {
            return;
        }

        if ($this->option('except-path')) {
            foreach (explode(',', $this->option('except-path')) as $path) {
                if (Str::contains($route['uri'], $path)) {
                    return;
                }
            }
        }

        return $route;
    }

    /**
     * Get the table headers for the visible columns.
     *
     * @return array
     */
    protected function getHeaders()
    {
        return Arr::only($this->headers, array_keys($this->getColumns()));
    }

    /**
     * Get the column names to show (lowercase table headers).
     *
     * @return array
     */
    protected function getColumns()
    {
        $availableColumns = array_map('strtolower', $this->headers);

        if ($this->option('compact')) {
            return array_intersect($availableColumns, $this->compactColumns);
        }

        if ($columns = $this->option('columns')) {
            return array_intersect($availableColumns, $this->parseColumns($columns));
        }

        return $availableColumns;
    }

    /**
     * Parse the column list.
     *
     * @param  array  $columns
     * @return array
     */
    protected function parseColumns(array $columns)
    {
        $results = [];

        foreach ($columns as $i => $column) {
            if (Str::contains($column, ',')) {
                $results = array_merge($results, explode(',', $column));
            } else {
                $results[] = $column;
            }
        }

        return array_map('strtolower', $results);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['columns', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Columns to include in the route table'],
            ['compact', 'c', InputOption::VALUE_NONE, 'Only show method, URI and action columns'],
            ['json', null, InputOption::VALUE_NONE, 'Output the route list as JSON'],
            ['method', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by method'],
            ['name', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by name'],
            ['path', null, InputOption::VALUE_OPTIONAL, 'Only show routes matching the given path pattern'],
            ['except-path', null, InputOption::VALUE_OPTIONAL, 'Do not display the routes matching the given path pattern'],
            ['reverse', 'r', InputOption::VALUE_NONE, 'Reverse the ordering of the routes'],
            ['sort', null, InputOption::VALUE_OPTIONAL, 'The column (precedence, domain, method, uri, name, action, middleware) to sort by', 'uri'],
        ];
    }
}
