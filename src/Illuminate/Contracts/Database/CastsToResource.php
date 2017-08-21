<?php

namespace Illuminate\Contracts\Database;

interface CastsToResource
{
    /**
     * Cast the given model into a resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $model
     * @return mixed
     */
    public static function castToResource($request, $model);

    /**
     * Cast the given paginator or collection into a resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $collection
     * @return mixed
     */
    public static function castCollectionToResource($request, $collection);
}
