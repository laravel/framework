<?php

namespace Illuminate\Tests\Translation;

use Mockery as m;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Config\Repository as Config;

class TranslationDateTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testCarbonDateFirstEnglishThenFrench()
    {
        $app = new Application;
        $app['config'] = new Config();
        $app['translator'] = new \Illuminate\Translation\Translator($this->getLoader(), 'en');

        $this->assertEquals(Carbon::now()->addYear()->diffForHumans(), '1 year from now');
        $app->setLocale('fr');
        $this->assertEquals(Carbon::now()->addYear()->diffForHumans(), 'dans 1 an');
    }

    public function testCarbonDateGermanFromInitialConfig()
    {
        $app = new Application;
        $app['config'] = new Config();
        $app['translator'] = new \Illuminate\Translation\Translator($this->getLoader(), 'de');

        $this->assertEquals(Carbon::now()->addYear()->diffForHumans(), 'in 1 Jahr');
        $app->setLocale('en');
        $this->assertEquals(Carbon::now()->addYear()->diffForHumans(), '1 year from now');
    }

    protected function getLoader()
    {
        return m::mock('Illuminate\Translation\LoaderInterface');
    }
}
