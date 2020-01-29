<?php

namespace Illuminate\View;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

abstract class Component implements Renderable
{
    /**
     * That properties / methods that should not be exposed to the component.
     *
     * @var array
     */
    protected $except = [];

    /**
     * The component attributes.
     *
     * @var \Illuminate\View\ComponentAttributeBag
     */
    public $attributes;

    /**
     * Get the view / view contents that represent the component.
     *
     * @return string
     */
    abstract public function view();

    /**
     * Get the data that should be supplied to the view.
     *
     * @author Freek Van der Herten
     * @author Brent Roose
     *
     * @return array
     */
    public function data()
    {
        $this->attributes = $this->attributes ?: new ComponentAttributeBag;

        $class = new ReflectionClass($this);

        $publicProperties = collect($class->getProperties(ReflectionProperty::IS_PUBLIC))
            ->reject(function (ReflectionProperty $property) {
                return $this->shouldIgnore($property->getName());
            })
            ->mapWithKeys(function (ReflectionProperty $property) {
                return [$property->getName() => $this->{$property->getName()}];
            });

        $publicMethods = collect($class->getMethods(ReflectionMethod::IS_PUBLIC))
            ->reject(function (ReflectionMethod $method) {
                return $this->shouldIgnore($method->getName());
            })
            ->mapWithKeys(function (ReflectionMethod $method) {
                return [$method->getName() => $this->createVariableFromMethod($method)];
            });

        return $publicProperties->merge($publicMethods)->all();
    }

    /**
     * Create a callable variable from the given method.
     *
     * @param  \ReflectionMethod  $method
     * @return mixed
     */
    protected function createVariableFromMethod(ReflectionMethod $method)
    {
        return $method->getNumberOfParameters() === 0
                        ? $this->{$method->getName()}()
                        : Closure::fromCallable([$this, $method->getName()]);
    }

    /**
     * Set the extra attributes that the component should make available.
     *
     * @param  array  $attributes
     * @return $this
     */
    public function withAttributes(array $attributes)
    {
        $this->attributes = $this->attributes ?: new ComponentAttributeBag;

        $this->attributes->setAttributes($attributes);

        return $this;
    }

    /**
     * Determine if the given property / method should be ignored.
     *
     * @param  string  $name
     * @return bool
     */
    protected function shouldIgnore($name)
    {
        return Str::startsWith($name, '__') ||
               in_array($name, $this->ignoredMethods());
    }

    /**
     * Get the methods that should be ignored.
     *
     * @return array
     */
    protected function ignoredMethods()
    {
        return array_merge([
            'view',
            'data',
            'withAttributes',
            'render',
            'viewFile',
            'shouldRender',
        ], $this->except);
    }

    /**
     * Get the blade view file that should be used when rendering the component.
     * When a string-based view is used, a temporary view will be generated.
     *
     * @return string
     */
    public function viewFile()
    {
        if (View::exists($this->view())) {
            return $this->view();
        }

        return $this->createBladeViewFromString($this->view());
    }

    /**
     * Create a blade view with the component string content.
     *
     * @param $contents
     * @return string
     */
    protected function createBladeViewFromString($contents)
    {
        $viewPath = Container::getInstance()['config']->get('view.compiled');

        View::addNamespace('__components', $viewPath);

        $viewFile = $viewPath.'/'.sha1($contents).'.blade.php';

        if (! file_exists($viewFile)) {
            file_put_contents($viewFile, $contents);
        }

        return '__components::'.basename($viewFile, '.blade.php');
    }

    /**
     * Determine if the component should be rendered.
     *
     * @return bool
     */
    public function shouldRender()
    {
        return true;
    }

    /**
     * Get the evaluated contents of the object.
     *
     * @return string
     */
    public function render()
    {
        return (string) View::make($this->viewFile(), $this->data());
    }
}
