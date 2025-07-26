<?php

namespace Illuminate\Routing;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\AttributeRouteController;
use Illuminate\Routing\Attributes\Group;
use Illuminate\Routing\Attributes\Patch;
use Illuminate\Routing\Attributes\RouteAttribute;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

class AttributeRouteRegistrar
{
    /**
     * The PSR-4 autoloading map from Composer.
     *
     * @var array
     */
    protected $psr4Paths;

    /**
     * Create a new AttributeRouteRegistrar instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function __construct(protected Application $app, protected Router $router)
    {
        $this->psr4Paths = $this->getPsr4Paths();
    }

    /**
     * Scan the given directories and register any found attribute-based routes.
     *
     * @param  string  ...$controllerDirectories
     * @return void
     */
    public function register(...$controllerDirectories)
    {
        if (empty($controllerDirectories)) {
            return;
        }

        $finder = (new Finder)->files()->in($controllerDirectories)->name('*.php');

        foreach ($finder as $file) {
            $className = $this->getClassFromFile($file->getRealPath());

            if ($className && class_exists($className) && is_a($className, AttributeRouteController::class, true)) {
                $this->registerControllerRoutes($className);
            }
        }
    }

    /**
     * Registers all routes for a given controller class.
     *
     * @param  string  $controllerClassName
     * @return void
     */
    public function registerControllerRoutes($controllerClassName)
    {
        $reflectionClass = new ReflectionClass($controllerClassName);

        $groupAttributes = $this->getGroupAttributes($reflectionClass) ?? [];

        $this->router->group($groupAttributes, function (Router $router) use ($reflectionClass) {
            foreach ($reflectionClass->getMethods() as $method) {
                $attributes = $method->getAttributes(RouteAttribute::class, \ReflectionAttribute::IS_INSTANCEOF);

                foreach ($attributes as $attribute) {
                    try {
                        $instance = $attribute->newInstance();
                        $route = $router->addRoute(
                            $instance->methods,
                            $instance->path,
                            [$reflectionClass->getName(), $method->getName()]
                        );
                        $this->applyRouteOptions($route, $instance);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }
        });
    }

    /**
     * Applies all options from a RouteAttribute instance to a route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  \Illuminate\Routing\Attributes\RouteAttribute  $instance
     * @return void
     */
    protected function applyRouteOptions(Route $route, RouteAttribute $instance): void
    {
        if ($instance->name) {
            $route->name($instance->name);
        }
        if ($instance->middleware) {
            $route->middleware($instance->middleware);
        }
        if ($instance->where) {
            $route->where($instance->where);
        }

        // Mark the route for the route:list command
        $route->setAction(array_merge($route->getAction(), ['is_attribute_route' => true]));
    }

    /**
     * Gets the properties from a single #[Group] attribute on a class.
     *
     * @param  \ReflectionClass  $reflectionClass
     * @return array|null
     */
    protected function getGroupAttributes(ReflectionClass $reflectionClass): ?array
    {
        $attributes = $reflectionClass->getAttributes(Group::class);

        if (count($attributes) === 0) {
            return null;
        }

        try {
            /** @var Group $group */
            $group = $attributes[0]->newInstance();

            return array_filter([
                'prefix' => $group->prefix,
                'middleware' => $group->middleware,
                'as' => $group->name,
                'where' => $group->where,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    /**
     * Derive the fully qualified class name from a file path.
     *
     * This implementation uses the project's Composer PSR-4 map to determine
     * the class name, making it compatible with any autoloaded directory.
     *
     * @param  string  $path
     * @return string|null
     */
    protected function getClassFromFile($path)
    {
        foreach ($this->psr4Paths as $namespace => $paths) {
            foreach ((array) $paths as $psr4Path) {
                if (Str::startsWith($path, $psr4Path)) {
                    $relativePath = Str::of($path)
                        ->after($psr4Path)
                        ->trim(DIRECTORY_SEPARATOR)
                        ->replace(['/', '.php'], ['\\', ''])
                        ->toString();

                    return $namespace.$relativePath;
                }
            }
        }

        return null;
    }

    /**
     * Load the Composer PSR-4 autoloading map.
     *
     * This map is used to convert a file path into a fully qualified class name.
     *
     * @return array
     */
    protected function getPsr4Paths()
    {
        $composerPath = $this->app->basePath('vendor/composer/autoload_psr4.php');

        return file_exists($composerPath) ? require $composerPath : [];
    }
}
