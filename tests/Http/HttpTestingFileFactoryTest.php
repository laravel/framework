<?php

namespace Illuminate\Tests\Http;

use PHPUnit\Framework\TestCase;
use Illuminate\Http\Testing\FileFactory;

class HttpTestingFileFactoryTest extends TestCase
{
    public function testImagePng()
    {
        $image = (new FileFactory)->image('test.png', 15, 20);

        $info = getimagesize($image->getRealPath());

        $this->assertSame('image/png', $info['mime']);
        $this->assertSame(15, $info[0]);
        $this->assertSame(20, $info[1]);
    }

    public function testImageJpeg()
    {
        $image = (new FileFactory)->image('test.jpeg', 15, 20);

        $info = getimagesize($image->getRealPath());

        $this->assertSame('image/jpeg', $info['mime']);
        $this->assertSame(15, $info[0]);
        $this->assertSame(20, $info[1]);
    }

    public function testCreateFromFile()
    {
        $file = (new FileFactory)->createFromFile('test.txt', __DIR__ .'/fixtures/test.txt');

        $this->assertEquals('This is a story about something that happened long ago when your grandfather was a child.',
            trim($file->get()));
        $this->assertSame('test.txt', $file->name);
    }
}
