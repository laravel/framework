<?php

use Illuminate\Validation\ValidationServiceProvider;

class ValidationServiceProviderTest extends PHPUnit_Framework_TestCase
{
    public function testDbIsNotRequired()
    {
        $app = new \Illuminate\Container\Container();

        $app->singleton('translator', function ($app) {
            return $this->getIlluminateArrayTranslator();
        });

        // when the 'db' binding is required and missing an exception will be thrown, so no assertions required
        // for this test
        (new ValidationServiceProvider($app))->register();

        $factory = $app->make('validator');

        $factory->make(['name' => ''], ['name' => 'required'])->passes();
    }

    /**
     * Configures and returns an Illuminate\Translation\Translator.
     *
     * @return \Illuminate\Translation\Translator
     */
    public function getIlluminateArrayTranslator()
    {
        return new Illuminate\Translation\Translator(
            new Illuminate\Translation\ArrayLoader, 'en'
        );
    }
}
