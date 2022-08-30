<?php

namespace Illuminate\Tests\Support;

use ArrayIterator;
use Illuminate\Support\Collection;
use Illuminate\Support\ValidatedInput;
use PHPUnit\Framework\TestCase;

class ValidatedInputTest extends TestCase
{
    public function test_can_access_input()
    {
        $input = new ValidatedInput(['name' => 'Taylor', 'votes' => 100]);

        $this->assertSame('Taylor', $input->name);
        $this->assertSame('Taylor', $input['name']);
        $this->assertEquals(['name' => 'Taylor'], $input->only(['name']));
        $this->assertEquals(['name' => 'Taylor'], $input->except(['votes']));
        $this->assertEquals(['name' => 'Taylor', 'votes' => 100], $input->all());
    }

    public function test_can_merge_items()
    {
        $input = new ValidatedInput(['name' => 'Taylor']);

        $input = $input->merge(['votes' => 100]);

        $this->assertSame('Taylor', $input->name);
        $this->assertSame('Taylor', $input['name']);
        $this->assertEquals(['name' => 'Taylor'], $input->only(['name']));
        $this->assertEquals(['name' => 'Taylor'], $input->except(['votes']));
        $this->assertEquals(['name' => 'Taylor', 'votes' => 100], $input->all());
    }

    public function test_input_existence()
    {
        $inputA = new ValidatedInput(['name' => 'Taylor']);

        $this->assertEquals(true, $inputA->has('name'));
        $this->assertEquals(true, $inputA->missing('votes'));
        $this->assertEquals(true, $inputA->missing(['votes']));
        $this->assertEquals(false, $inputA->missing('name'));

        $inputB = new ValidatedInput(['name' => 'Taylor', 'votes' => 100]);

        $this->assertEquals(true, $inputB->has(['name', 'votes']));
    }

    public function test_input_collect()
    {
        $input = new ValidatedInput(['name' => 'Taylor', 'votes' => 100]);

        $this->assertInstanceOf(Collection::class, $input->collect());
    }

    public function test_input_all()
    {
        $input = new ValidatedInput(['name' => 'Taylor', 'votes' => 100]);

        $this->assertEquals(['name' => 'Taylor', 'votes' => 100], $input->all());
    }

    public function test_input_toArray()
    {
        $input = new ValidatedInput(['name' => 'Taylor', 'votes' => 100]);

        $this->assertEquals(['name' => 'Taylor', 'votes' => 100], $input->toArray());
    }

    public function test_input_access_offsetExists()
    {
        $input = new ValidatedInput(['name' => 'Taylor', 'votes' => 100]);

        $this->assertTrue($input->offsetExists('name'));
        $this->assertTrue($input->offsetExists('votes'));
        $this->assertFalse($input->offsetExists('family'));
    }

    public function test_input_access_offsetSet()
    {
        $input = new ValidatedInput(['name' => 'Taylor', 'votes' => 100]);

        $input->offsetSet('name', 'Amir');
        $this->assertEquals('Amir', $input['name']);
        $this->assertNotEquals('Taylor', $input['name']);
    }

    public function test_input_access_offsetGet()
    {
        $input = new ValidatedInput(['name' => 'Taylor', 'votes' => 100]);

        $this->assertEquals('Taylor', $input->offsetGet('name'));
        $this->assertEquals(100, $input->offsetGet('votes'));
    }

    public function test_input_access_offsetUnset()
    {
        $input = new ValidatedInput(['name' => 'Taylor', 'votes' => 100]);

        $this->assertTrue(isset($input['name']));
        $input->offsetUnset('name');
        $this->assertFalse(isset($input['name']));
    }

    public function test_input_access_getIterator()
    {
        $input = new ValidatedInput(['name' => 'Taylor']);

        $this->assertInstanceOf(ArrayIterator::class, $input->getIterator());
        $this->assertEquals(['name' => 'Taylor'], $input->getIterator()->getArrayCopy());
    }
}
