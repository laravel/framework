<?php

use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class FilesystemAdapterTest extends \PHPUnit_Framework_TestCase
{
    private $tempDir;

    public function setUp()
    {
        $this->tempDir = __DIR__.'/tmp';
        mkdir($this->tempDir, 0777);
    }

    public function testUrl()
    {
        $adapter = new FilesystemAdapter(new Filesystem(new Local($this->tempDir)));
        $actual = $adapter->url('foo.txt');
        $expected = $this->tempDir.DIRECTORY_SEPARATOR.'foo.txt';
        $this->assertEquals($expected, $actual);
    }

    public function tearDown()
    {
        rmdir($this->tempDir);
    }
}
