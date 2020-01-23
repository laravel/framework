<?php

namespace Illuminate\Tests\View;

use Illuminate\View\ComponentAttributeBag;
use PHPUnit\Framework\TestCase;

class ViewComponentAttributeBagTest extends TestCase
{
    public function testAttributeRetrieval()
    {
        $bag = new ComponentAttributeBag(['class' => 'font-bold', 'name' => 'test']);

        $this->assertEquals('class="mt-4 font-bold" name="test"', (string) $bag->merge(['class' => 'mt-4']));
        $this->assertEquals('class="mt-4 font-bold" name="test"', (string) $bag(['class' => 'mt-4']));
        $this->assertEquals('class="mt-4 font-bold"', (string) $bag->only('class')->merge(['class' => 'mt-4']));
        $this->assertEquals('class="mt-4 font-bold"', (string) $bag->merge(['class' => 'mt-4'])->only('class'));
        $this->assertEquals('class="mt-4 font-bold"', (string) $bag->only('class')(['class' => 'mt-4']));
        $this->assertEquals('font-bold', $bag->get('class'));
        $this->assertEquals('bar', $bag->get('foo', 'bar'));
        $this->assertEquals('font-bold', $bag['class']);

        $bag = new ComponentAttributeBag([]);

        $this->assertEquals('class="mt-4"', (string) $bag->merge(['class' => 'mt-4']));
    }
}
