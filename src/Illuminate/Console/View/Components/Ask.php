<?php

namespace Illuminate\Console\View\Components;

class Ask extends Component
{
    /**
     * Renders the component using the given arguments.
     *
     * @param $question
     * @return void
     */
    public function render($question)
    {
        return $this->askView('ask', [
            'content' => $question,
        ]);
    }
}
