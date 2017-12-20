<?php

namespace Illuminate\Tests\Hashing;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\Hash;

class FakeHasherTest extends TestCase
{
    public function testHashCanBeFaked()
    {
        Hash::fake();

        $this->assertEquals(crc32('test'), Hash::make('test'));
        $this->assertTrue(Hash::check('test', Hash::make('test')));
        $this->assertFalse(Hash::check('wrong', Hash::make('test')));
        $this->assertTrue(is_array(Hash::info('example')));
    }
}
