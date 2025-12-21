<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

include_once 'Enums.php';

class ValidationRuleContainsTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = Rule::contains('Taylor');
        $this->assertSame('contains:"Taylor"', (string) $rule);

        $rule = Rule::contains('Taylor', 'Abigail');
        $this->assertSame('contains:"Taylor","Abigail"', (string) $rule);

        $rule = Rule::contains(['Taylor', 'Abigail']);
        $this->assertSame('contains:"Taylor","Abigail"', (string) $rule);

        $rule = Rule::contains(collect(['Taylor', 'Abigail']));
        $this->assertSame('contains:"Taylor","Abigail"', (string) $rule);

        $rule = Rule::contains([ArrayKeys::key_1, ArrayKeys::key_2]);
        $this->assertSame('contains:"key_1","key_2"', (string) $rule);

        $rule = Rule::contains([ArrayKeysBacked::key_1, ArrayKeysBacked::key_2]);
        $this->assertSame('contains:"key_1","key_2"', (string) $rule);

        $rule = Rule::contains(['Taylor', 'Taylor']);
        $this->assertSame('contains:"Taylor","Taylor"', (string) $rule);

        $rule = Rule::contains([1, 2, 3]);
        $this->assertSame('contains:"1","2","3"', (string) $rule);

        $rule = Rule::contains(['"foo"', '"bar"', '"baz"']);
        $this->assertSame('contains:"""foo""","""bar""","""baz"""', (string) $rule);
    }

    public function testContainsValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        // Test fails when value is string
        $v = new Validator($trans, ['roles' => 'admin'], ['roles' => Rule::contains('editor')]);
        $this->assertTrue($v->fails());

        // Test passes when array contains the value
        $v = new Validator($trans, ['roles' => ['admin', 'user']], ['roles' => Rule::contains('admin')]);
        $this->assertTrue($v->passes());

        // Test fails when array doesn't contain all the values
        $v = new Validator($trans, ['roles' => ['admin', 'user']], ['roles' => Rule::contains(['admin', 'editor'])]);
        $this->assertTrue($v->fails());

        // Test fails when array doesn't contain all the values (using multiple arguments)
        $v = new Validator($trans, ['roles' => ['admin', 'user']], ['roles' => Rule::contains('admin', 'editor')]);
        $this->assertTrue($v->fails());

        // Test passes when array contains all the values
        $v = new Validator($trans, ['roles' => ['admin', 'user', 'editor']], ['roles' => Rule::contains(['admin', 'editor'])]);
        $this->assertTrue($v->passes());

        // Test passes when array contains all the values (using multiple arguments)
        $v = new Validator($trans, ['roles' => ['admin', 'user', 'editor']], ['roles' => Rule::contains('admin', 'editor')]);
        $this->assertTrue($v->passes());

        // Test fails when array doesn't contain the value
        $v = new Validator($trans, ['roles' => ['admin', 'user']], ['roles' => Rule::contains('editor')]);
        $this->assertTrue($v->fails());

        // Test fails when array doesn't contain any of the values
        $v = new Validator($trans, ['roles' => ['admin', 'user']], ['roles' => Rule::contains(['editor', 'manager'])]);
        $this->assertTrue($v->fails());

        // Test with empty array
        $v = new Validator($trans, ['roles' => []], ['roles' => Rule::contains('admin')]);
        $this->assertTrue($v->fails());

        // Test with nullable field
        $v = new Validator($trans, ['roles' => null], ['roles' => ['nullable', Rule::contains('admin')]]);
        $this->assertTrue($v->passes());
    }
}
