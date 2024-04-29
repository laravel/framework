<?php

namespace Illuminate\View;

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

    /**
     * Create a new anonymous component instance.
     *
     * @param  string  $view
     * @param  array  $data
     * @return void
     */
    public function __construct($view, $data, Factory $factory)
    {
        $this->view = $view;
        $this->data = $data;
        self::$factory = $factory;
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
     * Resolve the component instance with the given data.
     *
     * @param  array  $data
     * @return static
     */
    public static function resolve($data)
    {
        return new static($data['view'], $data['data'], $data['factory']);
    }

    /**
     * Resolve the Blade view or view file that should be used when rendering the component.
     *
     * @return string
     */
    public function resolveView()
    {
        $view = $this->render();

        return $this->extractBladeViewFromString($view);
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
