<?php

namespace Illuminate\Http\Resources;

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
        return $this->build($request, response(
            $this->instance()->toHtml($request),
            $this->calculateStatus(), $this->headers
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
            $this->instance()->withHtmlResponse($request, $response);
        });
    }
}
