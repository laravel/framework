<?php

namespace Illuminate\Tests\View;

use Illuminate\View\ComponentAttributeBag;
use PHPUnit\Framework\TestCase;

class ViewComponentAttributeBagTest extends TestCase
{
    public function testAttributeRetrieval()
    {
        $bag = new ComponentAttributeBag(['class' => 'font-bold', 'name' => 'test']);

        $this->assertSame('class="font-bold"', (string) $bag->whereStartsWith('class'));
        $this->assertSame('font-bold', (string) $bag->whereStartsWith('class')->first());
        $this->assertSame('name="test"', (string) $bag->whereDoesntStartWith('class'));
        $this->assertSame('test', (string) $bag->whereDoesntStartWith('class')->first());
        $this->assertSame('class="mt-4 font-bold" name="test"', (string) $bag->merge(['class' => 'mt-4']));
        $this->assertSame('class="mt-4 font-bold" name="test"', (string) $bag->merge(['class' => 'mt-4', 'name' => 'foo']));
        $this->assertSame('class="mt-4 font-bold" id="bar" name="test"', (string) $bag->merge(['class' => 'mt-4', 'id' => 'bar']));
        $this->assertSame('class="mt-4 font-bold" name="test"', (string) $bag(['class' => 'mt-4']));
        $this->assertSame('class="mt-4 font-bold"', (string) $bag->only('class')->merge(['class' => 'mt-4']));
        $this->assertSame('name="test" class="font-bold"', (string) $bag->merge(['name' => 'default']));
        $this->assertSame('class="font-bold" name="test"', (string) $bag->merge([]));
        $this->assertSame('class="mt-4 font-bold"', (string) $bag->merge(['class' => 'mt-4'])->only('class'));
        $this->assertSame('class="mt-4 font-bold"', (string) $bag->only('class')(['class' => 'mt-4']));
        $this->assertSame('font-bold', $bag->get('class'));
        $this->assertSame('bar', $bag->get('foo', 'bar'));
        $this->assertSame('font-bold', $bag['class']);
        $this->assertSame('class="mt-4 font-bold" name="test"', (string) $bag->class('mt-4'));
        $this->assertSame('class="mt-4 font-bold" name="test"', (string) $bag->class(['mt-4']));
        $this->assertSame('class="mt-4 ml-2 font-bold" name="test"', (string) $bag->class(['mt-4', 'ml-2' => true, 'mr-2' => false]));

        $bag = new ComponentAttributeBag(['class' => 'font-bold', 'name' => 'test', 'style' => 'margin-top: 10px']);
        $this->assertSame('class="mt-4 ml-2 font-bold" style="margin-top: 10px;" name="test"', (string) $bag->class(['mt-4', 'ml-2' => true, 'mr-2' => false]));
        $this->assertSame('style="margin-top: 4px; margin-left: 10px; margin-top: 10px;" class="font-bold" name="test"', (string) $bag->style(['margin-top: 4px', 'margin-left: 10px;']));

        $bag = new ComponentAttributeBag(['class' => 'font-bold', 'name' => 'test', 'style' => 'margin-top: 10px; font-weight: bold']);
        $this->assertSame('class="mt-4 ml-2 font-bold" style="margin-top: 10px; font-weight: bold;" name="test"', (string) $bag->class(['mt-4', 'ml-2' => true, 'mr-2' => false]));
        $this->assertSame('style="margin-top: 4px; margin-left: 10px; margin-top: 10px; font-weight: bold;" class="font-bold" name="test"', (string) $bag->style(['margin-top: 4px', 'margin-left: 10px;']));

        $bag = new ComponentAttributeBag([]);

        $this->assertSame('class="mt-4"', (string) $bag->merge(['class' => 'mt-4']));

        $bag = new ComponentAttributeBag([
            'test-string' => 'ok',
            'test-null' => null,
            'test-false' => false,
            'test-true' => true,
            'test-0' => 0,
            'test-0-string' => '0',
            'test-empty-string' => '',
        ]);

        $this->assertSame('test-string="ok" test-true="test-true" test-0="0" test-0-string="0" test-empty-string=""', (string) $bag);
        $this->assertSame('test-string="ok" test-true="test-true" test-0="0" test-0-string="0" test-empty-string=""', (string) $bag->merge());

        $bag = (new ComponentAttributeBag)
            ->merge([
                'test-escaped' => '<tag attr="attr">',
            ]);

        $this->assertSame('test-escaped="&lt;tag attr=&quot;attr&quot;&gt;"', (string) $bag);

        $bag = (new ComponentAttributeBag)
            ->merge([
                'test-string' => 'ok',
                'test-null' => null,
                'test-false' => false,
                'test-true' => true,
                'test-0' => 0,
                'test-0-string' => '0',
                'test-empty-string' => '',
            ]);

        $this->assertSame('test-string="ok" test-true="test-true" test-0="0" test-0-string="0" test-empty-string=""', (string) $bag);

        $bag = (new ComponentAttributeBag)
            ->merge([
                'test-extract-1' => 'extracted-1',
                'test-extract-2' => 'extracted-2',
                'test-discard-1' => 'discarded-1',
                'test-discard-2' => 'discarded-2',
            ]);

        $this->assertSame('test-extract-1="extracted-1" test-extract-2="extracted-2"', (string) $bag->exceptProps([
            'test-discard-1',
            'test-discard-2' => 'defaultValue',
        ]));

        $bag = (new ComponentAttributeBag)
            ->merge([
                'test-extract-1' => 'extracted-1',
                'test-extract-2' => 'extracted-2',
                'test-discard-1' => 'discarded-1',
                'test-discard-2' => 'discarded-2',
            ]);

        $this->assertSame('test-extract-1="extracted-1" test-extract-2="extracted-2"', (string) $bag->onlyProps([
            'test-extract-1',
            'test-extract-2' => 'defaultValue',
        ]));

        // Test only() method
        $bag = new ComponentAttributeBag(['class' => 'font-bold', 'name' => 'test', 'id' => 'my-id']);
        $this->assertInstanceOf(ComponentAttributeBag::class, $bag->only('class'));
        $this->assertSame('class="font-bold"', (string) $bag->only('class'));
        $this->assertSame('class="font-bold" name="test"', (string) $bag->only(['class', 'name']));
        $this->assertSame('', (string) $bag->only('missing'));
        $this->assertSame('name="test"', (string) $bag->only(['name', 'missing']));

        // Test except() method
        $this->assertInstanceOf(ComponentAttributeBag::class, $bag->except('class'));
        $this->assertSame('name="test" id="my-id"', (string) $bag->except('class'));
        $this->assertSame('id="my-id"', (string) $bag->except(['class', 'name']));
        $this->assertSame('class="font-bold" name="test" id="my-id"', (string) $bag->except('missing'));
        $this->assertSame('class="font-bold" id="my-id"', (string) $bag->except(['name', 'missing']));
    }

