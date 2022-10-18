<?php

namespace Illuminate\View\Concerns;

use Closure;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait ManagesEvents
{
    /**
     * An array of views and whether they have registered "creators".
     *
     * @var array<string, true>|true
     */
    protected $shouldCallCreators = [];

    /**
     * An array of views and whether they have registered "composers".
     *
     * @var array<string, true>|true
     */
    protected $shouldCallComposers = [];

    /**
     * Register a view creator event.
     *
     * @param  array|string  $views
     * @param  \Closure|string  $callback
     * @return array
     */
    public function creator($views, $callback)
    {
        if (is_array($this->shouldCallCreators)) {
            foreach (Arr::wrap($views) as $view) {
                if (str_contains($view, '*')) {
                    $this->shouldCallCreators = true;

                    break;
                }

                $this->shouldCallCreators[$this->normalizeName($view)] = true;
            }
        }

        $creators = [];

        foreach ((array) $views as $view) {
            $creators[] = $this->addViewEvent($view, $callback, 'creating: ');
        }

        return $creators;
    }

    /**
     * Register multiple view composers via an array.
     *
     * @param  array  $composers
     * @return array
     */
    public function composers(array $composers)
    {
        $registered = [];

        foreach ($composers as $callback => $views) {
            $registered = array_merge($registered, $this->composer($views, $callback));
        }

        return $registered;
    }

    /**
     * Register a view composer event.
     *
     * @param  array|string  $views
     * @param  \Closure|string  $callback
     * @return array
     */
    public function composer($views, $callback)
    {
        if (is_array($this->shouldCallComposers)) {
            foreach (Arr::wrap($views) as $view) {
                if (str_contains($view, '*')) {
                    $this->shouldCallComposers = true;

                    break;
                }

                $this->shouldCallComposers[$this->normalizeName($view)] = true;
            }
        }

        $composers = [];

        foreach ((array) $views as $view) {
            $composers[] = $this->addViewEvent($view, $callback);
        }

        return $composers;
    }

    /**
     * Add an event for a given view.
     *
     * @param  string  $view
     * @param  \Closure|string  $callback
     * @param  string  $prefix
     * @return \Closure|null
     */
    protected function addViewEvent($view, $callback, $prefix = 'composing: ')
    {
        $view = $this->normalizeName($view);

        if ($callback instanceof Closure) {
            $this->addEventListener($prefix.$view, $callback);

            return $callback;
        } elseif (is_string($callback)) {
            return $this->addClassEvent($view, $callback, $prefix);
        }
    }

    /**
     * Register a class based view composer.
     *
     * @param  string  $view
     * @param  string  $class
     * @param  string  $prefix
     * @return \Closure
     */
    protected function addClassEvent($view, $class, $prefix)
    {
        $name = $prefix.$view;

        // When registering a class based view "composer", we will simply resolve the
        // classes from the application IoC container then call the compose method
        // on the instance. This allows for convenient, testable view composers.
        $callback = $this->buildClassEventCallback(
            $class, $prefix
        );

        $this->addEventListener($name, $callback);

        return $callback;
    }

    /**
     * Build a class based container callback Closure.
     *
     * @param  string  $class
     * @param  string  $prefix
     * @return \Closure
     */
    protected function buildClassEventCallback($class, $prefix)
    {
        [$class, $method] = $this->parseClassEvent($class, $prefix);

        // Once we have the class and method name, we can build the Closure to resolve
        // the instance out of the IoC container and call the method on it with the
        // given arguments that are passed to the Closure as the composer's data.
        return function () use ($class, $method) {
            return $this->container->make($class)->{$method}(...func_get_args());
        };
    }

    /**
     * Parse a class based composer name.
     *
     * @param  string  $class
     * @param  string  $prefix
     * @return array
     */
    protected function parseClassEvent($class, $prefix)
    {
        return Str::parseCallback($class, $this->classEventMethodForPrefix($prefix));
    }

    /**
     * Determine the class event method based on the given prefix.
     *
     * @param  string  $prefix
     * @return string
     */
    protected function classEventMethodForPrefix($prefix)
    {
        return str_contains($prefix, 'composing') ? 'compose' : 'create';
    }

    /**
     * Add a listener to the event dispatcher.
     *
     * @param  string  $name
     * @param  \Closure  $callback
     * @return void
     */
    protected function addEventListener($name, $callback)
    {
        if (str_contains($name, '*')) {
            $callback = function ($name, array $data) use ($callback) {
                return $callback($data[0]);
            };
        }

        $this->events->listen($name, $callback);
    }

    /**
     * Call the composer for a given view.
     *
     * @param  \Illuminate\Contracts\View\View  $view
     * @return void
     */
    public function callComposer(ViewContract $view)
    {
        if ($this->shouldCallComposers === true || isset($this->shouldCallComposers[
            $this->normalizeName($view->name())
        ])) {
            $this->events->dispatch('composing: '.$view->name(), [$view]);
        }
    }

    /**
     * Call the creator for a given view.
     *
     * @param  \Illuminate\Contracts\View\View  $view
     * @return void
     */
    public function callCreator(ViewContract $view)
    {
        if ($this->shouldCallCreators === true || isset($this->shouldCallCreators[
            $this->normalizeName((string) $view->name())
        ])) {
            $this->events->dispatch('creating: '.$view->name(), [$view]);
        }
    }
}
