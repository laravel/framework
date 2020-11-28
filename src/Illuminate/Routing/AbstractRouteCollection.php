<?php

namespace Illuminate\Routing;

use ArrayIterator;
use Countable;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use IteratorAggregate;
use LogicException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;
use Symfony\Component\Routing\RouteCollection as SymfonyRouteCollection;

abstract class AbstractRouteCollection implements Countable, IteratorAggregate, RouteCollectionInterface
{
    /**
     * We define default method in auto routing.
     */
    protected $default_method_in_auto_routing = 'index';

    /**
     * Define default error message in auto routing.
     */
    protected $default_error_message = 'Not Found';

    /**
     * Handle the matched route.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Routing\Route|null  $route
     * @return \Illuminate\Routing\Route
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function handleMatchedRoute(Request $request, $route)
    {
        if (! is_null($route)) {
            return $route->bind($request);
        }

        // If no route was found we will now check if a matching route is specified by
        // another HTTP verb. If it is we will need to throw a MethodNotAllowed and
        // inform the user agent of which HTTP verb it should use for this route.
        $others = $this->checkForAlternateVerbs($request);

        if (count($others) > 0) {
            return $this->getRouteForMethods($request, $others);
        }

        // If no route was found we will now check in auto routing
        try {
            $this->autoRouting();
        } catch (\Throwable $th) {
            $this->printErrorPage($this->default_error_message, 404);
        }
    }

    /**
     * Auto routing
     * This for testing my new idea only.
     *
     * created by
     * teguhrijanandi02@gmail.com
     */

    protected function autoRouting()
    {
        $application = new Application;

        // define host from application
        $host = $_SERVER['HTTP_HOST'];

        // get request url
        // for example we have
        // url like this
        // http://localhost:8000/myfolder/myclass/mymethod
        // and the REQUEST_URI is /myfolder/myclass/mymethod
        $request_url = trim($_SERVER['REQUEST_URI'], '/');

        // define controller path
        // and the result is
        // \app\Http\Controllers\
        $controller_path = ucwords(ltrim($application->path(), DIRECTORY_SEPARATOR)).
                                DIRECTORY_SEPARATOR.
                                'Http'.
                                DIRECTORY_SEPARATOR.
                                'Controllers'.
                                DIRECTORY_SEPARATOR;

        // Explode REQUEST_URI
        // and the result is
        // Array ( [0] => myfolder [1] => myclass [2] => mymethod )
        // we define 0 is folder
        //           1 is class
        //           2 is method
        $explode = explode('/', $request_url);

        // and we read the class and method from last array

        if (count($explode) >= 3) {
            $path_to_call = $controller_path.$explode[0].DIRECTORY_SEPARATOR.$explode[count($explode) - 2];
            $class_to_call = $explode[count($explode) - 2];
            $method_to_call = $explode[count($explode) - 1];
        }

        if (count($explode) == 2) {
            $path_to_call = $controller_path.$explode[0].DIRECTORY_SEPARATOR.$explode[count($explode) - 2];
            $class_to_call = $explode[count($explode) - 2];
            $method_to_call = $explode[count($explode) - 1];
        }

        if (count($explode) == 1) {
            $path_to_call = $controller_path.$explode[0];
            $class_to_call = $explode[0];
            $method_to_call = $this->default_method_in_auto_routing;
        }

        // for url test
        // echo "path " . $path_to_call . "<br>";
        // echo "class " . $class_to_call . "<br>";
        // echo "method " . $method_to_call . '()';
        // die();

        // call controller and method
        $call_class = new $path_to_call;

        if(! method_exists($call_class, $method_to_call)) {
            return $this->printErrorPage($this->default_error_message, 404);
        }

        return $call_class->$method_to_call();
    }

