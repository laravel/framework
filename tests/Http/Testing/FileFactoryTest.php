<?php

namespace Illuminate\Tests\Http\Testing;

use PHPUnit\Framework\TestCase;
use Illuminate\Http\Testing\FileFactory;

class FileFactoryTest extends TestCase
{
    public function testCreateFakeImage()
    {
        $this->assertTrue(
            (new FileFactory)->image('fake.jpg')->isReadable()
        );
    }
}
