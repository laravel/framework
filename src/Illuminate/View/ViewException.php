<?php

namespace Illuminate\View;

use ErrorException;

class ViewException extends ErrorException
{
    /**
     * Report the exception.
     *
     * @return void
     */
    public function report()
    {
        $exception = $this->getPrevious();

        if ($exception && method_exists($exception, 'report')) {
            $exception->report();
        }
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        $exception = $this->getPrevious();

        if ($exception && method_exists($exception, 'render')) {
            return $exception->render($request);
        }
    }
}