    public function testAttributeRetrievalUsingDotNotation()
    {
        $bag = new ComponentAttributeBag([
            'data.config' => 'value1',
            'x-on:click.prevent' => 'handler',
            'wire:model.lazy' => 'username',
            '@submit.prevent' => 'submitForm',
            'wire:model.debounce.500ms' => 'search',
        ]);

        $this->assertFalse($bag->has('data'));
        $this->assertFalse($bag->has('wire:model.debounce'));

        $this->assertTrue($bag->has('data.config'));
        $this->assertTrue($bag->has('x-on:click.prevent'));
        $this->assertTrue($bag->has('wire:model.lazy'));
        $this->assertTrue($bag->has('@submit.prevent'));
        $this->assertTrue($bag->has('wire:model.debounce.500ms'));

        $this->assertSame('value1', $bag->get('data.config'));
        $this->assertSame('handler', $bag->get('x-on:click.prevent'));
        $this->assertSame('username', $bag->get('wire:model.lazy'));
        $this->assertSame('submitForm', $bag->get('@submit.prevent'));
        $this->assertSame('search', $bag->get('wire:model.debounce.500ms'));
    }

    public function testItMakesAnExceptionForAlpineXdata()
    {
        $bag = new ComponentAttributeBag([
            'required' => true,
            'x-data' => true,
        ]);

        $this->assertSame('required="required" x-data=""', (string) $bag);
    }

