<?php

namespace Illuminate\View;

use ReflectionClass;

class AnonymousComponent extends Component
{
    /**
     * The component view.
     *
     * @var string
     */
    protected $view;

    /**
     * The component data.
     *
     * @var array
     */
    protected $data = [];

    protected static array $ignoredParameterNames = [];

    /**
     * Fetch a cached set of anonymous component constructor parameter names to exclude.
     */
    public static function ignoredParameterNames(): array
    {
        if (!isset(static::$ignoredParameterNames)) {
            $constructor = (new ReflectionClass(
                static::class
            ))->getConstructor();

            static::$ignoredParameterNames = collect($constructor->getParameters())
                ->map->getName()
                ->all();
        }

        return static::$ignoredParameterNames;
    }

    /**
     * Create a new anonymous component instance.
     *
     * @param  string  $view
     * @param  array  $data
     * @return void
     */
    public function __construct($view, $data)
    {
        $this->view = $view;
        $this->data = $data;
    }

    /**
     * Get the view / view contents that represent the component.
     *
     * @return string
     */
    public function render()
    {
        return $this->view;
    }

    /**
     * Get the data that should be supplied to the view.
     *
     * @return array
     */
    public function data()
    {
        $this->attributes = $this->attributes ?: $this->newAttributeBag();

        return array_merge(
            ($this->data['attributes'] ?? null)?->getAttributes() ?: [],
            $this->attributes->getAttributes(),
            $this->data,
            ['attributes' => $this->attributes]
        );
    }
}
