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
        $this->assertSame('30 Days', $this->app['translator']->get('30 Days'));

        $this->app->setLocale('fr');

        $this->assertSame('30 jours', $this->app['translator']->get('30 Days'));
    }

    public function testItCanCheckLanguageExistsHasFromLocaleForJson()
    {
        $this->assertTrue($this->app['translator']->has('1 Day'));
        $this->assertTrue($this->app['translator']->hasForLocale('1 Day'));
        $this->assertTrue($this->app['translator']->hasForLocale('30 Days'));

        $this->app->setLocale('fr');

        $this->assertFalse($this->app['translator']->has('1 Day'));
        $this->assertFalse($this->app['translator']->hasForLocale('1 Day'));
        $this->assertTrue($this->app['translator']->hasForLocale('30 Days'));
    }

    public function testItCanHandleMissingKeysUsingCallback()
    {
        $this->app['translator']->handleMissingKeysUsing(function ($key) {
            $_SERVER['__missing_translation_key'] = $key;

            return 'callback key';
        });

        $key = $this->app['translator']->get('some missing key');

        $this->assertSame('callback key', $key);
        $this->assertSame('some missing key', $_SERVER['__missing_translation_key']);

        $this->app['translator']->handleMissingKeysUsing(null);
    }

    public function testItCanHandleMissingKeysNoReturn()
    {
        $this->app['translator']->handleMissingKeysUsing(function ($key) {
            $_SERVER['__missing_translation_key'] = $key;
        });

        $key = $this->app['translator']->get('some missing key');

        $this->assertSame('some missing key', $key);
        $this->assertSame('some missing key', $_SERVER['__missing_translation_key']);

        $this->app['translator']->handleMissingKeysUsing(null);
    }

    public function testItReturnsCorrectLocaleForMissingKeys()
    {
        $this->app['translator']->handleMissingKeysUsing(function ($key, $replacements, $locale) {
            $_SERVER['__missing_translation_key_locale'] = $locale;
        });

        $this->app['translator']->get('some missing key', [], 'ht');

        $this->assertSame('ht', $_SERVER['__missing_translation_key_locale']);

        $this->app['translator']->handleMissingKeysUsing(null);
    }
}
