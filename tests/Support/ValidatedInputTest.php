<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\ValidatedInput;
use PHPUnit\Framework\TestCase;

class ValidatedInputTest extends TestCase
{
    public function test_can_access_input()
    {
        $input = new ValidatedInput(['name' => 'Taylor', 'votes' => 100]);

        $this->assertEquals('Taylor', $input->name);
        $this->assertEquals('Taylor', $input['name']);
        $this->assertEquals(['name' => 'Taylor'], $input->only(['name']));
        $this->assertEquals(['name' => 'Taylor'], $input->except(['votes']));
        $this->assertEquals(['name' => 'Taylor', 'votes' => 100], $input->all());
    }

    public function test_can_merge_items()
    {
        $input = new ValidatedInput(['name' => 'Taylor']);

        $input = $input->merge(['votes' => 100]);

        $this->assertEquals('Taylor', $input->name);
        $this->assertEquals('Taylor', $input['name']);
        $this->assertEquals(['name' => 'Taylor'], $input->only(['name']));
        $this->assertEquals(['name' => 'Taylor'], $input->except(['votes']));
        $this->assertEquals(['name' => 'Taylor', 'votes' => 100], $input->all());
    }
}
