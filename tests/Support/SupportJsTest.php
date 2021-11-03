<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Js;
use PHPUnit\Framework\TestCase;

class SupportJsTest extends TestCase
{
    public function testJsFrom()
    {
        $this->assertEquals("'hey'", Js::from('hey'));
        $this->assertEquals("'don\'t mess with my path\\\path'", Js::from("don't mess with my path\path"));
        $this->assertEquals("JSON.parse(atob('eyJoZXkiOiJ0aGVyZSJ9'))", Js::from(['hey' => 'there']));
        $this->assertEquals("JSON.parse(atob('WyJoZXkiLCJ0aGVyZSJd'))", Js::from(['hey', 'there']));
    }
}
