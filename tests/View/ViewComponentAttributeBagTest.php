<?php

namespace Illuminate\Tests\View;

use Illuminate\Tests\Support\Concerns\TestsInteractsWithDataContract;
use Illuminate\View\ComponentAttributeBag;
use PHPUnit\Framework\TestCase;

class ViewComponentAttributeBagTest extends TestCase
{
    use TestsInteractsWithDataContract;

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
    }

    public function testOnly()
    {
        $bag = new ComponentAttributeBag(['class' => 'font-bold', 'name' => 'test', 'id' => 'my-id']);

        $this->assertInstanceOf(ComponentAttributeBag::class, $bag->only('class'));
        $this->assertSame('class="font-bold"', (string) $bag->only('class'));
        $this->assertSame('class="font-bold" name="test"', (string) $bag->only(['class', 'name']));
        $this->assertSame('', (string) $bag->only('missing'));
        $this->assertSame('name="test"', (string) $bag->only(['name', 'missing']));
        $this->assertSame('class="font-bold" name="test" id="my-id"', (string) $bag->only(null));
        $this->assertSame(['class' => 'font-bold'], $bag->all('class'));
    }

    public function testExcept()
    {
        $bag = new ComponentAttributeBag(['class' => 'font-bold', 'name' => 'test', 'id' => 'my-id']);

        $this->assertInstanceOf(ComponentAttributeBag::class, $bag->except('class'));
        $this->assertSame('name="test" id="my-id"', (string) $bag->except('class'));
        $this->assertSame('id="my-id"', (string) $bag->except(['class', 'name']));
        $this->assertSame('class="font-bold" name="test" id="my-id"', (string) $bag->except('missing'));
        $this->assertSame('class="font-bold" id="my-id"', (string) $bag->except(['name', 'missing']));
        $this->assertSame('class="font-bold" name="test" id="my-id"', (string) $bag->except(null));
    }

    public function testHas()
    {
        $this->assertDataHasContract();
    }

    public function testHasAny()
    {
        $this->assertDataHasAnyContract();
    }

    public function testWhenEnum()
    {
        $this->assertDataWhenEnumContract();
    }

    public function testMissing()
    {
        $this->assertDataMissingContract();
    }

    public function testStr()
    {
        $this->assertDataStrContract();
    }

    public function testClamp()
    {
        $this->assertDataClampContract();
    }

    public function testDate()
    {
        $this->assertDataDateContract();
    }

    public function testInterval()
    {
        $this->assertDataIntervalContract();
    }

    public function testEnum()
    {
        $this->assertDataEnumContract();
    }

    public function testEnums()
    {
        $this->assertDataEnumsContract();
    }

    public function testArray()
    {
        $this->assertDataArrayContract();
        $this->assertDataArrayArrayableContract();
    }

    public function testCollect()
    {
        $this->assertDataCollectContract();
    }

    public function testNullValues()
    {
        $this->assertDataNullValuesContract();

        $bag = new ComponentAttributeBag(['city' => null]);

        $this->assertSame('default', $bag->string('city', 'default')->value());
        $this->assertTrue($bag->boolean('city', true));
        $this->assertSame(42, $bag->integer('city', 42));
        $this->assertSame(4.2, $bag->float('city', 4.2));
    }

    public function testMergeTerminatesDefaultStyleWithSemicolon()
    {
        $bag = new ComponentAttributeBag(['style' => 'color: blue']);

        // The default style value is not terminated with a semicolon, so one
        // should be added to keep the resulting declaration list valid.
        $this->assertSame('style="font-weight: bold; color: blue;"', (string) $bag->merge(['style' => 'font-weight: bold']));

        $bag = new ComponentAttributeBag(['style' => 'color: blue']);

        // A default style value that already ends with a semicolon must not gain a second one.
        $this->assertSame('style="font-weight: bold; color: blue;"', (string) $bag->merge(['style' => 'font-weight: bold;']));

        // When no default style is provided the existing value should be returned untouched.
        $bag = new ComponentAttributeBag(['style' => 'color: blue']);

        $this->assertSame('style="color: blue;"', (string) $bag->merge([]));
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
        $this->assertDataFilledContract();
    }

    public function testIsNotFilled()
    {
        $this->assertDataIsNotFilledContract();
    }

    public function testAnyFilled()
    {
        $this->assertDataAnyFilledContract();
    }

    public function testWhenFilled()
    {
        $this->assertDataWhenFilledContract();
    }

    public function testWhenHas()
    {
        $this->assertDataWhenHasContract();
    }

    public function testWhenMissing()
    {
        $this->assertDataWhenMissingContract();
    }

    public function testString()
    {
        $this->assertDataStringContract();
        $this->assertDataStringStrictContract();
    }

    public function testBoolean()
    {
        $this->assertDataBooleanContract();
        $this->assertDataBooleanStrictContract();
        $this->assertDataBooleanStrictNativeValuesContract();
    }

    public function testInteger()
    {
        $this->assertDataIntegerContract();
        $this->assertDataIntegerStrictContract();
    }

    public function testFloat()
    {
        $this->assertDataFloatContract();
        $this->assertDataFloatStrictContract();
    }

    public function testExists()
    {
        $this->assertDataExistsContract();
    }

    protected static function arrayToDataFactory(array $data)
    {
        return new ComponentAttributeBag($data);
    }
}
