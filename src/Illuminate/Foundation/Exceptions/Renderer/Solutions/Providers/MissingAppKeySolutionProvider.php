<?php

namespace Illuminate\Foundation\Exceptions\Renderer\Solutions\Providers;

use Illuminate\Encryption\MissingAppKeyException;
use Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\SolutionProvider;
use Illuminate\Foundation\Exceptions\Renderer\Solutions\Solution;
use Throwable;

class MissingAppKeySolutionProvider implements SolutionProvider
{
    public function canSolve(Throwable $throwable): bool
    {
        return $throwable instanceof MissingAppKeyException;
    }

    public function getSolutions(Throwable $throwable): array
    {
        return [
            new Solution(
                title: 'Generate an application key',
                description: 'No `APP_KEY` is set in your `.env` file. Run `php artisan key:generate` to generate one.',
            ),
        ];
    }
}
