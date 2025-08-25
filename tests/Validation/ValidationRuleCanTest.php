<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Auth\Access\Gate;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rules\Can;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;
use stdClass;

class ValidationRuleCanTest extends TestCase
{
    protected $container;
    protected $user;
    protected $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = new stdClass;

        Container::setInstance($this->container = new Container);

        $this->container->singleton(GateContract::class, function () {
            return new Gate($this->container, function () {
                return $this->user;
            });
        });

        $this->container->bind('translator', function () {
            return new Translator(
                new ArrayLoader, 'en'
            );
        });

        Facade::setFacadeApplication($this->container);

        (new ValidationServiceProvider($this->container))->register();
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        Facade::clearResolvedInstances();

        Facade::setFacadeApplication(null);
    }

    public function testValidationFails()
    {
        $this->gate()->define('update-company', function ($user, $value) {
            $this->assertEquals('1', $value);

            return false;
        });

        $v = new Validator(
            resolve('translator'),
            ['company' => '1'],
            ['company' => new Can('update-company')]
        );

        $this->assertTrue($v->fails());
    }

    public function testValidationPasses()
    {
        $this->gate()->define('update-company', function ($user, $class, $model, $value) {
            $this->assertEquals(\App\Models\Company::class, $class);
            $this->assertInstanceOf(stdClass::class, $model);
            $this->assertEquals('1', $value);

            return true;
        });

        $v = new Validator(
            resolve('translator'),
            ['company' => '1'],
            ['company' => new Can('update-company', [\App\Models\Company::class, new stdClass])]
        );

        $this->assertTrue($v->passes());
    }

    /**
     * Get the Gate instance from the container.
     *
     * @return \Illuminate\Auth\Access\Gate
     */
    protected function gate()
    {
        return $this->container->make(GateContract::class);
    }
}
