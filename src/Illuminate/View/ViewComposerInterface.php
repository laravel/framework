<?php namespace Illuminate\View;

interface ViewComposerInterface {

    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View $view
     * @return void
     */
    public function compose(View $view);
}