    public function testItMakesAnExceptionForLivewireWireAttributes()
    {
        $bag = new ComponentAttributeBag([
            'wire:loading' => true,
            'wire:loading.remove' => true,
            'wire:poll' => true,
        ]);

        $this->assertSame('wire:loading="" wire:loading.remove="" wire:poll=""', (string) $bag);
    }

    public function testAttributeExistence()
    {
        $bag = new ComponentAttributeBag(['name' => 'test', 'href' => '', 'src' => null]);

        $this->assertTrue($bag->has('src'));
        $this->assertTrue($bag->has('href'));
        $this->assertTrue($bag->has('name'));
        $this->assertTrue($bag->has(['name']));
        $this->assertTrue($bag->hasAny(['class', 'name']));
        $this->assertTrue($bag->hasAny('class', 'name'));
        $this->assertFalse($bag->missing('name'));
        $this->assertFalse($bag->has('class'));
        $this->assertFalse($bag->has(['class']));
        $this->assertFalse($bag->has(['name', 'class']));
        $this->assertFalse($bag->has('name', 'class'));
        $this->assertTrue($bag->missing('class'));
    }

    public function testAttributeIsEmpty()
    {
        $bag = new ComponentAttributeBag([]);

        $this->assertTrue((bool) $bag->isEmpty());
    }

    public function testAttributeIsNotEmpty()
    {
        $bag = new ComponentAttributeBag(['name' => 'test']);

        $this->assertTrue((bool) $bag->isNotEmpty());
    }

    public function testAttributeIsArray()
    {
        $bag = new ComponentAttributeBag([
            'name' => 'test',
            'class' => 'font-bold',
        ]);

        $this->assertIsArray($bag->toArray());
        $this->assertEquals(['name' => 'test', 'class' => 'font-bold'], $bag->toArray());
    }

    public function testFilled()
    {
        $bag = new ComponentAttributeBag([
            'name' => 'test',
            'class' => 'font-bold',
            'empty' => '',
            'whitespace' => '   ',
            'zero' => '0',
            'false' => false,
            'null' => null,
        ]);

        $this->assertTrue($bag->filled('name'));
        $this->assertTrue($bag->filled('class'));
        $this->assertTrue($bag->filled('zero'));
        $this->assertTrue($bag->filled('false'));
        $this->assertFalse($bag->filled('null'));
        $this->assertFalse($bag->filled('empty'));
        $this->assertFalse($bag->filled('whitespace'));
        $this->assertFalse($bag->filled('nonexistent'));

        // Multiple keys
        $this->assertTrue($bag->filled(['name', 'class']));
        $this->assertFalse($bag->filled(['name', 'empty']));
        $this->assertTrue($bag->filled('name', 'class'));
        $this->assertFalse($bag->filled('name', 'empty'));
    }

    public function testIsNotFilled()
    {
        $bag = new ComponentAttributeBag([
            'name' => 'test',
            'empty' => '',
            'whitespace' => '   ',
        ]);

        $this->assertFalse($bag->isNotFilled('name'));
        $this->assertTrue($bag->isNotFilled('empty'));
        $this->assertTrue($bag->isNotFilled('whitespace'));
        $this->assertTrue($bag->isNotFilled('nonexistent'));

        // Multiple keys - all must be empty
        $this->assertTrue($bag->isNotFilled(['empty', 'whitespace']));
        $this->assertFalse($bag->isNotFilled(['name', 'empty']));
    }

    public function testAnyFilled()
    {
        $bag = new ComponentAttributeBag([
            'name' => 'test',
            'empty' => '',
            'whitespace' => '   ',
        ]);

        $this->assertTrue($bag->anyFilled(['name', 'empty']));
        $this->assertTrue($bag->anyFilled(['empty', 'name']));
        $this->assertFalse($bag->anyFilled(['empty', 'whitespace']));
        $this->assertTrue($bag->anyFilled('name', 'empty'));
        $this->assertFalse($bag->anyFilled('empty', 'whitespace'));
    }

