<?php

namespace Illuminate\Tests\Integration\Translation;

use Orchestra\Testbench\TestCase;

class TranslatorTest extends TestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        $app['translator']->addJsonPath(__DIR__.'/lang');

        parent::defineEnvironment($app);
    }

    public function testItCanGetFromLocaleForJson()
    {
        $this->assertSame('30 Days', __('30 Days'));

        $this->app->setLocale('fr');

        $this->assertSame('30 jours', __('30 Days'));
    }

    public function testItCanCheckLanguageExistsHasFromLocaleForJson()
    {
        $this->assertTrue(trans()->has('1 Day'));
        $this->assertTrue(trans()->hasForLocale('1 Day'));
        $this->assertTrue(trans()->hasForLocale('30 Days'));

        $this->app->setLocale('fr');

        $this->assertFalse(trans()->has('1 Day'));
        $this->assertFalse(trans()->hasForLocale('1 Day'));
        $this->assertTrue(trans()->hasForLocale('30 Days'));
    }
}
