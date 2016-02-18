<?php

namespace Illuminate\Routing;

class RouteGroup
{
    /**
     * The parent of the group.
     *
     * @var /Illuminate/Routing/RouteGroup|null
     */
    protected $parent;

    /**
     * The registered route value binders.
     *
     * @var array
     */
    protected $binders = [];

    /**
     * Create a new RouteGroup instance.
     *
     * @param /Illuminate/Routing/RouteGroup  $parent
     * @return void
     */
    public function __construct(RouteGroup $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * Add a new route parameter binder for this group.
     *
     * @param  string  $key
     * @param  callable  $binder
     * @return void
     */
    public function bind($key, $binder)
    {
        $this->binders[str_replace('-', '_', $key)] = $binder;
    }

    /**
     * Get the route group binders.
     *
     * @return array
     */
    public function getBinders()
    {
        $parentBinders = $this->parent ? $this->parent->getBinders() : [];

        return array_merge($parentBinders, $this->binders);
    }
}
