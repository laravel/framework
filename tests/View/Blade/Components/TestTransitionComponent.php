<?php

namespace Illuminate\Tests\View\Blade\Components;

use Illuminate\View\Component;

class TestTransitionComponent extends Component
{
    public function render()
    {
        return '<div {{ $attributes }}>{{ $slot }}</div>';
    }
}
