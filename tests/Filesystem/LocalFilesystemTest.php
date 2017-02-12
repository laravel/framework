<?php

namespace Illuminate\Tests\Filesystem;

use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Config\Repository as Config;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;

class LocalFilesystemTest extends TestCase
{
    protected $container;
    private $tempDir;

    public function setUp()
    {
        $this->tempDir = __DIR__.'/tmp';
        mkdir($this->tempDir);

        Container::setInstance($this->container = new Container);

        $this->container->singleton('config', function () {
            return new Config([
                'filesystems' => [
                    'default' => 'local',
                    'disks' => [
                        'local' => [
                            'driver' => 'local',
                            'root' => $this->tempDir,
                        ],
                    ],
                ],
            ]);
        });

        $this->container->singleton(FilesystemManager::class, function () {
            return new FilesystemManager($this->container);
        });
    }

    public function tearDown()
    {
        $files = new Filesystem();
        $files->deleteDirectory($this->tempDir);
    }

    public function testLocalAdapter()
    {
        $adapter = $this->disk('local');

        $this->assertInstanceOf(FilesystemAdapter::class, $adapter);
        $this->assertTrue($adapter->getConfig()->has('root'));
    }

    public function providePaths()
    {
        return [
            [null],
            ['toto.txt'],
            ['folder/folder/file.csv'],
        ];
    }

    /**
     * @dataProvider providePaths
     */
    public function testLocalUrlGenerationFromRoot($path)
    {
        $root = rtrim($this->tempDir).'/';
        $adapter = $this->disk('local');

        $this->assertEquals($root.$path, $adapter->url($path));
    }

    public function provideUrlAndPath()
    {
        return [
            [null, 'toto.txt', '/toto.txt'],
            [$this->tempDir.'/', 'toto.txt', $this->tempDir.'/toto.txt'],
            ['/', 'folder/folder/file.csv', '/folder/folder/file.csv'],
        ];
    }

    /**
     * @dataProvider provideUrlAndPath
     */
    public function testLocalUrlGenerationFromUrl($url, $path, $result)
    {
        $this->container['config']->set('filesystems.disks.local.url', $url);

        $adapter = $this->disk('local');
        $this->assertEquals($result, $adapter->url($path));
    }

    private function disk($name)
    {
        return $this->container->make(FilesystemManager::class)->disk($name);
    }
}
