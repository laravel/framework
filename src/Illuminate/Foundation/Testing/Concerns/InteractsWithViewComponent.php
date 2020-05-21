<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Support\Facades\Blade;

trait InteractsWithViewComponent
{
    protected function assertHasViewComponent($alias, $component = null)
    {
        $components = Blade::getClassComponentAliases();

        if (is_null($component)) {
            $this->assertTrue(
                array_key_exists($alias, $components) || in_array($alias, $components)
            );
        } else {
            $this->assertTrue(
                $components[$alias] === $component
            );
        }
    }
}