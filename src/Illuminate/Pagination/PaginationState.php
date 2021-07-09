<?php

namespace Illuminate\Pagination;

class PaginationState
{
    /**
     * Bind the pagination state resolvers using the given application container as a base.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public static function resolveUsing($app)
    {
        Paginator::viewFactoryResolver(function () use ($app) {
            return $app['view'];
        });

        Paginator::currentPathResolver(function () use ($app) {
            return $app['request']->url();
        });

        Paginator::currentPageResolver(function ($pageName = 'page') use ($app) {
            $page = $app['request']->input($pageName);

            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int) $page >= 1) {
                return (int) $page;
            }

            return 1;
        });

        Paginator::queryStringResolver(function () use ($app) {
            return $app['request']->query();
        });

        CursorPaginator::currentCursorResolver(function ($cursorName = 'cursor') use ($app) {
            return Cursor::fromEncoded($app['request']->input($cursorName));
        });
    }
}
