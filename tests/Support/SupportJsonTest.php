<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Json;
use PHPUnit\Framework\TestCase;

class SupportJsonTest extends TestCase
{
    public function testJsonParse()
    {
        $this->assertEquals("'hey'", Json::parse('hey'));
        $this->assertEquals("'\/path\/path'", Json::parse('/path/path'));
        $this->assertEquals("JSON.parse(atob('eyJoZXkiOiJ0aGVyZSJ9'))", Json::parse(['hey' => 'there']));
        $this->assertEquals("JSON.parse(atob('WyJoZXkiLCJ0aGVyZSJd'))", Json::parse(['hey', 'there']));
    }

    public function testJsonEncode()
    {
        $this->assertEquals('"hey"', Json::encode('hey'));
        $this->assertEquals('"\/path\/path"', Json::encode('/path/path'));
        $this->assertEquals('{"hey":"there"}', Json::encode(['hey' => 'there']));
        $this->assertEquals('["hey","there"]', Json::encode(['hey', 'there']));
    }

    public function testJsonStr()
    {
        $this->assertEquals("'\/path\/path'", Json::str('/path/path'));
        $this->assertEquals("'hey'", Json::str('hey'));
    }

    public function testJsonBool()
    {
        $this->assertEquals('true', Json::bool((bool) 1));
        $this->assertEquals('false', Json::bool((bool) 0));
    }
}
