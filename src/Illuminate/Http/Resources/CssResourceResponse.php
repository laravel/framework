<?php

namespace Illuminate\Http\Resources;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CssResourceResponse extends ResourceResponse
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function toResponse($request)
    {
        if (! method_exists($this->resource, 'toCss')) {
            throw NotFoundHttpException;
        }

        return $this->build($request, response(
            $this->resource->toCss($request),
            200, ['Content-Type' => 'text/css']
        ));
    }

    /**
     * Build the finished HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return \Illuminate\Http\Response
     */
    protected function build($request, $response)
    {
        return tap(parent::build($request, $response), function ($response) use ($request) {
            $this->resource->withCssResponse($request, $response);
        });
    }
}