    protected function printErrorPage($message, $code)
    {
        echo '
            <!DOCTYPE html>
            <html lang="en">
                <head>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">

                    <title>'.$code.' Error</title>

                    <!-- Fonts -->
                    <link rel="dns-prefetch" href="//fonts.gstatic.com">
                    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

                    <style>
                        /*! normalize.css v8.0.1 | MIT License | github.com/necolas/normalize.css */html{line-height:1.15;-webkit-text-size-adjust:100%}body{margin:0}a{background-color:transparent}code{font-family:monospace,monospace;font-size:1em}[hidden]{display:none}html{font-family:system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;line-height:1.5}*,:after,:before{box-sizing:border-box;border:0 solid #e2e8f0}a{color:inherit;text-decoration:inherit}code{font-family:Menlo,Monaco,Consolas,Liberation Mono,Courier New,monospace}svg,video{display:block;vertical-align:middle}video{max-width:100%;height:auto}.bg-white{--bg-opacity:1;background-color:#fff;background-color:rgba(255,255,255,var(--bg-opacity))}.bg-gray-100{--bg-opacity:1;background-color:#f7fafc;background-color:rgba(247,250,252,var(--bg-opacity))}.border-gray-200{--border-opacity:1;border-color:#edf2f7;border-color:rgba(237,242,247,var(--border-opacity))}.border-gray-400{--border-opacity:1;border-color:#cbd5e0;border-color:rgba(203,213,224,var(--border-opacity))}.border-t{border-top-width:1px}.border-r{border-right-width:1px}.flex{display:flex}.grid{display:grid}.hidden{display:none}.items-center{align-items:center}.justify-center{justify-content:center}.font-semibold{font-weight:600}.h-5{height:1.25rem}.h-8{height:2rem}.h-16{height:4rem}.text-sm{font-size:.875rem}.text-lg{font-size:1.125rem}.leading-7{line-height:1.75rem}.mx-auto{margin-left:auto;margin-right:auto}.ml-1{margin-left:.25rem}.mt-2{margin-top:.5rem}.mr-2{margin-right:.5rem}.ml-2{margin-left:.5rem}.mt-4{margin-top:1rem}.ml-4{margin-left:1rem}.mt-8{margin-top:2rem}.ml-12{margin-left:3rem}.-mt-px{margin-top:-1px}.max-w-xl{max-width:36rem}.max-w-6xl{max-width:72rem}.min-h-screen{min-height:100vh}.overflow-hidden{overflow:hidden}.p-6{padding:1.5rem}.py-4{padding-top:1rem;padding-bottom:1rem}.px-4{padding-left:1rem;padding-right:1rem}.px-6{padding-left:1.5rem;padding-right:1.5rem}.pt-8{padding-top:2rem}.fixed{position:fixed}.relative{position:relative}.top-0{top:0}.right-0{right:0}.shadow{box-shadow:0 1px 3px 0 rgba(0,0,0,.1),0 1px 2px 0 rgba(0,0,0,.06)}.text-center{text-align:center}.text-gray-200{--text-opacity:1;color:#edf2f7;color:rgba(237,242,247,var(--text-opacity))}.text-gray-300{--text-opacity:1;color:#e2e8f0;color:rgba(226,232,240,var(--text-opacity))}.text-gray-400{--text-opacity:1;color:#cbd5e0;color:rgba(203,213,224,var(--text-opacity))}.text-gray-500{--text-opacity:1;color:#a0aec0;color:rgba(160,174,192,var(--text-opacity))}.text-gray-600{--text-opacity:1;color:#718096;color:rgba(113,128,150,var(--text-opacity))}.text-gray-700{--text-opacity:1;color:#4a5568;color:rgba(74,85,104,var(--text-opacity))}.text-gray-900{--text-opacity:1;color:#1a202c;color:rgba(26,32,44,var(--text-opacity))}.uppercase{text-transform:uppercase}.underline{text-decoration:underline}.antialiased{-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}.tracking-wider{letter-spacing:.05em}.w-5{width:1.25rem}.w-8{width:2rem}.w-auto{width:auto}.grid-cols-1{grid-template-columns:repeat(1,minmax(0,1fr))}@-webkit-keyframes spin{0%{transform:rotate(0deg)}to{transform:rotate(1turn)}}@keyframes spin{0%{transform:rotate(0deg)}to{transform:rotate(1turn)}}@-webkit-keyframes ping{0%{transform:scale(1);opacity:1}75%,to{transform:scale(2);opacity:0}}@keyframes ping{0%{transform:scale(1);opacity:1}75%,to{transform:scale(2);opacity:0}}@-webkit-keyframes pulse{0%,to{opacity:1}50%{opacity:.5}}@keyframes pulse{0%,to{opacity:1}50%{opacity:.5}}@-webkit-keyframes bounce{0%,to{transform:translateY(-25%);-webkit-animation-timing-function:cubic-bezier(.8,0,1,1);animation-timing-function:cubic-bezier(.8,0,1,1)}50%{transform:translateY(0);-webkit-animation-timing-function:cubic-bezier(0,0,.2,1);animation-timing-function:cubic-bezier(0,0,.2,1)}}@keyframes bounce{0%,to{transform:translateY(-25%);-webkit-animation-timing-function:cubic-bezier(.8,0,1,1);animation-timing-function:cubic-bezier(.8,0,1,1)}50%{transform:translateY(0);-webkit-animation-timing-function:cubic-bezier(0,0,.2,1);animation-timing-function:cubic-bezier(0,0,.2,1)}}@media (min-width:640px){.sm\:rounded-lg{border-radius:.5rem}.sm\:block{display:block}.sm\:items-center{align-items:center}.sm\:justify-start{justify-content:flex-start}.sm\:justify-between{justify-content:space-between}.sm\:h-20{height:5rem}.sm\:ml-0{margin-left:0}.sm\:px-6{padding-left:1.5rem;padding-right:1.5rem}.sm\:pt-0{padding-top:0}.sm\:text-left{text-align:left}.sm\:text-right{text-align:right}}@media (min-width:768px){.md\:border-t-0{border-top-width:0}.md\:border-l{border-left-width:1px}.md\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}}@media (min-width:1024px){.lg\:px-8{padding-left:2rem;padding-right:2rem}}@media (prefers-color-scheme:dark){.dark\:bg-gray-800{--bg-opacity:1;background-color:#2d3748;background-color:rgba(45,55,72,var(--bg-opacity))}.dark\:bg-gray-900{--bg-opacity:1;background-color:#1a202c;background-color:rgba(26,32,44,var(--bg-opacity))}.dark\:border-gray-700{--border-opacity:1;border-color:#4a5568;border-color:rgba(74,85,104,var(--border-opacity))}.dark\:text-white{--text-opacity:1;color:#fff;color:rgba(255,255,255,var(--text-opacity))}.dark\:text-gray-400{--text-opacity:1;color:#cbd5e0;color:rgba(203,213,224,var(--text-opacity))}}
                    </style>

                    <style>
                        body {
                            font-family: "Nunito";
                        }
                    </style>
                </head>
                <body class="antialiased">
                    <div class="relative flex items-top justify-center min-h-screen bg-gray-100 dark:bg-gray-900 sm:items-center sm:pt-0">
                        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                            <div class="flex items-center pt-8 sm:justify-start sm:pt-0">
                                <div class="px-4 text-lg text-gray-500 border-r border-gray-400 tracking-wider">
                                    '.$code.'
                                </div>

                                <div class="ml-4 text-lg text-gray-500 uppercase tracking-wider">
                                    '.$message.'
                                </div>
                            </div>
                        </div>
                    </div>
                </body>
            </html>
        ';
    }

    /**
     * Determine if any routes match on another HTTP verb.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function checkForAlternateVerbs($request)
    {
        $methods = array_diff(Router::$verbs, [$request->getMethod()]);

        // Here we will spin through all verbs except for the current request verb and
        // check to see if any routes respond to them. If they do, we will return a
        // proper error response with the correct headers on the response string.
        return array_values(array_filter(
            $methods,
            function ($method) use ($request) {
                return ! is_null($this->matchAgainstRoutes($this->get($method), $request, false));
            }
        ));
    }

    /**
     * Determine if a route in the array matches the request.
     *
     * @param  \Illuminate\Routing\Route[]  $routes
     * @param  \Illuminate\Http\Request  $request
     * @param  bool  $includingMethod
     * @return \Illuminate\Routing\Route|null
     */
    protected function matchAgainstRoutes(array $routes, $request, $includingMethod = true)
    {
        [$fallbacks, $routes] = collect($routes)->partition(function ($route) {
            return $route->isFallback;
        });

        return $routes->merge($fallbacks)->first(function (Route $route) use ($request, $includingMethod) {
            return $route->matches($request, $includingMethod);
        });
    }

    /**
     * Get a route (if necessary) that responds when other available methods are present.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string[]  $methods
     * @return \Illuminate\Routing\Route
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function getRouteForMethods($request, array $methods)
    {
        if ($request->method() === 'OPTIONS') {
            return (new Route('OPTIONS', $request->path(), function () use ($methods) {
                return new Response('', 200, ['Allow' => implode(',', $methods)]);
            }))->bind($request);
        }

        $this->methodNotAllowed($methods, $request->method());
    }

    /**
     * Throw a method not allowed HTTP exception.
     *
     * @param  array  $others
     * @param  string  $method
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function methodNotAllowed(array $others, $method)
    {
        throw new MethodNotAllowedHttpException(
            $others,
            sprintf(
                'The %s method is not supported for this route. Supported methods: %s.',
                $method,
                implode(', ', $others)
            )
        );
    }

    /**
     * Compile the routes for caching.
     *
     * @return array
     */
    public function compile()
    {
        $compiled = $this->dumper()->getCompiledRoutes();

        $attributes = [];

        foreach ($this->getRoutes() as $route) {
            $attributes[$route->getName()] = [
                'methods' => $route->methods(),
                'uri' => $route->uri(),
                'action' => $route->getAction(),
                'fallback' => $route->isFallback,
                'defaults' => $route->defaults,
                'wheres' => $route->wheres,
                'bindingFields' => $route->bindingFields(),
                'lockSeconds' => $route->locksFor(),
                'waitSeconds' => $route->waitsFor(),
            ];
        }

        return compact('compiled', 'attributes');
    }

    /**
     * Return the CompiledUrlMatcherDumper instance for the route collection.
     *
     * @return \Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper
     */
    public function dumper()
    {
        return new CompiledUrlMatcherDumper($this->toSymfonyRouteCollection());
    }

    /**
     * Convert the collection to a Symfony RouteCollection instance.
     *
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function toSymfonyRouteCollection()
    {
        $symfonyRoutes = new SymfonyRouteCollection;

        $routes = $this->getRoutes();

        foreach ($routes as $route) {
            if (! $route->isFallback) {
                $symfonyRoutes = $this->addToSymfonyRoutesCollection($symfonyRoutes, $route);
            }
        }

        foreach ($routes as $route) {
            if ($route->isFallback) {
                $symfonyRoutes = $this->addToSymfonyRoutesCollection($symfonyRoutes, $route);
            }
        }

        return $symfonyRoutes;
    }

    /**
     * Add a route to the SymfonyRouteCollection instance.
     *
     * @param  \Symfony\Component\Routing\RouteCollection  $symfonyRoutes
     * @param  \Illuminate\Routing\Route  $route
     * @return \Symfony\Component\Routing\RouteCollection
     */
    protected function addToSymfonyRoutesCollection(SymfonyRouteCollection $symfonyRoutes, Route $route)
    {
        $name = $route->getName();

        if (Str::endsWith($name, '.') &&
            ! is_null($symfonyRoutes->get($name))) {
            $name = null;
        }

        if (! $name) {
            $route->name($name = $this->generateRouteName());

            $this->add($route);
        } elseif (! is_null($symfonyRoutes->get($name))) {
            throw new LogicException("Unable to prepare route [{$route->uri}] for serialization. Another route has already been assigned name [{$name}].");
        }

        $symfonyRoutes->add($route->getName(), $route->toSymfonyRoute());

        return $symfonyRoutes;
    }

    /**
     * Get a randomly generated route name.
     *
     * @return string
     */
    protected function generateRouteName()
    {
        return 'generated::'.Str::random();
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->getRoutes());
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->getRoutes());
    }
}
