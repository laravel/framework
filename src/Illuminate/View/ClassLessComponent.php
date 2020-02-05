<?php

namespace Illuminate\View;

class ClassLessComponent extends Component
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
     * Create a new class-less component instance.
     *
     * @param  array  $data
     * @return void
     */
    public function __construct($data)
    {
        $this->view = $data['view'];

        unset($data['view']);

        $this->data = $data;
    }

    /**
     * Get the view / view contents that represent the component.
     *
     * @return string
     */
    public function view()
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
        return $this->data;
    }
}
