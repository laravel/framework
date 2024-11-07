<?php

namespace Illuminate\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class MethodField extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        protected string $value,
        protected string $name = '_method',
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('laravel::method-field')
            ->with([
                'name'  => $this->name,
                'value' => $this->value,
            ]);
    }
}
