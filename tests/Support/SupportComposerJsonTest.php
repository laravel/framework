<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\ComposerJson;
use PHPUnit\Framework\TestCase;

class SupportComposerJsonTest extends TestCase
{
    /** @test */
    public function getNamespacedClassFromPath()
    {
        $reader = ComposerJson::make($p = __DIR__.'/Fixtures/composer_json/shortcut_namespace');
        $namespace = $reader->getNamespacedClassFromPath($p.'/app/G1/G2.php');
        $this->assertEquals('App\G1\G2', $namespace);

        $namespace = $reader->getNamespacedClassFromPath($p.'/app/Models/G1/G2.php');
        $this->assertEquals('Models\G1\G2', $namespace);

        $reader = ComposerJson::make($p = __DIR__.'/Fixtures/composer_json');
        $namespace = $reader->getNamespacedClassFromPath($p.'/m1/G1/G2.php');
        $this->assertEquals('Map\G1\G2', $namespace);

        $namespace = $reader->getNamespacedClassFromPath($p.'/m2/G1/G2.php');
        $this->assertEquals('Map\G1\G2', $namespace);

        $namespace = $reader->getNamespacedClassFromPath($p.'/dapp/dapp/G1/G2.php');
        $this->assertEquals('Dapp\dapp\G1\G2', $namespace);

        $namespace = $reader->getNamespacedClassFromPath($p.'/a2/ref/ref/G2.php');
        $this->assertEquals('G2\ref\G2', $namespace);
    }

    /** @test */
    public function read_autoload_psr4_purged()
    {
        $reader = ComposerJson::make(__DIR__.'/Fixtures/composer_json/shortcut_namespace');
        $this->assertEquals([
            '/' => [
                'App\\' => 'app/',
                'Tests\\' => 'tests/',
            ],
        ], $reader->readAutoload(true));
    }

    /** @test */
    public function read_autoload_psr4()
    {
        $reader = ComposerJson::make(__DIR__.'/Fixtures/composer_json');

        $expected = [
            'a2' => [
                'G2\\' => 'a2/ref/',
                'App2\\' => 'a2/app2/',
            ],
            '/' => [
                'App\\' => 'app/',
                'Dapp\\' => 'dapp/', // <==== is normalized
                'Map\\' => ['m1/', 'm2/'],
            ],
        ];

        $this->assertEquals($expected, $reader->readAutoload());
    }

    /** @test */
    public function get_namespace_from_relative_path()
    {
        $reader = ComposerJson::make($p = __DIR__.'/Fixtures/composer_json/a3');
        $result = $reader->getNamespacedClassFromPath('app/Hello.php');
        $this->assertEquals('App\\Hello', $result);

        $result = $reader->getNamespacedClassFromPath('app/appollo.php');
        $this->assertEquals('App\\appollo', $result);

        $result = $reader->getNamespacedClassFromPath('app/Models/Hello.php');
        $this->assertEquals('Models\\Hello', $result);

        $result = $reader->getNamespacedClassFromPath('app/appollo.php');
        $this->assertEquals('App\\appollo', $result);

        $result = $reader->getNamespacedClassFromPath('app/d/appollo.php');
        $this->assertEquals('Test\\appollo', $result);
    }

    /** @test */
    public function readKey()
    {
        $reader = ComposerJson::make(__DIR__.'/Fixtures/composer_json');
        $this->assertEquals('iman/ghafoori', $reader->readKey('name'));
        $this->assertEquals(['hello/how' => '~5.0'], $reader->readKey('require'));
        $this->assertEquals('~5.0', $reader->readKey('require.hello/how'));
        $this->assertEquals(['framework', 'package'], $reader->readKey('keywords'));
    }

    /** @test */
    public function expects_real_paths()
    {
        $this->expectException(\InvalidArgumentException::class);
        ComposerJson::make(__DIR__.'/Stubs/absent');
    }

    /** @test */
    public function expects_composer_json_file_to_exist()
    {
        $this->expectException(\InvalidArgumentException::class);
        ComposerJson::make(__DIR__.'/Stubs/empty');
    }

    /** @test */
    public function readComposerFileData()
    {
        $reader = ComposerJson::make(__DIR__.'/Fixtures/composer_json');
        $actual = $reader->readComposerFileData();
        $expected = [
            'name' => 'iman/ghafoori',
            'description' => 'Some description.',
            'keywords' => ['framework', 'package'],
            'license' => 'MIT',
            'type' => 'project',
            'require' => [
                'hello/how' => '~5.0',
            ],
            'require-dev' => [
                'phpunit/phpunit' => '~4.0',
            ],
            'autoload' => [
                'classmap' => [
                    'database',
                    'tests/TestCase.php',
                ],
                'psr-4' => [
                    'App\\' => 'app/',
                    'Dapp\\' => 'dapp',
                    'Map\\' => ['m1/', 'm2/'],
                ],
                'files' => [
                    'src/MyLib/functions.php',
                    'src/MyLib/functions2.php',
                ],
            ],
            'autoload-dev' => [
                'psr-4' => [
                    'Imanghafoori\\LaravelMicroscope\\Tests\\' => 'tests',
                ],
                'files' => [
                    'src/MyLib/functions.php',
                    'src/MyLib/functions2.php',
                ],
            ],
            'repositories' => [
                [
                    'type' => 'path',
                    'url' => './a2',
                ],
            ],
            'extra' => [
                'some_key' => [
                    'dont-discover' => ['*'],
                ],
            ],
            'minimum-stability' => 'dev',
        ];

        $this->assertEquals($expected, $actual);
    }
}
