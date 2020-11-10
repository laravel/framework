<?php

namespace Illuminate\Tests\Database\Concerns;

use Illuminate\Database\Eloquent\Concerns\GuardsAttributes;
use PHPUnit\Framework\TestCase;

class GuardsAttributesTest extends TestCase
{
    use GuardsAttributes;

    public function testIsGuarded(): void
    {
        $this->assertFalse($this->isGuarded('key'));
    }
}
