<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Routing\ControllerMiddlewareOptions;
use PHPUnit\Framework\TestCase;

class ControllerMiddlewareOptionsTest extends TestCase
{
    public function test_only_sets_methods_from_array()
    {
        $options = [];
        $middlewareOptions = new ControllerMiddlewareOptions($options);

        $result = $middlewareOptions->only(['index', 'show']);

        $this->assertSame($middlewareOptions, $result);
        $this->assertSame(['index', 'show'], $options['only']);
    }

    public function test_only_sets_methods_from_variadic_arguments()
    {
        $options = [];
        $middlewareOptions = new ControllerMiddlewareOptions($options);

        $middlewareOptions->only('index', 'show', 'create');

        $this->assertSame(['index', 'show', 'create'], $options['only']);
    }

    public function test_only_sets_single_method()
    {
        $options = [];
        $middlewareOptions = new ControllerMiddlewareOptions($options);

        $middlewareOptions->only('index');

        $this->assertSame(['index'], $options['only']);
    }

    public function test_except_sets_methods_from_array()
    {
        $options = [];
        $middlewareOptions = new ControllerMiddlewareOptions($options);

        $result = $middlewareOptions->except(['destroy', 'update']);

        $this->assertSame($middlewareOptions, $result);
        $this->assertSame(['destroy', 'update'], $options['except']);
    }

    public function test_except_sets_methods_from_variadic_arguments()
    {
        $options = [];
        $middlewareOptions = new ControllerMiddlewareOptions($options);

        $middlewareOptions->except('destroy', 'update', 'edit');

        $this->assertSame(['destroy', 'update', 'edit'], $options['except']);
    }

    public function test_except_sets_single_method()
    {
        $options = [];
        $middlewareOptions = new ControllerMiddlewareOptions($options);

        $middlewareOptions->except('destroy');

        $this->assertSame(['destroy'], $options['except']);
    }

    public function test_options_are_passed_by_reference()
    {
        $options = ['existing' => 'value'];
        $middlewareOptions = new ControllerMiddlewareOptions($options);

        $middlewareOptions->only(['index']);

        $this->assertSame('value', $options['existing']);
        $this->assertSame(['index'], $options['only']);
    }

    public function test_chaining_only_and_except()
    {
        $options = [];
        $middlewareOptions = new ControllerMiddlewareOptions($options);

        $middlewareOptions->only(['index', 'show'])->except(['destroy']);

        $this->assertSame(['index', 'show'], $options['only']);
        $this->assertSame(['destroy'], $options['except']);
    }
}
