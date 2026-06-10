<?php

namespace Illuminate\Foundation\Exceptions\Renderer\Solutions\Providers;

use Illuminate\Foundation\Exceptions\Renderer\Solutions\BaseSolution;
use Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\SolutionProvider;
use InvalidArgumentException;
use Throwable;

class ViewNotFoundSolutionProvider implements SolutionProvider
{
    public function canSolve(Throwable $throwable): bool
    {
        if (! $throwable instanceof InvalidArgumentException) {
            return false;
        }

        return str_contains($throwable->getMessage(), 'View [')
            && str_contains($throwable->getMessage(), '] not found');
    }

    public function getSolutions(Throwable $throwable): array
    {
        $view = $this->extractViewName($throwable->getMessage());

        $description = 'The view file could not be found.';
        if ($view) {
            $description = "The view `{$view}` could not be found.\nCheck that the file exists and the name is spelled correctly.";
        }

        return [
            new BaseSolution(
                title: 'Check the view file exists',
                description: $description,
            ),
        ];
    }

    private function extractViewName(string $message): ?string
    {
        if (preg_match('/View \[([^\]]+)\] not found/', $message, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
