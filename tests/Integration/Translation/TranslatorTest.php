<?php

namespace Illuminate\Tests\Integration\Translation;

use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

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
        $app['translator']->addNamespace('tests', __DIR__.'/lang');
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

    public function testItCanCheckKeyExistsHasFromLocaleForJsonWithNestedKeys()
    {
        $this->assertTrue($this->app['translator']->has('days.monday'));
        $this->assertTrue($this->app['translator']->hasForLocale('days.monday'));
        $this->assertTrue($this->app['translator']->has('days.wednesday'));
        $this->assertTrue($this->app['translator']->hasForLocale('days.wednesday'));
        $this->assertTrue($this->app['translator']->has('days.friday'));
        $this->assertTrue($this->app['translator']->hasForLocale('days.friday'));

        $this->assertSame('Monday', $this->app['translator']->get('days.monday'));
        $this->assertSame('Wednesday', $this->app['translator']->get('days.wednesday'));
        $this->assertSame('Friday', $this->app['translator']->get('days.friday'));

        $this->assertFalse($this->app['translator']->has('days.sunday'));
        $this->assertFalse($this->app['translator']->hasForLocale('days.sunday'));
        $this->assertFalse($this->app['translator']->has('days.tuesday'));
        $this->assertFalse($this->app['translator']->hasForLocale('days.tuesday'));
        $this->assertFalse($this->app['translator']->has('days.thursday'));
        $this->assertFalse($this->app['translator']->hasForLocale('days.thursday'));

        $this->assertSame('days.sunday', $this->app['translator']->get('days.sunday'));
        $this->assertSame('days.tuesday', $this->app['translator']->get('days.tuesday'));
        $this->assertSame('days.thursday', $this->app['translator']->get('days.thursday'));

        $this->app->setLocale('fr');

        $this->assertTrue($this->app['translator']->has('days.sunday'));
        $this->assertTrue($this->app['translator']->hasForLocale('days.sunday'));
        $this->assertTrue($this->app['translator']->has('days.tuesday'));
        $this->assertTrue($this->app['translator']->hasForLocale('days.tuesday'));
        $this->assertTrue($this->app['translator']->has('days.thursday'));
        $this->assertTrue($this->app['translator']->hasForLocale('days.thursday'));

        $this->assertSame('Dimanche', $this->app['translator']->get('days.sunday'));
        $this->assertSame('Mardi', $this->app['translator']->get('days.tuesday'));
        $this->assertSame('Jeudi', $this->app['translator']->get('days.thursday'));

        $this->assertFalse($this->app['translator']->has('days.monday'));
        $this->assertFalse($this->app['translator']->hasForLocale('days.monday'));
        $this->assertFalse($this->app['translator']->has('days.wednesday'));
        $this->assertFalse($this->app['translator']->hasForLocale('days.wednesday'));
        $this->assertFalse($this->app['translator']->has('days.friday'));
        $this->assertFalse($this->app['translator']->hasForLocale('days.friday'));

        $this->assertSame('days.monday', $this->app['translator']->get('days.monday'));
        $this->assertSame('days.wednesday', $this->app['translator']->get('days.wednesday'));
        $this->assertSame('days.friday', $this->app['translator']->get('days.friday'));
    }

    public function testItCanCheckKeyExistsWithoutTriggeringHandleMissingKeys()
    {
        $this->app['translator']->handleMissingKeysUsing(function ($key) {
            $_SERVER['__missing_translation_key'] = $key;
        });

        $this->assertFalse($this->app['translator']->has('Foo Bar'));
        $this->assertFalse(isset($_SERVER['__missing_translation_key']));

        $this->assertFalse($this->app['translator']->hasForLocale('Foo Bar', 'nl'));
        $this->assertFalse(isset($_SERVER['__missing_translation_key']));
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

    #[DataProvider('greetingChoiceDataProvider')]
    public function testItCanHandleChoice(int $count, string $expected, ?string $locale = null)
    {
        if (! is_null($locale)) {
            $this->app->setLocale($locale);
        }

        $name = 'Taylor';

        $this->assertSame(
            strtr($expected, [':name' => $name, ':count' => $count]),
            $this->app['translator']->choice('tests::app.greeting', $count, ['name' => $name])
        );
    }

    #[DataProvider('greetingChoiceDataProvider')]
    public function testItCanHandleChoiceWithChoiceSeparatorInReplaceString(int $count, string $expected, ?string $locale = null)
    {
        if (! is_null($locale)) {
            $this->app->setLocale($locale);
        }

        $name = 'Taylor | Laravel';

        $this->assertSame(
            strtr($expected, [':name' => $name, ':count' => $count]),
            $this->app['translator']->choice('tests::app.greeting', $count, ['name' => $name])
        );
    }

    public static function greetingChoiceDataProvider()
    {
        yield [0, 'Hello :name'];
        yield [3, 'Hello :name, you have 3 unread messages'];
        yield [0, 'Bonjour :name', 'fr'];
        yield [3, 'Bonjour :name, vous avez :count messages non lus', 'fr'];
    }
}
