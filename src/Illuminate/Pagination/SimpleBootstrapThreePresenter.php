<?php

namespace Illuminate\Pagination;

use Illuminate\View\Expression;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;

class SimpleBootstrapThreePresenter extends BootstrapThreePresenter
{
    /**
     * Create a simple Bootstrap 3 presenter.
     *
     * @param  \Illuminate\Contracts\Pagination\Paginator  $paginator
     * @return void
     */
    public function __construct(PaginatorContract $paginator)
    {
        $this->paginator = $paginator;
    }

    /**
     * Determine if the underlying paginator being presented has pages to show.
     *
     * @return bool
     */
    public function hasPages()
    {
        return $this->paginator->hasPages() && count($this->paginator->items()) > 0;
    }

    /**
     * Convert the URL window into Bootstrap HTML.
     *
     * @return \Illuminate\View\Expression
     */
    public function render()
    {
        if ($this->hasPages()) {
            return new Expression(sprintf(
                '<ul class="pager">%s %s</ul>',
                $this->getPreviousButton(),
                $this->getNextButton()
            ));
        }

        return '';
    }
}
