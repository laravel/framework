<?php

namespace Illuminate\Foundation\Exceptions\Renderer\Solutions\Providers;

use Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\SolutionProvider;
use Illuminate\Foundation\Exceptions\Renderer\Solutions\Solution;
use Illuminate\Foundation\ViteException;
use Throwable;

class ViteManifestNotFoundSolutionProvider implements SolutionProvider
{
    public function canSolve(Throwable $throwable): bool
    {
        return $throwable instanceof ViteException;
    }

    public function getSolutions(Throwable $throwable): array
    {
        return [
            new Solution(
                title: 'Run the Vite dev server or build assets',
                description: "The Vite manifest file was not found. Either:\n"
                    ."- Start the dev server: `npm run dev`\n"
                    .'- Or build for production: `npm run build`',
            ),
        ];
    }
}
