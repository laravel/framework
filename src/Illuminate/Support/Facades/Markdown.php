<?php

namespace Illuminate\Support\Facades;

use Illuminate\Contracts\Markdown\Markdown as MarkdownContract;

/**
 * @method static \Illuminate\Contracts\Support\Htmlable render(string $markdown)
 *
 * @see \Illuminate\Contracts\Markdown\Markdown
 */
class Markdown extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return MarkdownContract::class;
    }
}
