<?php

namespace Illuminate\Foundation\Exceptions\Renderer\Solutions\Providers;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\SolutionProvider;
use Illuminate\Foundation\Exceptions\Renderer\Solutions\RunnableSolution;
use Throwable;

class TableNotFoundSolutionProvider implements SolutionProvider
{
    public function canSolve(Throwable $throwable): bool
    {
        if (! $throwable instanceof QueryException) {
            return false;
        }

        return str_contains($throwable->getMessage(), 'Table')
            && str_contains($throwable->getMessage(), "doesn't exist");
    }

    public function getSolutions(Throwable $throwable): array
    {
        $table = $this->extractTableName($throwable->getMessage());

        $description = 'The table referenced in the query does not exist.';
        if ($table) {
            $description = "The table `{$table}` does not exist in the database.";
        }

        return [
            RunnableSolution::artisan(
                title: 'Run database migrations',
                description: $description,
                command: 'migrate',
            ),
        ];
    }

    private function extractTableName(string $message): ?string
    {
        if (preg_match("/Table '(?:[^.]+\.)?([^']+)' doesn't exist/", $message, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
