<?php

namespace Illuminate\Testing\Console;

use Illuminate\Console\OutputStyle;
use PHPUnit\Framework\Assert as PHPUnit;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * @internal
 */
class FakeOutputStyle extends OutputStyle
{
    /**
     * The expected questions that have not been asked yet, as [question, answer] pairs.
     *
     * @var array<int, array{0: string, 1: mixed}>
     */
    protected $questions;

    /**
     * The questions that were asked, keyed by their expected question text.
     *
     * @var array<string, \Symfony\Component\Console\Question\Question>
     */
    protected $asked = [];

    /**
     * The failure from a question that should not have been asked.
     *
     * @var string|null
     */
    protected $failure;

    /**
     * Create a new fake output style instance.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  array<int, array{0: string, 1: mixed}>  $questions
     * @param  array<string, array{expected: array, strict: bool}>  $choices
     */
    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        array $questions = [],
        protected array $choices = [],
    ) {
        parent::__construct($input, $output);

        $this->questions = array_values($questions);
    }

    /**
     * Answer the next expected question.
     *
     * @param  \Symfony\Component\Console\Question\Question  $question
     * @return mixed
     */
    #[\Override]
    public function askQuestion(Question $question): mixed
    {
        $text = $question->getQuestion();

        foreach ($this->questions as $i => [$expected, $answer]) {
            if ($text != $expected) {
                continue;
            }

            if ($i !== array_key_first($this->questions)) {
                PHPUnit::fail($this->failure ??= 'Question "'.$text.'" was asked before "'.reset($this->questions)[0].'".');
            }

            unset($this->questions[$i]);

            $this->asked[$expected] = $question;

            return $answer;
        }

        PHPUnit::fail($this->failure ??= 'Unexpected question "'.$text.'" was asked.');
    }

    /**
     * Get the message for the first unmet question expectation.
     *
     * @return string|null
     */
    public function failure()
    {
        if ($this->failure) {
            return $this->failure;
        }

        if (count($this->questions)) {
            return 'Question "'.reset($this->questions)[0].'" was not asked.';
        }

        return null;
    }

    /**
     * Get the expected choices with the choices that were actually offered.
     *
     * @return array<string, array{expected: array, strict: bool, actual: array|null}>
     */
    public function choices()
    {
        foreach ($this->choices as $text => $choice) {
            $question = $this->asked[$text] ?? null;

            $this->choices[$text]['actual'] = $question instanceof ChoiceQuestion && ! array_is_list($choice['expected'])
                ? $question->getChoices()
                : $question?->getAutocompleterValues();
        }

        return $this->choices;
    }
}
