<?php

namespace Illuminate\Testing;

use Illuminate\Testing\Assert as PHPUnit;
use Illuminate\Testing\Constraints\SeeInOrder;

class TestComponent
{
    /**
     * The original component.
     *
     * @var \Illuminate\View\Component
     */
    public $component;

    /**
     * The rendered component contents.
     *
     * @var string
     */
    protected $rendered;

    /**
     * Create a new test component instance.
     *
     * @param  \Illuminate\View\Component  $component
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function __construct($component, $view)
    {
        $this->component = $component;

        $this->rendered = $view->render();
    }

    /**
     * Assert that the given string is contained within the rendered component.
     *
     * @param  string  $value
     * @param  bool  $escape
     * @return $this
     */
    public function assertSee($value, $escape = true)
    {
        $value = $escape ? e($value) : $value;

        PHPUnit::assertStringContainsString((string) $value, $this->rendered);

        return $this;
    }

    /**
     * Assert that the given strings are contained in order within the rendered component.
     *
     * @param  array  $values
     * @param  bool  $escape
     * @return $this
     */
    public function assertSeeInOrder(array $values, $escape = true)
    {
        $values = $escape ? array_map('e', ($values)) : $values;

        PHPUnit::assertThat($values, new SeeInOrder($this->rendered));

        return $this;
    }

    /**
     * Assert that the given string is contained within the rendered component text.
     *
     * @param  string  $value
     * @param  bool  $escape
     * @return $this
     */
    public function assertSeeText($value, $escape = true)
    {
        $value = $escape ? e($value) : $value;

        PHPUnit::assertStringContainsString((string) $value, strip_tags($this->rendered));

        return $this;
    }

    /**
     * Assert that the given strings are contained in order within the rendered component text.
     *
     * @param  array  $values
     * @param  bool  $escape
     * @return $this
     */
    public function assertSeeTextInOrder(array $values, $escape = true)
    {
        $values = $escape ? array_map('e', ($values)) : $values;

        PHPUnit::assertThat($values, new SeeInOrder(strip_tags($this->rendered)));

        return $this;
    }

    /**
     * Assert that the given string is not contained within the rendered component.
     *
     * @param  string  $value
     * @param  bool  $escape
     * @return $this
     */
    public function assertDontSee($value, $escape = true)
    {
        $value = $escape ? e($value) : $value;

        PHPUnit::assertStringNotContainsString((string) $value, $this->rendered);

        return $this;
    }

    /**
     * Assert that the given string is not contained within the rendered component text.
     *
     * @param  string  $value
     * @param  bool  $escape
     * @return $this
     */
    public function assertDontSeeText($value, $escape = true)
    {
        $value = $escape ? e($value) : $value;

        PHPUnit::assertStringNotContainsString((string) $value, strip_tags($this->rendered));

        return $this;
    }

    /**
     * Get the string contents of the rendered component.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->rendered;
    }

    /**
     * Dynamically access properties on the underlying component.
     *
     * @param  string  $attribute
     * @return mixed
     */
    public function __get($attribute)
    {
        return $this->component->{$attribute};
    }

    /**
     * Dynamically call methods on the underlying component.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->component->{$method}(...$parameters);
    }
}
