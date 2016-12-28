<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Contracts\Mail\Mailable;

trait InteractsWithMailable
{
    /**
     * Render and return the mailable HTML view.
     *
     * @param  \Illuminate\Mail\Mailable $mailable
     * @return string
     */
    public function renderView(Mailable $mailable)
    {
        return $this->render($mailable, 'view');
    }

    /**
     * Render and return the mailable HTML view.
     *
     * @param  \Illuminate\Mail\Mailable $mailable
     * @return string
     */
    public function renderTextView(Mailable $mailable)
    {
        return $this->render($mailable, 'textView');
    }

    /**
     * Render and return the mailable view or text view.
     *
     * @param  \Illuminate\Mail\Mailable $mailable
     * @return string
     */
    private function render(Mailable $mailable, $view)
    {
        $this->app->call([$mailable, 'build']);

        return $this->app->make('view')
            ->make($mailable->$view, $mailable->buildViewData())
            ->render();
    }
}
