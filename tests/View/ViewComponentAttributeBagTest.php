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
    }

    public function testAttibuteExistence()
    {
        $bag = new ComponentAttributeBag(['name' => 'test']);

        $this->assertTrue((bool) $bag->has('name'));
        $this->assertFalse((bool) $bag->missing('name'));
        $this->assertFalse((bool) $bag->has('class'));
        $this->assertTrue((bool) $bag->missing('class'));
    }
}
