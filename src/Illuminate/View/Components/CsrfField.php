<?php

namespace Illuminate\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CsrfField extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        protected string $name = '_token',
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('laravel::csrf-field')
            ->with([
                'name'  => $this->name,
                'value' => csrf_token(),
            ]);
    }
}
