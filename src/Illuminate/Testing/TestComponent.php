<?php

namespace Illuminate\Testing;

use Illuminate\Testing\TestView;
use Illuminate\View\Component;


class TestComponent extends TestView
{
  /**
   * The original view.
   *
   * @var \Illuminate\View\Component
   */
  protected $component;

  /**
   * The rendered view contents.
   *
   * @var string
   */
  protected $rendered;

  /**
   * Create a new test view instance.
   *
   * @param  \Illuminate\View\Component  $view
   * @return void
   */
  public function __construct(Component $component, $view)
  {
    $this->component = $component;
    $this->rendered = $view->render();
  }

  public function __get($attribute)
  {
    return $this->component->{$attribute};
  }
}
