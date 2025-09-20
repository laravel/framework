<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Stringable;
use Illuminate\Support\Uri;

class To
{
    /**
     * Create a new cast definition for a Stringable instance.
     *
     * @return \Illuminate\Database\Eloquent\Casts\ToString<\Illuminate\Support\Stringable>
     */
    public static function stringable()
    {
        return new ToString(Stringable::class);
    }

    /**
     * Create a new cast definition for an HtmlString instance.
     *
     * @return \Illuminate\Database\Eloquent\Casts\ToString<\Illuminate\Support\HtmlString>
     */
    public static function htmlString()
    {
        return new ToString(HtmlString::class);
    }

    /**
     * Create a new cast definition for a Uri instance.
     *
     * @return \Illuminate\Database\Eloquent\Casts\ToString<\Illuminate\Support\Uri>
     */
    public static function uri()
    {
        return new ToString(Uri::class);
    }

    /**
     * Create a new cast definition for an ArrayObject.
     *
     * @return \Illuminate\Database\Eloquent\Casts\ToIterable<\Illuminate\Database\Eloquent\Casts\ArrayObject>
     */
    public static function arrayObject()
    {
        return new ToIterable(ArrayObject::class);
    }

    /**
     * Create a new cast definition for a Collection.
     *
     * @return \Illuminate\Database\Eloquent\Casts\ToIterable<\Illuminate\Support\Collection>
     */
    public static function collection()
    {
        return new ToIterable(Collection::class);
    }

    /**
     * Create a new cast definition for a Fluent instance.
     *
     * @return \Illuminate\Database\Eloquent\Casts\ToIterable<\Illuminate\Support\Fluent>
     */
    public static function fluent()
    {
        return new ToIterable(Fluent::class);
    }
}
