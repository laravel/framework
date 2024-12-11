<?php

namespace Illuminate\View;

class AnonymousComponent extends Component
{
    /**
     * Create a new anonymous component instance.
     *
     * @param  string  $view
     * @param  array  $data
     * @return void
     */
    public function __construct(
        protected $view,
        protected $data = [],
    ) {
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
