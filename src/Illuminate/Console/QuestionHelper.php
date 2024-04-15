<?php

namespace Illuminate\Console;

use Illuminate\Console\View\Components\TwoColumnDetail;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class QuestionHelper extends SymfonyQuestionHelper
{
    /**
     * {@inheritdoc}
     *
     * @return void
     */
    #[\Override]
    protected function writePrompt(OutputInterface $output, Question $question): void
    {
        $text = OutputFormatter::escapeTrailingBackslash($question->getQuestion());

        $text = $this->ensureEndsWithPunctuation($text);

        $text = "  <fg=default;options=bold>$text</></>";

        $default = $question->getDefault();

        if ($question->isMultiline()) {
            $text .= sprintf(' (press %s to continue)', 'Windows' == PHP_OS_FAMILY
                ? '<comment>Ctrl+Z</comment> then <comment>Enter</comment>'
                : '<comment>Ctrl+D</comment>');
        }

        $text = match (true) {
            is_null($default) => sprintf('<info>%s</info>', $text),
            $question instanceof ConfirmationQuestion => $text = sprintf('<info>%s (yes/no)</info> [<comment>%s</comment>]', $text, $default ? 'yes' : 'no'),
            $question instanceof ChoiceQuestion => sprintf('<info>%s</info> [<comment>%s</comment>]', $text, OutputFormatter::escape($question->getChoices()[$default] ?? $default)),
            default => sprintf('<info>%s</info> [<comment>%s</comment>]', $text, OutputFormatter::escape($default)),
        };

        $output->writeln($text);

        if ($question instanceof ChoiceQuestion) {
            foreach ($question->getChoices() as $key => $value) {
                with(new TwoColumnDetail($output))->render($value, $key);
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
