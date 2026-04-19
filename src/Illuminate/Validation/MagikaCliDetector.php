<?php

namespace Illuminate\Validation;

use Illuminate\Contracts\Validation\MagikaDetector;
use RuntimeException;
use Symfony\Component\Process\Process;

class MagikaCliDetector implements MagikaDetector
{
    /**
     * Create a new Magika CLI detector instance.
     *
     * @param  string  $binary
     */
    public function __construct(protected string $binary = 'magika')
    {
    }

    /**
     * Detect the content type of a file and return its canonical extension.
     *
     * @param  string  $path
     * @return string|null
     *
     * @throws \RuntimeException
     */
    public function detect(string $path): ?string
    {
        $process = new Process([$this->binary, '--json', $path]);

        $process->run();

        if (! $process->isSuccessful()) {
            if (! $this->binaryExists()) {
                throw new RuntimeException(
                    "The Magika binary [{$this->binary}] was not found. Install it via `pip install magika` and ensure it is available in your PATH."
                );
            }

            return null;
        }

        $output = json_decode($process->getOutput(), true);

        return $output[0]['result']['value']['dl']['label'] ?? null;
    }

    /**
     * Determine if the Magika binary exists on the system.
     *
     * @return bool
     */
    protected function binaryExists(): bool
    {
        $check = new Process(['which', $this->binary]);
        $check->run();

        return $check->isSuccessful();
    }
}
