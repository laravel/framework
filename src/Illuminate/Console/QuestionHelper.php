<?php

namespace Illuminate\Console;

use Illuminate\Console\View\Components\Detail;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use function Termwind\renderUsing;
use function Termwind\render;

class QuestionHelper extends SymfonyQuestionHelper
{
    /**
     * {@inheritdoc}
     */
    protected function writePrompt(OutputInterface $output, Question $question)
    {
        $text = OutputFormatter::escapeTrailingBackslash($question->getQuestion());

        $text = $this->ensureEndsWithPunctuation($text);

        $text = "  <fg=default;options=bold>$text</></>";

        $default = $question->getDefault();

        if ($question->isMultiline()) {
            $text .= sprintf(' (press %s to continue)', $this->getEofShortcut());
        }

        switch (true) {
            case null === $default:
                $text = sprintf('<info>%s</info>', $text);

                break;

            case $question instanceof ConfirmationQuestion:
                $text = sprintf('<info>%s (yes/no)</info> [<comment>%s</comment>]', $text, $default ? 'yes' : 'no');

                break;

            case $question instanceof ChoiceQuestion && $question->isMultiselect():
                $choices = $question->getChoices();
                $default = explode(',', $default);

                foreach ($default as $key => $value) {
                    $default[$key] = $choices[trim($value)];
                }

                $text = sprintf('<info>%s</info> [<comment>%s</comment>]', $text, OutputFormatter::escape(implode(', ', $default)));

                break;

            case $question instanceof ChoiceQuestion:
                $choices = $question->getChoices();
                $text = sprintf('<info>%s</info> [<comment>%s</comment>]', $text, OutputFormatter::escape($choices[$default] ?? $default));

                break;

            default:
                $text = sprintf('<info>%s</info> [<comment>%s</comment>]', $text, OutputFormatter::escape($default));
        }

        $output->writeln($text);

        if ($question instanceof ChoiceQuestion) {
            foreach ($question->getChoices() as $key => $value) {
                Detail::renderUsing($output, $key, $value);
            }
        }

        $output->write('<options=bold>❯ </>');
    }

    /**
     * Ensures the given string ends with punctuation.
     *
     * @param  string  $string
     * @return string
     */
    protected function ensureEndsWithPunctuation($string)
    {
        if (! str($string)->endsWith(['?', ':', '!', '.'])) {
            return "$string:";
        }

        return $string;
    }
}
