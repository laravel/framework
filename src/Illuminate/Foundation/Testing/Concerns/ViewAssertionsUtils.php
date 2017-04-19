<?php

namespace Illuminate\Foundation\Testing\Concerns;

use PHPUnit_Framework_Assert as PHPUnit;

trait ViewAssertionsUtils
{
    /**
     * Tests to see whether the view template provided is the one
     * used in the rendered view.
     *
     * @param  string  $view_name
     * @return $this
     */
    protected function assertViewIs($view_name)
    {
        $this->assertEquals($view_name, $this->response->original->getName());

        return $this;
    }

    /**
     * Tests to see whether the view template provided is the one
     * used in the rendered view.
     *
     * @param  string  $view_name
     * @return $this
     */
    protected function assertTemplateUsedIs($view_name)
    {
        return $this->assertViewIs($view_name);
    }

    /**
     * Tests to see whether the view template provided contains
     * a link with an href matching the given one.
     *
     * @param  string  $link
     * @return $this
     */
    protected function assertLink($link)
    {
        $this->assertGreaterThan(0, $this->crawler()->filter('a[href="'. $link .'"]')->count());

        return $this;
    }

    /**
     * Tests to see whether the view template provided do not contains
     * a link with an href matching the given one.
     *
     * @param  string  $link
     * @return $this
     */
    protected function assertNotLink($link)
    {
        $this->assertEquals(0, $this->crawler()->filter('a[href="'. $link .'"]')->count());

        return $this;
    }

    /**
     * Tests to see whether the view template provided contains
     * {$count} times a link with an href matching the given one.
     *
     * @param  string  $link
     * @param  int  $count
     * @return $this
     */
    protected function assertLinkCount($link, $count)
    {
        $this->assertEquals($count, $this->crawler()->filter('a[href="'. $link .'"]')->count());

        return $this;
    }

    /**
     * Tests to see whether the view template provided do not contains
     * {$count} times a link with an href matching the given one.
     *
     * @param  string  $link
     * @param  int  $count
     * @return $this
     */
    protected function assertNotLinkCount($link, $count)
    {
        $this->assertNotEquals($count, $this->crawler()->filter('a[href="'. $link .'"]')->count());

        return $this;
    }

    /**
     * Tests to see whether the view template provided contains
     * more than {$count} times a link with an href matching the given one.
     *
     * @param  string  $link
     * @param  int  $count
     * @return $this
     */
    protected function assertLinkCountGreaterThan($link, $count)
    {
        $this->assertGreaterThan($count, $this->crawler()->filter('a[href="'. $link .'"]')->count());

        return $this;
    }

    /**
     * Tests to see whether the view template provided contains
     * at least {$count} times a link with an href matching the given one.
     *
     * @param  string  $link
     * @param  int  $count
     * @return $this
     */
    protected function assertLinkCountGreaterThanOrEqual($link, $count)
    {
        $this->assertGreaterThanOrEqual($count, $this->crawler()->filter('a[href="'. $link .'"]')->count());

        return $this;
    }

    /**
     * Tests to see whether the view template provided contains
     * less than {$count} times a link with an href matching the given one.
     *
     * @param  string  $link
     * @param  int  $count
     * @return $this
     */
    protected function assertLinkCountLessThan($link, $count)
    {
        $this->assertLessThan($count, $this->crawler()->filter('a[href="'. $link .'"]')->count());

        return $this;
    }

    /**
     * Tests to see whether the view template provided contains
     * at most {$count} times a link with an href matching the given one.
     *
     * @param  string  $link
     * @param  int  $count
     * @return $this
     */
    protected function assertLinkCountLessThanOrEqual($link, $count)
    {
        $this->assertLessThanOrEqual($count, $this->crawler()->filter('a[href="'. $link .'"]')->count());

        return $this;
    }

    /**
     * Tests to see whether the view template provided contains
     * a link generated with the provided route name.
     *
     * @param  string  $route_name
     * @return $this
     */
    protected function assertLinkRoute($route_name)
    {
        return $this->assertLink(route($route_name));
    }


    /**
     * Tests to see whether the view template provided do not contains
     * a link generated with the provided route name.
     *
     * @param  string  $route_name
     * @return $this
     */
    protected function assertNotLinkRoute($route_name)
    {
        return $this->assertNotLink(route($route_name));
    }

    /**
     * Tests to see whether the view template provided contains
     * {$count} times a link generated with the provided route name.
     *
     * @param  string  $route_name
     * @param  int  $count
     * @return $this
     */
    protected function assertLinkRouteCount($route_name, $count)
    {
        return $this->assertLinkCount(route($route_name), $count);
    }

    /**
     * Tests to see whether the view template provided do not contains
     * {$count} times a link generated with the provided route name.
     *
     * @param  string  $route_name
     * @param  int  $count
     * @return $this
     */
    protected function assertNotLinkRouteCount($route_name, $count)
    {
        return $this->assertNotLinkCount(route($route_name), $count);
    }

    /**
     * Tests to see whether the view template provided contains
     * more than {$count} times a link generated with the provided route name.
     *
     * @param  string  $route_name
     * @param  int  $count
     * @return $this
     */
    protected function assertLinkRouteCountGreaterThan($route_name, $count)
    {
        return $this->assertLinkCountGreaterThan(route($route_name), $count);
    }

    /**
     * Tests to see whether the view template provided contains
     * at least {$count} times a link generated with the provided route name.
     *
     * @param  string  $route_name
     * @param  int  $count
     * @return $this
     */
    protected function assertLinkRouteCountGreaterThanOrEqual($route_name, $count)
    {
        return $this->assertLinkCountGreaterThanOrEqual(route($route_name), $count);
    }

    /**
     * Tests to see whether the view template provided contains
     * less than {$count} times a link generated with the provided route name.
     *
     * @param  string  $route_name
     * @param  int  $count
     * @return $this
     */
    protected function assertLinkRouteCountLessThan($route_name, $count)
    {
        return $this->assertLinkCountLessThan(route($route_name), $count);
    }

    /**
     * Tests to see whether the view template provided contains
     * at most {$count} times a link generated with the provided route name.
     *
     * @param  string  $route_name
     * @param  int  $count
     * @return $this
     */
    protected function assertLinkRouteCountLessThanOrEqual($route_name, $count)
    {
        return $this->assertLinkCountGreaterThanOrEqual(route($route_name), $count);
    }
}
