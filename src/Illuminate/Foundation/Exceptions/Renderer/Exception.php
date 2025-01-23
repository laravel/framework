<?php

namespace Illuminate\Foundation\Exceptions\Renderer;

use Closure;
use Composer\Autoload\ClassLoader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

class Exception
{
    /**
     * The "flattened" exception instance.
     *
     * @var \Symfony\Component\ErrorHandler\Exception\FlattenException
     */
    protected $exception;

    /**
     * The current request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The exception listener instance.
     *
     * @var \Illuminate\Foundation\Exceptions\Renderer\Listener
     */
    protected $listener;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    public $files;

    /**
     * The application's base path.
     *
     * @var string
     */
    protected $basePath;

    /**
     * The application's base path.
     *
     * @var string
     */
    protected $classmapPath;

    /**
     * Creates a new exception renderer instance.
     *
     * @param  \Symfony\Component\ErrorHandler\Exception\FlattenException  $exception
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Foundation\Exceptions\Renderer\Listener  $listener
     * @param  string  $basePath
     * @return void
     */
    public function __construct(FlattenException $exception, Request $request, Listener $listener, Filesystem $files, string $basePath, string $classmapPath)
    {
        $this->exception = $exception;
        $this->request = $request;
        $this->listener = $listener;
        $this->files = $files;
        $this->basePath = $basePath;
        $this->classmapPath = $classmapPath;
    }

    /**
     * Get the exception title.
     *
     * @return string
     */
    public function title()
    {
        return $this->exception->getStatusText();
    }

    /**
     * Get the exception message.
     *
     * @return string
     */
    public function message()
    {
        return $this->exception->getMessage();
    }

    /**
     * Get the exception class name.
     *
     * @return string
     */
    public function class()
    {
        return $this->exception->getClass();
    }

    /**
     * Get the first "non-vendor" frame index.
     *
     * @return int
     */
    public function defaultFrame()
    {
        $key = array_search(false, array_map(function (Frame $frame) {
            return $frame->isFromVendor();
        }, $this->frames()->all()));

        return $key === false ? 0 : $key;
    }

    /**
     * Get the exception's frames.
     *
     * @return \Illuminate\Support\Collection<int, Frame>
     */
    public function frames()
    {
        $classMap = once(fn () => $this->getClassmap());

        $trace = array_values(array_filter(
            $this->exception->getTrace(), fn ($trace) => isset($trace['file']),
        ));

        if (($trace[1]['class'] ?? '') === HandleExceptions::class) {
            array_shift($trace);
            array_shift($trace);
        }

        return new Collection(array_map(
            fn (array $trace) => new Frame($this->exception, $classMap, $trace, $this->basePath), $trace,
        ));
    }

    /**
     * Get the current classmap.
     *
     * @return array
     */
    public function getClassmap(): array
    {
        if (! $this->files->exists($this->classmapPath)) {
            $this->buildClassmap();
        }

        return $this->files->exists($this->classmapPath) ?
            $this->files->getRequire($this->classmapPath) : [];

        return [];
    }

    /**
     * Build the classmap and write it to disk.
     *
     * @return array
     */
    public function buildClassmap()
    {
        $this->write(array_map(function ($path) {
            return (string) realpath($path);
        }, array_values(ClassLoader::getRegisteredLoaders())[0]->getClassMap()));
    }

    /**
     * Write the given classmap array to disk.
     *
     * @param  array  $classmap
     * @return void
     *
     * @throws \Exception
     */
    protected function write(array $classmap)
    {
        if (! is_writable($dirname = dirname($this->classmapPath))) {
            throw new \Exception("The {$dirname} directory must be present and writable.");
        }

        $this->files->replace(
            $this->classmapPath, '<?php return '.var_export($classmap, true).';'
        );
    }

    /**
     * Get the exception's request instance.
     *
     * @return \Illuminate\Http\Request
     */
    public function request()
    {
        return $this->request;
    }

    /**
     * Get the request's headers.
     *
     * @return array<string, string>
     */
    public function requestHeaders()
    {
        return array_map(function (array $header) {
            return implode(', ', $header);
        }, $this->request()->headers->all());
    }

    /**
     * Get the request's body parameters.
     *
     * @return string|null
     */
    public function requestBody()
    {
        if (empty($payload = $this->request()->all())) {
            return null;
        }

        $json = (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return str_replace('\\', '', $json);
    }

    /**
     * Get the application's route context.
     *
     * @return array<string, string>
     */
    public function applicationRouteContext()
    {
        $route = $this->request()->route();

        return $route ? array_filter([
            'controller' => $route->getActionName(),
            'route name' => $route->getName() ?: null,
            'middleware' => implode(', ', array_map(function ($middleware) {
                return $middleware instanceof Closure ? 'Closure' : $middleware;
            }, $route->gatherMiddleware())),
        ]) : [];
    }

    /**
     * Get the application's route parameters context.
     *
     * @return array<string, mixed>|null
     */
    public function applicationRouteParametersContext()
    {
        $parameters = $this->request()->route()?->parameters();

        return $parameters ? json_encode(array_map(
            fn ($value) => $value instanceof Model ? $value->withoutRelations() : $value,
            $parameters
        ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : null;
    }

    /**
     * Get the application's SQL queries.
     *
     * @return array<int, array{connectionName: string, time: float, sql: string}>
     */
    public function applicationQueries()
    {
        return array_map(function (array $query) {
            $sql = $query['sql'];

            foreach ($query['bindings'] as $binding) {
                $sql = match (gettype($binding)) {
                    'integer', 'double' => preg_replace('/\?/', $binding, $sql, 1),
                    'NULL' => preg_replace('/\?/', 'NULL', $sql, 1),
                    default => preg_replace('/\?/', "'$binding'", $sql, 1),
                };
            }

            return [
                'connectionName' => $query['connectionName'],
                'time' => $query['time'],
                'sql' => $sql,
            ];
        }, $this->listener->queries());
    }
}
