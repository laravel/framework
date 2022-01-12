<?php

namespace Illuminate\Foundation\Console;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\RedirectController;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\ViewController;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Terminal;

class RouteListCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'route:list';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     */
    protected static $defaultName = 'route:list';

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
    protected $headers = ['Domain', 'Method', 'URI', 'Name', 'Action', 'Middleware', 'File'];

    /**
     * The terminal width resolver callback.
     *
     * @var \Closure|null
     */
    protected static $terminalWidthResolver;

    /**
     * Different types of routes.
     */
    const TYPE_ROUTE = 0;
    const TYPE_VIEW = 1;
    const TYPE_REDIRECT = 2;

    /**
     * The verb colors for the command.
     *
     * @var array
     */
    protected $verbColors = [
        'ANY' => 'red',
        'GET' => 'blue',
        'HEAD' => '#6C7280',
        'OPTIONS' => '#6C7280',
        'POST' => 'yellow',
        'PUT' => 'yellow',
        'PATCH' => 'yellow',
        'DELETE' => 'red',
    ];

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
        $reflection = $this->resolveReflection($route);
        $type = $this->resolveType($route, $reflection);

        $file = match ($type) {
            self::TYPE_VIEW => str_replace(base_path().DIRECTORY_SEPARATOR, '', resource_path('views'.DIRECTORY_SEPARATOR.$route->defaults['view'].'.blade.php')),
            self::TYPE_REDIRECT => $route->defaults['destination'].' '.$route->defaults['status'],
            self::TYPE_ROUTE => str_replace(base_path().DIRECTORY_SEPARATOR, '', $reflection->getFileName()).':'.$reflection->getStartLine()
        };

        return $this->filterRoute([
            'domain' => $route->domain(),
            'method' => implode('|', $route->methods()),
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'action' => ltrim($route->getActionName(), '\\'),
            'middleware' => $this->getMiddleware($route),
            'file' => $file,
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
        $routes = collect($routes);

        $this->output->writeln(
            $this->option('json') ? $this->asJson($routes) : $this->forCli($routes)
        );
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
        return array_map('strtolower', $this->headers);
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
     * Convert the given routes to JSON.
     *
     * @param  \Illuminate\Support\Collection  $routes
     * @return string
     */
    protected function asJson($routes)
    {
        return $routes
            ->map(function ($route) {
                $route['middleware'] = empty($route['middleware']) ? [] : explode("\n", $route['middleware']);

                return $route;
            })
            ->values()
            ->toJson();
    }

    /**
     * Convert the given routes to regular CLI output.
     *
     * @param  \Illuminate\Support\Collection  $routes
     * @return array
     */
    protected function forCli($routes)
    {
        $routes = $routes->map(
            fn ($route) => array_merge($route, [
                'action' => $this->formatActionForCli($route),
                'method' => $route['method'] == 'GET|HEAD|POST|PUT|PATCH|DELETE|OPTIONS' ? 'ANY' : $route['method'],
                'uri' => $route['domain'] ? ($route['domain'].'/'.$route['uri']) : $route['uri'],
            ]),
        );

        $maxMethod = mb_strlen($routes->max('method'));

        $terminalWidth = $this->getTerminalWidth();

        return $routes->map(function ($route) use ($maxMethod, $terminalWidth) {
            [
                'action' => $action,
                'domain' => $domain,
                'method' => $method,
                'middleware' => $middleware,
                'uri' => $uri,
                'file' => $file,
            ] = $route;

            $middleware = Str::of($middleware)->explode("\n")->filter()->whenNotEmpty(
                fn ($collection) => $collection->map(
                    fn ($middleware) => sprintf('         %s⇂ %s', str_repeat(' ', $maxMethod), $middleware)
                )
            )->implode("\n");

            $file = sprintf('         %s└ %s', str_repeat(' ', $maxMethod), $file);

            $spaces = str_repeat(' ', max($maxMethod + 6 - mb_strlen($method), 0));

            $dots = str_repeat('.', max(
                $terminalWidth - mb_strlen($method.$spaces.$uri.$action) - 6 - ($action ? 1 : 0), 0
            ));

            $dots = empty($dots) ? $dots : " $dots";

            if ($action && ! $this->output->isVerbose() && mb_strlen($method.$spaces.$uri.$action.$dots) > ($terminalWidth - 6)) {
                $action = substr($action, 0, $terminalWidth - 7 - mb_strlen($method.$spaces.$uri.$dots)).'…';
            }

            $method = Str::of($method)->explode('|')->map(
                fn ($method) => sprintf('<fg=%s>%s</>', $this->verbColors[$method] ?? 'default', $method),
            )->implode('<fg=#6C7280>|</>');

            return [sprintf(
                '  <fg=white;options=bold>%s</> %s<fg=white>%s</><fg=#6C7280>%s %s</>',
                $method,
                $spaces,
                preg_replace('#({[^}]+})#', '<fg=yellow>$1</>', $uri),
                $dots,
                str_replace('   ', ' › ', $action),
            ),
                $this->output->isVerbose() && ! empty($middleware) ? "<fg=#6C7280>$middleware</>" : null,
                $this->output->isVerbose() && ! empty($file) ? "<fg=#6C7280>$file</>" : null, ];
        })->flatten()->filter()->prepend('')->push('')->toArray();
    }

    /**
     * Return a reflection object for the
     * code executing on a given route.
     *
     * @param  Route  $route
     * @return ReflectionFunctionAbstract
     */
    protected function resolveReflection(Route $route): ReflectionFunctionAbstract
    {
        if (! $route->getAction('controller')) {
            $closure = $route->getAction('uses');

            return new ReflectionFunction($closure);
        }

        [$class, $method] = Str::parseCallback($route->getAction('controller'), '__invoke');

        return new ReflectionMethod($class, $method);
    }

    /**
     * Resolve the type of route we are doing.
     *
     * @param  Route  $route
     * @param  mixed  $reflection
     * @return self::TYPE_REDIRECT|self::TYPE_VIEW|self::TYPE_ROUTE
     */
    protected function resolveType(Route $route, $reflection)
    {
        $class = null;

        if ($reflection instanceof ReflectionClass) {
            $class = $reflection;
        }
        if ($reflection instanceof ReflectionMethod) {
            $class = $reflection->getDeclaringClass();
        }

        if ($class && $class->getName() === RedirectController::class) {
            return self::TYPE_REDIRECT;
        }

        if ($class && $class->getName() === ViewController::class) {
            return self::TYPE_VIEW;
        }

        return self::TYPE_ROUTE;
    }

    /**
     * Get the formatted action for display on the CLI.
     *
     * @param  array  $route
     * @return string
     */
    protected function formatActionForCli($route)
    {
        ['action' => $action, 'name' => $name] = $route;

        if ($action === 'Closure' || $action === ViewController::class) {
            return $name;
        }

        $name = $name ? "$name   " : null;

        $rootControllerNamespace = $this->laravel[UrlGenerator::class]->getRootControllerNamespace()
            ?? ($this->laravel->getNamespace().'Http\\Controllers');

        if (str_starts_with($action, $rootControllerNamespace)) {
            return $name.substr($action, mb_strlen($rootControllerNamespace) + 1);
        }

        $actionClass = explode('@', $action)[0];

        if (class_exists($actionClass) && str_starts_with((new ReflectionClass($actionClass))->getFilename(), base_path('vendor'))) {
            $actionCollection = collect(explode('\\', $action));

            return $name.$actionCollection->take(2)->implode('\\').'   '.$actionCollection->last();
        }

        return $name.$action;
    }

    /**
     * Get the terminal width.
     *
     * @return int
     */
    public static function getTerminalWidth()
    {
        return is_null(static::$terminalWidthResolver)
            ? (new Terminal)->getWidth()
            : call_user_func(static::$terminalWidthResolver);
    }

    /**
     * Set a callback that should be used when resolving the terminal width.
     *
     * @param  \Closure|null  $callback
     * @return void
     */
    public static function resolveTerminalWidthUsing($resolver)
    {
        static::$terminalWidthResolver = $resolver;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
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
