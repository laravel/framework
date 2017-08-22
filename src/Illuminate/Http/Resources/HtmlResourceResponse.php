<?php

namespace Illuminate\Http\Resources;

use Illuminate\Contracts\View\View;

class HtmlResourceResponse extends ResourceResponse
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function toResponse($request)
    {
        $view = $this->resource->toHtml($request);

        if ($view instanceof View && ! isset($view->resource)) {
            $view->with('resource', $this->resource);
        }

        return $this->build($request, response(
            $view, $this->calculateStatus(), $this->headers
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
            $this->resource->withHtmlResponse($request, $response);
        });
    }
}
