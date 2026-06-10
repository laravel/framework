<?php

namespace Illuminate\Foundation\Exceptions\Renderer;

use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Exceptions\Renderer\Mappers\BladeMapper;
use Illuminate\Foundation\Exceptions\Renderer\Solutions\SolutionProviderRepository;
use Illuminate\Http\Request;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Throwable;

class SolutionRenderer extends Renderer
{
    public function __construct(
        Factory $viewFactory,
        Listener $listener,
        HtmlErrorRenderer $htmlErrorRenderer,
        BladeMapper $bladeMapper,
        string $basePath,
        private readonly SolutionProviderRepository $solutionRepository,
    ) {
        parent::__construct($viewFactory, $listener, $htmlErrorRenderer, $bladeMapper, $basePath);
    }

    public function render(Request $request, Throwable $throwable)
    {
        $html = parent::render($request, $throwable);

        $solutions = $this->solutionRepository->getSolutions($throwable);

        if (empty($solutions)) {
            return $html;
        }

        $solutionsHtml = $this->viewFactory->make('laravel-exceptions-renderer::solutions', [
            'solutions' => $solutions,
        ])->render();

        // Inject before the separator that precedes the trace section.
        $marker = 'class="h-0 w-full relative -mt-5 -z-10"';

        $position = strpos($html, $marker);

        if ($position !== false) {
            $divStart = strrpos(substr($html, 0, $position), '<div');
            if ($divStart !== false) {
                return substr_replace($html, $solutionsHtml, $divStart, 0);
            }
        }

        // Fallback: inject before </body>
        return str_replace('</body>', $solutionsHtml.'</body>', $html);
    }
}
