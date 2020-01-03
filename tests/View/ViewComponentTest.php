<?php

namespace Illuminate\Tests\View;

use Illuminate\View\Component;
use PHPUnit\Framework\TestCase;

class ViewFactoryTest extends TestCase
{
    public function testAttributeRetrieval()
    {
        $component = new TestViewComponent;
        $component->withAttributes(['class' => 'font-bold', 'name' => 'test']);

        $this->assertEquals('class="mt-4 font-bold" name="test"', (string) $component->attributes(['class' => 'mt-4']));
    }
}

class TestViewComponent extends Component
{
    public function view()
    {
        return 'test';
    }
}
