<?php

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Mimes;

class ValidationMimesRuleTest extends PHPUnit_Framework_TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $type = new Mimes(['types' => ['video/avi']]);
        $this->assertEquals('mimetypes:video/avi', (string) $type);

        $rule = new Mimes(['rules' => ['png']]);
        $this->assertEquals('mimes:png', (string) $rule);

        $type = Rule::mime()->type('video/avi');
        $this->assertEquals('mimetypes:video/avi', (string) $type);

        $rule = Rule::mime()->rule('png');
        $this->assertEquals('mimes:png', (string) $rule);

        $multipleTypes = Rule::mime()->type(['video/avi','video/mpeg']);
        $this->assertEquals('mimetypes:video/avi,video/mpeg', (string) $multipleTypes);

        $multipleRules = Rule::mime()->rule(['gif','png']);
        $this->assertEquals('mimes:gif,png', (string) $multipleRules);
    }
}