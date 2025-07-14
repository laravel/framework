<?php

namespace Illuminate\Pagination;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Pagination extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        protected LengthAwarePaginator|Paginator $paginator,
        protected ?string $view = null,
        protected array $data = [],
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        //length aware paginator
        if ($this->paginator instanceof LengthAwarePaginator) {
            //get elements
            $window = UrlWindow::make($this->paginator);

            $elements = array_filter([
                $window['first'],
                is_array($window['slider']) ? '...' : null,
                $window['slider'],
                is_array($window['last']) ? '...' : null,
                $window['last'],
            ]);
        }

        //determine view
        $view = $this->view ?? $this->paginator instanceof LengthAwarePaginator
            ? AbstractPaginator::$defaultView
            : AbstractPaginator::$defaultSimpleView;

        //load view
        return view($view)
            ->with([
                ...$this->data,
                'paginator' => $this->paginator,
                'elements' => $elements ?? [],
            ]);
    }
}
