<?php

namespace Illuminate\Testing;

use Illuminate\Testing\Assert as PHPUnit;
use Illuminate\View\View;

class TestView
{
    /**
     * The original view.
     *
     * @var \Illuminate\View\View
     */
    protected $view;

    /**
     * Create a new test view instance.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function __construct(View $view)
    {
        $this->view = $view;
    }

    /**
     * Assert that the given string is contained within the view.
     *
     * @param  string  $value
     * @param  bool  $escaped
     * @return $this
     */
    public function assertSee($value, $escaped = true)
    {
        $value = $escaped ? e($value) : $value;

        PHPUnit::assertStringContainsString((string) $value, $this->view->render());

        return $this;
    }
}
