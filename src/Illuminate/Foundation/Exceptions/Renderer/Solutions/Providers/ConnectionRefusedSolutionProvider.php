<?php

namespace Illuminate\Foundation\Exceptions\Renderer\Solutions\Providers;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Renderer\Solutions\BaseSolution;
use Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\SolutionProvider;
use Throwable;

class ConnectionRefusedSolutionProvider implements SolutionProvider
{
    public function canSolve(Throwable $throwable): bool
    {
        if (! $throwable instanceof QueryException) {
            return false;
        }

        return str_contains($throwable->getMessage(), 'Connection refused')
            || str_contains($throwable->getMessage(), 'SQLSTATE[HY000] [2002]');
    }

    public function getSolutions(Throwable $throwable): array
    {
        return [
            new BaseSolution(
                title: 'Database connection refused',
                description: "The database server is not reachable. Check that:\n"
                    ."- The database server is running\n"
                    ."- The host and port in your .env are correct\n"
                    .'- No firewall is blocking the connection',
            ),
        ];
    }
}
