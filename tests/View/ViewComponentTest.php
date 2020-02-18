<?php

namespace Illuminate\Tests\View;

use Illuminate\Container\Container;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory as ValidationFactoryContract;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException;
use Illuminate\View\Component;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ViewComponentTest extends TestCase
{
    public function testDataExposure()
    {
        $component = new TestViewComponent;

        $variables = $component->data();

        $this->assertEquals(10, $variables['votes']);
        $this->assertEquals('world', $variables['hello']());
        $this->assertEquals('taylor', $variables['hello']('taylor'));
    }

    public function testComponentValidationThrowsException()
    {
        $this->expectException(ValidationException::class);

        $this->createComponent()->validate();
    }

    public function testComponentValidation()
    {
        $this->createComponent(['taylor@laravel.com'])->validate();
    }

    protected function createComponent(array $props = [], $class = TestValidationViewComponent::class)
    {
        $container = tap(new Container, function ($container) {
            $container->instance(
                ValidationFactoryContract::class,
                $this->createValidationFactory($container)
            );
        });

        $component = new $class(...$props);
        $component->setContainer($container);

        return $component;
    }

    /**
     * Create a new validation factory.
     *
     * @param  \Illuminate\Container\Container  $container
     * @return \Illuminate\Validation\Factory
     */
    protected function createValidationFactory($container)
    {
        $translator = m::mock(Translator::class)->shouldReceive('get')
            ->zeroOrMoreTimes()->andReturn('error')->getMock();

        return new ValidationFactory($translator, $container);
    }
}

class TestViewComponent extends Component
{
    public $votes = 10;

    public function render()
    {
        return 'test';
    }

    public function hello($string = 'world')
    {
        return $string;
    }
}


class TestValidationViewComponent extends Component
{
    public $votes = 10;

    public $email;

    public function __construct($email = '')
    {
        $this->email = $email;
    }

    public function rules()
    {
        return [
            'votes' => ['required', 'lte:10'],
            'email' => ['required', 'email'],
            'hello' => ['required'],
        ];
    }

    public function render()
    {
        return 'test';
    }

    public function hello($string = 'world')
    {
        return $string;
    }
}
