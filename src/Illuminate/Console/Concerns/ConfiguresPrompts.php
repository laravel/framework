<?php

namespace Illuminate\Console\Concerns;

use Laravel\Prompts\ConfirmPrompt;
use Laravel\Prompts\MultiSelectPrompt;
use Laravel\Prompts\PasswordPrompt;
use Laravel\Prompts\Prompt;
use Laravel\Prompts\SearchPrompt;
use Laravel\Prompts\SelectPrompt;
use Laravel\Prompts\SuggestPrompt;
use Laravel\Prompts\TextPrompt;
use Symfony\Component\Console\Input\InputInterface;

trait ConfiguresPrompts
{
    /**
     * Configure the prompt fallbacks.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @return void
     */
    protected function configurePrompts(InputInterface $input)
    {
        Prompt::setOutput($this->output);

        Prompt::fallbackWhen(! $input->isInteractive() || windows_os() || $this->laravel->runningUnitTests());

        TextPrompt::fallbackUsing(fn (TextPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->components->ask($prompt->label, $prompt->default ?: null) ?? '',
            $prompt->required,
            $prompt->validate
        ));

        PasswordPrompt::fallbackUsing(fn (PasswordPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->components->secret($prompt->label) ?? '',
            $prompt->required,
            $prompt->validate
        ));

        ConfirmPrompt::fallbackUsing(fn (ConfirmPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->components->confirm($prompt->label, $prompt->default),
            $prompt->required,
            $prompt->validate
        ));

        SelectPrompt::fallbackUsing(fn (SelectPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->components->choice($prompt->label, $prompt->options, $prompt->default),
            false,
            $prompt->validate
        ));

        MultiSelectPrompt::fallbackUsing(function (MultiSelectPrompt $prompt) {
            if ($prompt->default !== []) {
                return $this->promptUntilValid(
                    fn () => $this->components->choice($prompt->label, $prompt->options, implode(',', $prompt->default), multiple: true),
                    $prompt->required,
                    $prompt->validate
                );
            }

            return $this->promptUntilValid(
                fn () => collect($this->components->choice($prompt->label, ['' => 'None', ...$prompt->options], 'None', multiple: true))
                    ->reject('')
                    ->all(),
                $prompt->required,
                $prompt->validate
            );
        });

        SuggestPrompt::fallbackUsing(fn (SuggestPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->components->askWithCompletion($prompt->label, $prompt->options, $prompt->default ?: null) ?? '',
            $prompt->required,
            $prompt->validate
        ));

        SearchPrompt::fallbackUsing(fn (SearchPrompt $prompt) => $this->promptUntilValid(
            function () use ($prompt) {
                $query = $this->components->ask($prompt->label);

                $options = ($prompt->options)($query);

                return $this->components->choice($prompt->label, $options);
            },
            false,
            $prompt->validate
        ));
    }

    /**
     * Prompt the user until the given validation callback passes.
     *
     * @param  \Closure  $prompt
     * @param  bool|string  $required
     * @param  \Closure|null  $validate
     * @return mixed
     */
    protected function promptUntilValid($prompt, $required, $validate)
    {
        while (true) {
            $result = $prompt();

            if ($required && ($result === '' || $result === [] || $result === false)) {
                $this->components->error(is_string($required) ? $required : 'Required.');

                continue;
            }

            if ($validate) {
                $error = $validate($result);

                if (is_string($error) && strlen($error) > 0) {
                    $this->components->error($error);

                    continue;
                }
            }

            return $result;
        }
    }

    /**
     * Restore the prompts output.
     *
     * @return void
     */
    protected function restorePrompts()
    {
        Prompt::setOutput($this->output);
    }
}
