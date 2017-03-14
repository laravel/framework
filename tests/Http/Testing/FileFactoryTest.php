<?php

namespace Illuminate\Tests\Http\Testing;

use PHPUnit\Framework\TestCase;
use Illuminate\Http\Testing\FileFactory;

class FileFactoryTest extends TestCase
{
    public function testCreateFakeFile()
    {
        $this->assertTrue(
            (new FileFactory)->create('document.pdf')->isReadable()
        );
    }

    public function testCreateFakeImage()
    {
        $this->assertTrue(
            (new FileFactory)->image('image.jpg')->isReadable()
        );
    }

    public function testCreateFakeImageWithSpecificSize()
    {
        $image = (new FileFactory)->image('image.jpg', 5, 10);

        $info = getimagesize($image->path());

        $this->assertEquals(5, $info[0]);
        $this->assertEquals(10, $info[1]);
    }
}
