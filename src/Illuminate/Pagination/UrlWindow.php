<?php

namespace Illuminate\Pagination;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as PaginatorContract;

class UrlWindow
{
    /**
     * The paginator implementation.
     *
     * @var \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    protected $paginator;

    /**
     * Create a new URL window instance.
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator  $paginator
     * @return void
     */
    public function __construct(PaginatorContract $paginator)
    {
        $this->paginator = $paginator;
    }

    /**
     * Create a new URL window instance.
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator  $paginator
     * @param  int  $onEachSide
     * @param  int  $onEachEdge
     * @return array
     */
    public static function make(PaginatorContract $paginator, $onEachSide = 3, $onEachEdge = 2)
    {
        return (new static($paginator))->get($onEachSide, $onEachEdge);
    }

    /**
     * Get the window of URLs to be shown.
     *
     * @param  int  $onEachSide
     * @param  int  $onEachEdge
     * @return array
     */
    public function get($onEachSide = 3, $onEachEdge = 2)
    {
        if ($this->paginator->lastPage() < ($onEachSide * 2) + ($onEachEdge * 2) + 2) {
            return $this->getSmallSlider();
        }

        return $this->getUrlSlider($onEachSide, $onEachEdge);
    }

    /**
     * Get the slider of URLs there are not enough pages to slide.
     *
     * @return array
     */
    protected function getSmallSlider()
    {
        return [
            'first'  => $this->paginator->getUrlRange(1, $this->lastPage()),
            'slider' => null,
            'last'   => null,
        ];
    }

    /**
     * Create a URL slider links.
     *
     * @param  int  $onEachSide
     * @param  int  $onEachEdge
     * @return array
     */
    protected function getUrlSlider($onEachSide, $onEachEdge)
    {
        $window = $onEachSide * 2;

        if (! $this->hasPages()) {
            return [
                'first'  => null,
                'slider' => null,
                'last'   => null,
            ];
        }

        // If the current page is very close to the beginning of the page range, we will
        // just render the beginning of the page range, followed by the last links in
        // this list, since we will not have room to create a full slider.
        if ($this->currentPage() <= $onEachSide + $onEachEdge + 1) {
            return $this->getSliderTooCloseToBeginning($window, $onEachEdge);
        }

        // If the current page is close to the ending of the page range we will just get
        // this first couple pages, followed by a larger window of these ending pages
        // since we're too close to the end of the list to create a full on slider.
        elseif ($this->currentPage() >= ($this->lastPage() - $onEachSide - $onEachEdge)) {
            return $this->getSliderTooCloseToEnding($window, $onEachEdge);
        }

        // If we have enough room on both sides of the current page to build a slider we
        // will surround it with both the beginning and ending caps, with this window
        // of pages in the middle providing a Google style sliding paginator setup.
        return $this->getFullSlider($onEachSide, $onEachEdge);
    }

    /**
     * Get the slider of URLs when too close to beginning of window.
     *
     * @param  int  $window
     * @param  int  $onEachEdge
     * @return array
     */
    protected function getSliderTooCloseToBeginning($window, $onEachEdge)
    {
        return [
            'first'  => $this->paginator->getUrlRange(1, $window + $onEachEdge + 1),
            'slider' => null,
            'last'   => $this->getFinish($onEachEdge),
        ];
    }

    /**
     * Get the slider of URLs when too close to ending of window.
     *
     * @param  int  $window
     * @param  int  $onEachEdge
     * @return array
     */
    protected function getSliderTooCloseToEnding($window, $onEachEdge)
    {
        $last = $this->paginator->getUrlRange(
            $this->lastPage() - $window - $onEachEdge,
            $this->lastPage()
        );

        return [
            'first'  => $this->getStart($onEachEdge),
            'slider' => null,
            'last'   => $last,
        ];
    }

    /**
     * Get the slider of URLs when a full slider can be made.
     *
     * @param  int  $onEachSide
     * @param  int  $onEachEdge
     * @return array
     */
    protected function getFullSlider($onEachSide, $onEachEdge)
    {
        return [
            'first'  => $this->getStart($onEachEdge),
            'slider' => $this->getAdjacentUrlRange($onEachSide),
            'last'   => $this->getFinish($onEachEdge),
        ];
    }

    /**
     * Get the page range for the current page window.
     *
     * @param  int  $onEachSide
     * @return array
     */
    public function getAdjacentUrlRange($onEachSide)
    {
        return $this->paginator->getUrlRange(
            $this->currentPage() - $onEachSide,
            $this->currentPage() + $onEachSide
        );
    }

    /**
     * Get the starting URLs of a pagination slider.
     *
     * @param  int  $onEachEdge
     * @return array
     */
    public function getStart($onEachEdge)
    {
        return $this->paginator->getUrlRange(1, $onEachEdge);
    }

    /**
     * Get the ending URLs of a pagination slider.
     *
     * @param  int  $onEachEdge
     * @return array
     */
    public function getFinish($onEachEdge)
    {
        return $this->paginator->getUrlRange(
            $this->lastPage() + 1 - $onEachEdge,
            $this->lastPage()
        );
    }

    /**
     * Determine if the underlying paginator being presented has pages to show.
     *
     * @return bool
     */
    public function hasPages()
    {
        return $this->paginator->lastPage() > 1;
    }

    /**
     * Get the current page from the paginator.
     *
     * @return int
     */
    protected function currentPage()
    {
        return $this->paginator->currentPage();
    }

    /**
     * Get the last page from the paginator.
     *
     * @return int
     */
    protected function lastPage()
    {
        return $this->paginator->lastPage();
    }
}
