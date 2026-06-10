<?php

namespace Illuminate\Foundation\Exceptions\Renderer\Solutions\Providers;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\SolutionProvider;
use Illuminate\Foundation\Exceptions\Renderer\Solutions\RunnableSolution;
use Throwable;

class MissingColumnSolutionProvider implements SolutionProvider
{
    public function canSolve(Throwable $throwable): bool
    {
        if (! $throwable instanceof QueryException) {
            return false;
        }

        return str_contains($throwable->getMessage(), 'Column not found')
            || str_contains($throwable->getMessage(), 'Unknown column');
    }

    public function getSolutions(Throwable $throwable): array
    {
        $column = $this->extractColumnName($throwable->getMessage());

        $description = 'A column referenced in the query does not exist in the database.';
        if ($column) {
            $description = "The column `{$column}` does not exist in the database.";
        }

        return [
            RunnableSolution::artisan(
                title: 'Run database migrations',
                description: $description,
                command: 'migrate',
            ),
        ];
    }

    private function extractColumnName(string $message): ?string
    {
        if (preg_match("/Unknown column '([^']+)'/", $message, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
