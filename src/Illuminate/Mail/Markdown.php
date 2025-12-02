<?php

namespace Illuminate\Mail;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\EncodedHtmlString;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class Markdown
{
    /**
     * The view factory implementation.
     *
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $view;

    /**
     * The current theme being used when generating emails.
     *
     * @var string
     */
    protected $theme = 'default';

    /**
     * The registered component paths.
     *
     * @var array
     */
    protected $componentPaths = [];

    /**
     * Indicates if secure encoding should be enabled.
     *
     * @var bool
     */
    protected static $withSecuredEncoding = false;

    /**
     * The extracted head styles (media queries) from the theme.
     *
     * @var string
     */
    protected static $headStyles = '';

    /**
     * Create a new Markdown renderer instance.
     *
     * @param  \Illuminate\Contracts\View\Factory  $view
     * @param  array  $options
     */
    public function __construct(ViewFactory $view, array $options = [])
    {
        $this->view = $view;
        $this->theme = $options['theme'] ?? 'default';
        $this->loadComponentsFrom($options['paths'] ?? []);
    }

    /**
     * Render the Markdown template into HTML.
     *
     * @param  string  $view
     * @param  array  $data
     * @param  \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles|null  $inliner
     * @return \Illuminate\Support\HtmlString
     */
    public function render($view, array $data = [], $inliner = null)
    {
        $this->view->flushFinderCache();

        $this->view->replaceNamespace('mail', $this->htmlComponentPaths());

        if ($this->view->exists($customTheme = Str::start($this->theme, 'mail.'))) {
            $theme = $customTheme;
        } else {
            $theme = str_contains($this->theme, '::')
                ? $this->theme
                : 'mail::themes.'.$this->theme;
        }

        $themeCss = $this->view->make($theme, $data)->render();

        [$inlineCss, static::$headStyles] = $this->extractMediaQueries($themeCss);

        $bladeCompiler = $this->view
            ->getEngineResolver()
            ->resolve('blade')
            ->getCompiler();

        $contents = $bladeCompiler->usingEchoFormat(
            'new \Illuminate\Support\EncodedHtmlString(%s)',
            function () use ($view, $data) {
                if (static::$withSecuredEncoding === true) {
                    EncodedHtmlString::encodeUsing(function ($value) {
                        $replacements = [
                            '[' => '\[',
                            '<' => '&lt;',
                            '>' => '&gt;',
                        ];

                        return str_replace(array_keys($replacements), array_values($replacements), $value);
                    });
                }

                try {
                    $contents = $this->view->make($view, $data)->render();
                } finally {
                    EncodedHtmlString::flushState();
                }

                return $contents;
            }
        );

        return new HtmlString(($inliner ?: new CssToInlineStyles)->convert(
            str_replace('\[', '[', $contents), $inlineCss
        ));
    }

    /**
     * Render the Markdown template into text.
     *
     * @param  string  $view
     * @param  array  $data
     * @return \Illuminate\Support\HtmlString
     */
    public function renderText($view, array $data = [])
    {
        $this->view->flushFinderCache();

        $contents = $this->view->replaceNamespace(
            'mail', $this->textComponentPaths()
        )->make($view, $data)->render();

        return new HtmlString(
            html_entity_decode(preg_replace("/[\r\n]{2,}/", "\n\n", $contents), ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Parse the given Markdown text into HTML.
     *
     * @param  string  $text
     * @param  bool  $encoded
     * @return \Illuminate\Support\HtmlString
     */
    public static function parse($text, bool $encoded = false)
    {
        if ($encoded === false) {
            return new HtmlString(static::converter()->convert($text)->getContent());
        }

        if (static::$withSecuredEncoding === true || $encoded === true) {
            EncodedHtmlString::encodeUsing(function ($value) {
                $replacements = [
                    '[' => '\[',
                    '<' => '\<',
                ];

                $html = str_replace(array_keys($replacements), array_values($replacements), $value);

                return static::converter([
                    'html_input' => 'escape',
                ])->convert($html)->getContent();
            });
        }

        $html = '';

        try {
            $html = static::converter()->convert($text)->getContent();
        } finally {
            EncodedHtmlString::flushState();
        }

        return new HtmlString($html);
    }

    /**
     * Get a Markdown converter instance.
     *
     * @internal
     *
     * @param  array<string, mixed>  $config
     * @return \League\CommonMark\MarkdownConverter
     */
    public static function converter(array $config = [])
    {
        $environment = new Environment(array_merge([
            'allow_unsafe_links' => false,
        ], $config));

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);

        return new MarkdownConverter($environment);
    }

    /**
     * Get the HTML component paths.
     *
     * @return array
     */
    public function htmlComponentPaths()
    {
        return array_map(function ($path) {
            return $path.'/html';
        }, $this->componentPaths());
    }

    /**
     * Get the text component paths.
     *
     * @return array
     */
    public function textComponentPaths()
    {
        return array_map(function ($path) {
            return $path.'/text';
        }, $this->componentPaths());
    }

    /**
     * Get the component paths.
     *
     * @return array
     */
    protected function componentPaths()
    {
        return array_unique(array_merge($this->componentPaths, [
            __DIR__.'/resources/views',
        ]));
    }

    /**
     * Register new mail component paths.
     *
     * @param  array  $paths
     * @return void
     */
    public function loadComponentsFrom(array $paths = [])
    {
        $this->componentPaths = $paths;
    }

    /**
     * Set the default theme to be used.
     *
     * @param  string  $theme
     * @return $this
     */
    public function theme($theme)
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Get the theme currently being used by the renderer.
     *
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Enable secured encoding when parsing Markdown.
     *
     * @return void
     */
    public static function withSecuredEncoding()
    {
        static::$withSecuredEncoding = true;
    }

    /**
     * Disable secured encoding when parsing Markdown.
     *
     * @return void
     */
    public static function withoutSecuredEncoding()
    {
        static::$withSecuredEncoding = false;
    }

    /**
     * Flush the class's global state.
     *
     * @return void
     */
    public static function flushState()
    {
        static::$withSecuredEncoding = false;
        static::$headStyles = '';
    }

    /**
     * Extract media queries from CSS that cannot be inlined.
     *
     * @param  string  $css
     * @return array{0: string, 1: string}
     */
    protected function extractMediaQueries($css)
    {
        $mediaBlocks = '';
        $inlineCss = '';
        $offset = 0;
        $length = strlen($css);

        while (($pos = strpos($css, '@media', $offset)) !== false) {
            $inlineCss .= substr($css, $offset, $pos - $offset);

            $open = strpos($css, '{', $pos);

            if ($open === false) {
                break;
            }

            $braceCount = 1;
            $i = $open + 1;

            while ($i < $length && $braceCount > 0) {
                if ($css[$i] === '{') {
                    $braceCount++;
                } elseif ($css[$i] === '}') {
                    $braceCount--;
                }
                $i++;
            }

            $mediaBlocks .= substr($css, $pos, $i - $pos)."\n";
            $offset = $i;
        }

        $inlineCss .= substr($css, $offset);

        return [$inlineCss, $mediaBlocks];
    }

    /**
     * Get the extracted head styles (media queries) from the theme.
     *
     * @return string
     */
    public static function getHeadStyles()
    {
        return static::$headStyles;
    }
}