    public function testWhenFilled()
    {
        $bag = new ComponentAttributeBag([
            'name' => 'test',
            'empty' => '',
        ]);

        $result = $bag->whenFilled('name', function ($value) {
            return 'callback-'.$value;
        });
        $this->assertEquals('callback-test', $result);

        $result = $bag->whenFilled('empty', function ($value) {
            return 'callback-'.$value;
        });
        $this->assertSame($bag, $result);

        $result = $bag->whenFilled('empty', function ($value) {
            return 'callback-'.$value;
        }, function () {
            return 'default-callback';
        });
        $this->assertEquals('default-callback', $result);
    }

    public function testWhenHas()
    {
        $bag = new ComponentAttributeBag(['name' => 'test']);

        $result = $bag->whenHas('name', function ($value) {
            return 'callback-'.$value;
        });
        $this->assertEquals('callback-test', $result);

        $result = $bag->whenHas('missing', function ($value) {
            return 'callback-'.$value;
        });
        $this->assertSame($bag, $result);

        $result = $bag->whenHas('missing', function ($value) {
            return 'callback-'.$value;
        }, function () {
            return 'default-callback';
        });
        $this->assertEquals('default-callback', $result);
    }

    public function testWhenMissing()
    {
        $bag = new ComponentAttributeBag(['name' => 'test']);

        $result = $bag->whenMissing('name', function () {
            return 'callback';
        });
        $this->assertSame($bag, $result);

        $result = $bag->whenMissing('missing', function () {
            return 'callback';
        });
        $this->assertEquals('callback', $result);

        $result = $bag->whenMissing('name', function () {
            return 'callback';
        }, function () {
            return 'default-callback';
        });
        $this->assertEquals('default-callback', $result);
    }

    public function testString()
    {
        $bag = new ComponentAttributeBag([
            'name' => 'test',
            'empty' => '',
            'number' => 123,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Stringable::class, $bag->string('name'));
        $this->assertEquals('test', (string) $bag->string('name'));
        $this->assertEquals('', (string) $bag->string('empty'));
        $this->assertEquals('123', (string) $bag->string('number'));
        $this->assertEquals('default', (string) $bag->string('missing', 'default'));
    }

    public function testBoolean()
    {
        $bag = new ComponentAttributeBag([
            'true_string' => 'true',
            'false_string' => 'false',
            'one' => '1',
            'zero' => '0',
            'yes' => 'yes',
            'no' => 'no',
            'on' => 'on',
            'off' => 'off',
        ]);

        $this->assertTrue($bag->boolean('true_string'));
        $this->assertFalse($bag->boolean('false_string'));
        $this->assertTrue($bag->boolean('one'));
        $this->assertFalse($bag->boolean('zero'));
        $this->assertTrue($bag->boolean('yes'));
        $this->assertFalse($bag->boolean('no'));
        $this->assertTrue($bag->boolean('on'));
        $this->assertFalse($bag->boolean('off'));
        $this->assertTrue($bag->boolean('missing', true));
        $this->assertFalse($bag->boolean('missing', false));
    }

    public function testInteger()
    {
        $bag = new ComponentAttributeBag([
            'number' => '123',
            'float' => '123.45',
            'string' => 'abc',
        ]);

        $this->assertSame(123, $bag->integer('number'));
        $this->assertSame(123, $bag->integer('float'));
        $this->assertSame(0, $bag->integer('string'));
        $this->assertSame(42, $bag->integer('missing', 42));
    }

    public function testFloat()
    {
        $bag = new ComponentAttributeBag([
            'number' => '123',
            'float' => '123.45',
            'string' => 'abc',
        ]);

        $this->assertSame(123.0, $bag->float('number'));
        $this->assertSame(123.45, $bag->float('float'));
        $this->assertSame(0.0, $bag->float('string'));
        $this->assertSame(42.5, $bag->float('missing', 42.5));
    }

    public function testExists()
    {
        $bag = new ComponentAttributeBag(['name' => 'test']);

        $this->assertTrue($bag->exists('name'));
        $this->assertFalse($bag->exists('missing'));
        $this->assertTrue($bag->exists(['name']));
        $this->assertFalse($bag->exists(['missing']));
    }
}
