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

        $bag = new ComponentAttributeBag([]);

        $this->assertSame('class="flex"', (string) $bag->make('class', 'flex'));
        $this->assertSame('class="flex mt-2 mx-4"', (string) $bag->make('class', ['flex', 'mt-2', 'mx-4' => true, 'mx-2' => false]));
        $this->assertSame('disabled="disabled"', (string) $bag->make('disabled', true));
        $this->assertSame('class="flex"', (string) $bag->make('class', ['flex' => true, 'grid' => false]));
        $this->assertSame('class="grid"', (string) $bag->make('class', ['flex' => false, 'grid' => true]));
        $this->assertSame('class="flex" disabled="disabled"', (string) $bag->make(['class' => ['flex' => true], 'disabled' => true]));
        $this->assertSame('class="flex" disabled="disabled"', (string) $bag->make(['class' => ['flex' => true], 'disabled']));
        $this->assertSame('class="flex disabled" disabled="disabled"', (string) $bag->make(['class' => ['flex' => true, 'disabled' => true], 'disabled' => true]));
        $this->assertSame('class="flex selected" selected="selected"', (string) $bag->make(['class' => ['flex' => true, 'disabled' => false, 'selected' => true], 'disabled' => false, 'selected' => true]));
        $this->assertSame('class="flex p-4" method="POST"', (string) $bag->make(['class' => ['flex' => true, 'grid' => false, 'p-4'], 'method' => ['GET' => false, 'POST' => true]]));
        $this->assertSame('type="button"', (string) $bag->make('type', 'button'));
        $this->assertSame('type="submit"', (string) $bag->make('type', ['button' => false, 'submit' => true]));
        $this->assertSame('', (string) $bag->make('type', ['button' => false]));
        $this->assertSame('class="edit create"', (string) $bag->make('class', ['edit' => true, 'create' => true]));
        $this->assertSame('id="edit"', (string) $bag->make('id', ['edit' => true, 'create' => true]));
        $this->assertSame('id="create"', (string) $bag->make('id', ['edit' => false, 'create' => true, 'delete' => true]));
        $this->assertSame('id="create"', (string) $bag->make('id', ['edit' => false, 'create']));
        $this->assertSame('id="create" class="edit create"', (string) $bag->make(['id' => ['edit' => false, 'create'], 'class' => ['edit' => true, 'create' => true]]));

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
    }
}
