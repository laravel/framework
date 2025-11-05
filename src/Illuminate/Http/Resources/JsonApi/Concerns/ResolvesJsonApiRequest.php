<?php

namespace Illuminate\Http\Resources\JsonApi\Concerns;

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiRequest;

trait ResolvesJsonApiRequest
{
    /**
     * Resolve the Request instance from Container.
     *
     * @return \Illuminate\Http\Resources\JsonApi\JsonApiRequest
     */
    protected function resolveJsonApiRequestFrom(Request $request)
    {
        if ($request instanceof JsonApiRequest) {
            return $request;
        }

        $app = Container::getInstance();

        return JsonApiRequest::createFrom($request);
    }
}
