<?php

namespace Illuminate\Console\Concerns;

use Illuminate\Support\Collection;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

trait HandleUrl
{
    /**
     * The custom URL opener.
     *
     * @var callable|null
     */
    protected $urlOpener;

    /**
     * The operating system family.
     *
     * @var string
     */
    protected $systemOsFamily = PHP_OS_FAMILY;

    /**
     * Open the URL in the user's browser.
     *
     * @param  string  $url
     * @return void
     */
    protected function open($url)
    {
        ($this->urlOpener ?? function ($url) {
            if (in_array($this->systemOsFamily, ['Darwin', 'Windows', 'Linux'])) {
                $this->openViaBuiltInStrategy($url);
            } else {
                $this->components->warn('Unable to open the URL on your system. You will need to open it yourself or create a custom opener for your system.');
            }
        })($url);
    }

    /**
     * Open the URL via the built in strategy.
     *
     * @param  string  $url
     * @return void
     */
    protected function openViaBuiltInStrategy($url)
    {
        if ($this->systemOsFamily === 'Windows') {
            $process = tap(Process::fromShellCommandline(escapeshellcmd("start {$url}")))->run();

            if (! $process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return;
        }

        $binary = Collection::make(match ($this->systemOsFamily) {
            'Darwin' => ['open'],
            'Linux' => ['xdg-open', 'wslview'],
        })->first(fn ($binary) => (new ExecutableFinder)->find($binary) !== null);

        if ($binary === null) {
            $this->components->warn('Unable to open the URL on your system. You will need to open it yourself or create a custom opener for your system.');

            return;
        }

        $process = tap(Process::fromShellCommandline(escapeshellcmd("{$binary} {$url}")))->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
