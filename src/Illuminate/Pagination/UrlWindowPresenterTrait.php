<?php

namespace Illuminate\Pagination;

trait UrlWindowPresenterTrait
{
    /**
     * Render the actual link slider.
     *
     * @return string
     */
    protected function getLinks()
    {
        $html = '';

        if (is_array($this->window['first'])) {
            $html .= $this->getUrlLinks($this->window['first']);
        }

        if (is_array($this->window['slider'])) {
            $html .= $this->getDots();
            $html .= $this->getUrlLinks($this->window['slider']);
        }

        if (is_array($this->window['last'])) {
            $html .= $this->getDots();
            $html .= $this->getUrlLinks($this->window['last']);
        }

        return $html;
    }

    /**
     * Get the links for the URLs in the given array.
     *
     * @param  array  $urls
     * @return string
     */
    protected function getUrlLinks(array $urls)
    {
        $html = '';

        foreach ($urls as $page => $url) {
            $html .= $this->getPageLinkWrapper($url, $page);
        }

        return $html;
    }

    /**
     * Get HTML wrapper for a page link.
     *
     * @param  string  $url
     * @param  int  $page
     * @param  string|null  $rel
     * @return string
     */
    protected function getPageLinkWrapper($url, $page, $rel = null)
    {
        if ($page == $this->paginator->currentPage()) {
            return $this->getActivePageWrapper($page);
        }

        return $this->getAvailablePageWrapper($url, $page, $rel);
    }
}
