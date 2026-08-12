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
        Paginator::viewFactoryResolver(static fn () => $app['view']);

        Paginator::currentPathResolver(static fn () => $app['request']->url());

        Paginator::currentPageResolver(static function ($pageName = 'page') use ($app) {
            $page = $app['request']->input($pageName);

            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int) $page >= 1) {
                return (int) $page;
            }

            return 1;
        });

        Paginator::queryStringResolver(static fn () => $app['request']->query());

        CursorPaginator::currentCursorResolver(static function ($cursorName = 'cursor') use ($app) {
            return Cursor::fromEncoded($app['request']->input($cursorName));
        });
    }
}
