<?php

use Mockery as m;

class FoundationPackageRegistrarTest extends PHPUnit_Framework_TestCase {

    public function tearDown()
    {
        m::close();
    }

    public function testDefaultPackageRegisteration()
    {
        $registrar = new Illuminate\Foundation\Publishing\PackageRegistrar();
        $registrar->register('foo/bar', 'foo/bar', __DIR__);

        $this->assertEquals('src/config', $registrar->getConfigPath('foo/bar'));
        $this->assertEquals('src/migrations', $registrar->getMigrationsPath('foo/bar'));
        $this->assertEquals('src/views', $registrar->getViewsPath('foo/bar'));
        $this->assertEquals('src/lang', $registrar->getLanguagePath('foo/bar'));
        $this->assertEquals('public', $registrar->getAssetsPath('foo/bar'));
    }

    public function testEmptyPackageRegistration()
    {
        $registrar = new Illuminate\Foundation\Publishing\PackageRegistrar();
        $registrar->register('foo/bar', 'foo/bar', __DIR__, []);

        $this->assertEquals(null, $registrar->getConfigPath('foo/bar'));
        $this->assertEquals(null, $registrar->getAssetsPath('foo/bar'));
        $this->assertEquals(null, $registrar->getViewsPath('foo/bar'));
        $this->assertEquals(null, $registrar->getConfigPath('foo/bar'));
        $this->assertEquals(null, $registrar->getMigrationsPath('foo/bar'));
    }

    public function testCustomPackageRegistration()
    {
        $registrar = new Illuminate\Foundation\Publishing\PackageRegistrar();
        $registrar->register('foo/bar', 'foo/bar', __DIR__, [
        	'config' => 'config',
        	'lang' => 'resources/lang',
        	'views' => 'resources/views',
        	'assets' => 'resources/assets',
        	'migrations' => 'migrations',
        ]);

        $this->assertEquals('config', $registrar->getConfigPath('foo/bar'));
        $this->assertEquals('resources/lang', $registrar->getLanguagePath('foo/bar'));
        $this->assertEquals('resources/views', $registrar->getViewsPath('foo/bar'));
        $this->assertEquals('resources/assets', $registrar->getAssetsPath('foo/bar'));
        $this->assertEquals('migrations', $registrar->getMigrationsPath('foo/bar'));
    }

}
