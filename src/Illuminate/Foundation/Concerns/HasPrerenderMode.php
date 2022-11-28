<?php

namespace Illuminate\Foundation\Concerns;

trait HasPrerenderMode
{
    /**
     * The flag to set if the maintenance needs to pre-render a view.
     *
     * @var bool
     */
    protected $prerender = false;

    /**
     * Marks the application in "pre-maintenance" mode so views can be pre-render checking for maintenance mode.
     *
     * @param  bool  $prerender
     * @return void
     */
    public function prerenderMaintenance(bool $prerender): void
    {
        $this->prerender = $prerender;
    }

    /**
     * Determine if the application needs to pre-render views for maintenance mode.
     *
     * @return bool
     */
    public function needsPrerender(): bool
    {
        return $this->prerender;
    }
}
