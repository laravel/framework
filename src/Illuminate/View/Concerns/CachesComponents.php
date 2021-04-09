<?php

namespace Illuminate\View\Concerns;

use Illuminate\Container\Container;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\CanBeCached;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

trait CachesComponents
{
    /**
     * The attributes that can be passed to the component to control the
     * caching behavior.
     * @return array
     */
    protected function cacheAttributes()
    {
        return [
            'x-cache',
            'x-cache-key',
            'x-cache-ttl',
        ];
    }

    /**
     * Determines if the component can be cached.
     *
     * @return bool
     */
    protected function canBeCached()
    {
        return $this instanceof CanBeCached;
    }

    /**
     * Determines if the component should be cached.
     *
     * @return bool
     */
    protected function shouldBeCached()
    {
        return $this->attributes->get('x-cache', false) && $this->canBeCached();
    }

    /**
     * The key used to cache the component.
     *
     * @return bool
     */
    protected function cacheKey()
    {
        return $this->attributes->get('x-cache-key', sha1(static::class . $this->attributes->except($this->cacheAttributes())->toHtml()));
    }

    /**
     * The cache TTL to use for the component.
     *
     * @return mixed
     */
    protected function cacheTtl()
    {
        return $this->attributes->get('x-cache-ttl', now()->addHour());
    }

    /**
     * Render the resolved component view so that it can be cached.
     *
     * @param \Illuminate\Contracts\View\View|\Illuminate\Contracts\Support\Htmlable|string $view
     * @param array $data
     * @return string
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function renderResolvedView($view, $data)
    {
        $data['attributes'] = $data['attributes']->filter(function ($value, $attribute) {
            return ! in_array($attribute, $this->cacheAttributes());
        });

        if ($view instanceof View) {
            return $view->with($data)->render();
        }

        if ($view instanceof Htmlable) {
            return $view->toHtml();
        }

        return Container::getInstance()->make('view')->make($view, $data)->render();
    }

    /**
     * Cache the resolved component view and return a Htmlable class that will
     * return the cached result.
     *
     * @param \Illuminate\Contracts\View\View|\Illuminate\Contracts\Support\Htmlable|string $view
     * @param array $data
     * @return Htmlable
     */
    protected function cacheResolvedView($view, $data)
    {
        if (! $this->shouldBeCached()) {
            return $view;
        }

        $html = Cache::remember($this->cacheKey(), $this->cacheTtl(), function() use ($view, $data) {
            return $this->renderResolvedView($view, $data);
        });

        return new class($html) implements Htmlable {
            protected $html;

            public function __construct($html)
            {
                $this->html = $html;
            }

            public function toHtml()
            {
                return $this->html;
            }
        };
    }
}
