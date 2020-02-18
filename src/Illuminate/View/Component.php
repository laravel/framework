<?php

namespace Illuminate\View;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

abstract class Component
{
    /**
     * The cache of public property names, keyed by class.
     *
     * @var array
     */
    protected static $propertyCache = [];

    /**
     * The cache of public method names, keyed by class.
     *
     * @var array
     */
    protected static $methodCache = [];

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
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The validator instance.
     *
     * @var \Illuminate\Contracts\Validation\Validator
     */
    protected $validator;

    /**
     * Get the view / view contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    abstract public function render();

    /**
     * Get the validation rules for the component properties.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * Resolve the Blade view or view file that should be used when rendering the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function resolveView()
    {
        $this->validate();

        $view = $this->render();

        if ($view instanceof View) {
            return $view;
        }

        $factory = Container::getInstance()->make('view');

        return $factory->exists($this->render())
                    ? $this->render()
                    : $this->createBladeViewFromString($factory, $this->render());
    }

    /**
     * Create a Blade view with the raw component string content.
     *
     * @param  \Illuminate\Contracts\View\Factory  $factory
     * @param  string  $contents
     * @return string
     */
    protected function createBladeViewFromString($factory, $contents)
    {
        $factory->addNamespace(
            '__components',
            $directory = Container::getInstance()['config']->get('view.compiled')
        );

        if (! file_exists($viewFile = $directory.'/'.sha1($contents).'.blade.php')) {
            file_put_contents($viewFile, $contents);
        }

        return '__components::'.basename($viewFile, '.blade.php');
    }

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

        return array_merge($this->extractPublicProperties(), $this->extractPublicMethods());
    }

    /**
     * Extract the public properties for the component.
     *
     * @return array
     */
    protected function extractPublicProperties()
    {
        $class = get_class($this);

        if (! isset(static::$propertyCache[$class])) {
            $reflection = new ReflectionClass($this);

            static::$propertyCache[$class] = collect($reflection->getProperties(ReflectionProperty::IS_PUBLIC))
                ->reject(function (ReflectionProperty $property) {
                    return $this->shouldIgnore($property->getName());
                })
                ->map(function (ReflectionProperty $property) {
                    return $property->getName();
                })->all();
        }

        $values = [];

        foreach (static::$propertyCache[$class] as $property) {
            $values[$property] = $this->{$property};
        }

        return $values;
    }

    /**
     * Extract the public methods for the component.
     *
     * @return array
     */
    protected function extractPublicMethods()
    {
        $class = get_class($this);

        if (! isset(static::$methodCache[$class])) {
            $reflection = new ReflectionClass($this);

            static::$methodCache[$class] = collect($reflection->getMethods(ReflectionMethod::IS_PUBLIC))
                ->reject(function (ReflectionMethod $method) {
                    return $this->shouldIgnore($method->getName());
                })
                ->map(function (ReflectionMethod $method) {
                    return $method->getName();
                });
        }

        $values = [];

        foreach (static::$methodCache[$class] as $method) {
            $values[$method] = $this->createVariableFromMethod(new ReflectionMethod($this, $method));
        }

        return $values;
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
            'data',
            'render',
            'resolveView',
            'setValidator',
            'shouldRender',
            'validate',
            'view',
            'withAttributes',
        ], $this->except);
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
     * Determine if the component should be rendered.
     *
     * @return bool
     */
    public function shouldRender()
    {
        return true;
    }

    /**
     * Validate the given class instance.
     *
     * @return void
     */
    public function validate()
    {
        $instance = $this->getValidatorInstance();

        if ($instance->fails()) {
            $this->failedValidation($instance);
        }
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator);
    }

    /**
     * Get the validator instance for the request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function getValidatorInstance()
    {
        if ($this->validator) {
            return $this->validator;
        }

        $factory = $this->container->make(ValidationFactory::class);

        if (method_exists($this, 'validator')) {
            $validator = $this->container->call([$this, 'validator'], compact('factory'));
        } else {
            $validator = $this->createDefaultValidator($factory);
        }

        $this->setValidator($validator);

        return $this->validator;
    }

    /**
     * Create the default validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Factory  $factory
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function createDefaultValidator(ValidationFactory $factory)
    {
        return $factory->make(
            $this->data(), $this->container->call([$this, 'rules'])
        );
    }

    /**
     * Set the Validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator(Validator $validator)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Set the container implementation.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return $this
     */
    public function setContainer(\Illuminate\Contracts\Container\Container $container)
    {
        $this->container = $container;

        return $this;
    }
}
