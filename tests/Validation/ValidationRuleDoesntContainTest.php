<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

include_once 'Enums.php';

class ValidationRuleDoesntContainTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = Rule::doesntContain('Taylor');
        $this->assertSame('doesnt_contain:"Taylor"', (string) $rule);

        $rule = Rule::doesntContain('Taylor', 'Abigail');
        $this->assertSame('doesnt_contain:"Taylor","Abigail"', (string) $rule);

        $rule = Rule::doesntContain(['Taylor', 'Abigail']);
        $this->assertSame('doesnt_contain:"Taylor","Abigail"', (string) $rule);

        $rule = Rule::doesntContain(collect(['Taylor', 'Abigail']));
        $this->assertSame('doesnt_contain:"Taylor","Abigail"', (string) $rule);

        $rule = Rule::doesntContain([ArrayKeys::key_1, ArrayKeys::key_2]);
        $this->assertSame('doesnt_contain:"key_1","key_2"', (string) $rule);

        $rule = Rule::doesntContain([ArrayKeysBacked::key_1, ArrayKeysBacked::key_2]);
        $this->assertSame('doesnt_contain:"key_1","key_2"', (string) $rule);

        $rule = Rule::doesntContain(['Taylor', 'Taylor']);
        $this->assertSame('doesnt_contain:"Taylor","Taylor"', (string) $rule);

        $rule = Rule::doesntContain([1, 2, 3]);
        $this->assertSame('doesnt_contain:"1","2","3"', (string) $rule);

        $rule = Rule::doesntContain(['"foo"', '"bar"', '"baz"']);
        $this->assertSame('doesnt_contain:"""foo""","""bar""","""baz"""', (string) $rule);
    }

    public function testDoesntContainValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        // Test fails when value is string
        $v = new Validator($trans, ['roles' => 'admin'], ['roles' => Rule::doesntContain('admin')]);
        $this->assertTrue($v->fails());

        // Test fails when array contains the value
        $v = new Validator($trans, ['roles' => ['admin', 'user']], ['roles' => Rule::doesntContain('admin')]);
        $this->assertTrue($v->fails());

        // Test fails when array contains all the values (using array argument)
        $v = new Validator($trans, ['roles' => ['admin', 'user']], ['roles' => Rule::doesntContain(['admin', 'editor'])]);
        $this->assertTrue($v->fails());

        // Test fails when array contains some of the values (using multiple arguments)
        $v = new Validator($trans, ['roles' => ['admin', 'user']], ['roles' => Rule::doesntContain('subscriber', 'admin')]);
        $this->assertTrue($v->fails());

        // Test passes when array does not contain any value
        $v = new Validator($trans, ['roles' => ['subscriber', 'guest']], ['roles' => Rule::doesntContain(['admin', 'editor'])]);
        $this->assertTrue($v->passes());

        // Test fails when array includes a value (using string-like format)
        $v = new Validator($trans, ['roles' => ['admin', 'user']], ['roles' => 'doesnt_contain:admin']);
        $this->assertTrue($v->fails());

        // Test passes when array doesn't include a value (using string-like format)
        $v = new Validator($trans, ['roles' => ['admin', 'user']], ['roles' => 'doesnt_contain:editor']);
        $this->assertTrue($v->passes());

        // Test fails when array doesn't contain the value
        $v = new Validator($trans, ['roles' => ['admin', 'user']], ['roles' => Rule::doesntContain('admin')]);
        $this->assertTrue($v->fails());

        // Test with empty array
        $v = new Validator($trans, ['roles' => []], ['roles' => Rule::doesntContain('admin')]);
        $this->assertTrue($v->passes());

        // Test with nullable field
        $v = new Validator($trans, ['roles' => null], ['roles' => ['nullable', Rule::doesntContain('admin')]]);
        $this->assertTrue($v->passes());
    }
}
