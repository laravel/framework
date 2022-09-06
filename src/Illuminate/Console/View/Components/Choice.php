<?php

namespace Illuminate\Console\View\Components;

use Symfony\Component\Console\Question\ChoiceQuestion;

class Choice extends Component
{
    /**
     * Renders the component using the given arguments.
     *
     * @param  string  $question
     * @param  array<array-key, string>  $choices
     * @param  mixed  $default
     * @param bool  $multiple
     * @return mixed
     */
    public function render($question, $choices, $default = null, $multiple = false)
    {
        return $this->usingQuestionHelper(
            fn () => $this->output->askQuestion(
                (new ChoiceQuestion($question, $choices, $default))->setMultiselect($multiple)
            ),
        );
    }
}