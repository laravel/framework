<?php

namespace Illuminate\Console\Concerns;

use Laravel\Prompts\ConfirmPrompt;
use Laravel\Prompts\MultiSelectPrompt;
use Laravel\Prompts\PasswordPrompt;
use Laravel\Prompts\Prompt;
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

        Prompt::fallbackWhen(! $input->isInteractive() || windows_os() || app()->runningUnitTests());

        TextPrompt::fallbackUsing(fn (TextPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->components->ask($prompt->label, $prompt->default ?: null) ?? '',
            $prompt->validate
        ));

        PasswordPrompt::fallbackUsing(fn (PasswordPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->components->secret($prompt->label),
            $prompt->validate
        ));

        ConfirmPrompt::fallbackUsing(fn (ConfirmPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->components->confirm($prompt->label, $prompt->default),
            $prompt->validate
        ));

        SelectPrompt::fallbackUsing(function (SelectPrompt $prompt) {
            if ($prompt->default === null) {
                $default = array_key_first($prompt->options);
            } else {
                $default = $prompt->default;
            }

            return $this->promptUntilValid(
                fn () => $this->components->choice($prompt->label, $prompt->options, $default),
                $prompt->validate
            );
        });

        MultiSelectPrompt::fallbackUsing(function (MultiSelectPrompt $prompt) {
            if ($prompt->default !== []) {
                return $this->promptUntilValid(
                    fn () => $this->components->choice($prompt->label, $prompt->options, implode(',', $prompt->default), multiple: true),
                    $prompt->validate
                );
            }

            return $this->promptUntilValid(
                fn () => collect($this->components->choice($prompt->label, ['' => 'None', ...$prompt->options], 'None', multiple: true))
                    ->reject('')
                    ->all(),
                $prompt->validate
            );
        });

        SuggestPrompt::fallbackUsing(fn (SuggestPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->components->askWithCompletion($prompt->label, $prompt->options, $prompt->default ?: null) ?? '',
            $prompt->validate
        ));
    }

    /**
     * Prompt the user until the given validation callback passes.
     *
     * @param  \Closure  $prompt
     * @param  \Closure|null  $validate
     * @return mixed
     */
    protected function promptUntilValid($prompt, $validate)
    {
        while (true) {
            $result = $prompt();

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
}
