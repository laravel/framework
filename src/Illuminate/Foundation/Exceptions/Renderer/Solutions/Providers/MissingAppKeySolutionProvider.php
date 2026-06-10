<?php

namespace Illuminate\Foundation\Exceptions\Renderer\Solutions\Providers;

use Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\SolutionProvider;
use Illuminate\Foundation\Exceptions\Renderer\Solutions\RunnableSolution;
use RuntimeException;
use Throwable;

class MissingAppKeySolutionProvider implements SolutionProvider
{
    public function canSolve(Throwable $throwable): bool
    {
        if (! $throwable instanceof RuntimeException) {
            return false;
        }

        return str_contains($throwable->getMessage(), 'No application encryption key');
    }

    public function getSolutions(Throwable $throwable): array
    {
        return [
            RunnableSolution::artisan(
                title: 'Generate an application key',
                description: 'No APP_KEY is set in your .env file.',
                command: 'key:generate',
            ),
        ];
    }
}
