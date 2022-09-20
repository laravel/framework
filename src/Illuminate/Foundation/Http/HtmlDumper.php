<?php

namespace Illuminate\Foundation\Http;

use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper as BaseHtmlDumper;
use Symfony\Component\VarDumper\VarDumper;
use Throwable;

class HtmlDumper extends BaseHtmlDumper
{
    /**
     * Where the source should be placed on "expanded" kind of dumps.
     */
    const EXPANDED_SEPARATOR = 'data-depth=1 class=sf-dump-expanded>';

    /**
     * Where the source should be placed on "non expanded" kind of dumps.
     */
    const NON_EXPANDED_SEPARATOR = "\n</pre><script>";

    /**
     * The base path of the application.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Creates a new Html Dumper instance.
     *
     * @param  string  $basePath
     * @return void
     */
    public function __construct($basePath)
    {
        parent::__construct();

        $this->basePath = $basePath;
    }

    /**
     * Creates a new Html Dumper instance, and registers it as the default dumper.
     *
     * @param  string  $basePath
     * @return void
     */
    public static function register($basePath)
    {
        $cloner = tap(new VarCloner())->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);

        $dumper = new static($basePath);

        VarDumper::setHandler(fn ($value) => $dumper->dumpWithSource($cloner->cloneVar($value)));
    }

    /**
     * Dumps a variable with its source file/line.
     *
     * @param  \Symfony\Component\VarDumper\Cloner\Data  $data
     * @return void
     */
    public function dumpWithSource(Data $data)
    {
        $output = $this->dump($data, true);

        $output = match (true) {
            str_contains($output, static::EXPANDED_SEPARATOR) => str_replace(
                static::EXPANDED_SEPARATOR,
                static::EXPANDED_SEPARATOR.$this->displayableDumpSource(),
                $output,
            ),
            str_contains($output, static::NON_EXPANDED_SEPARATOR) => str_replace(
                static::NON_EXPANDED_SEPARATOR,
                $this->displayableDumpSource().static::NON_EXPANDED_SEPARATOR,
                $output,
            ),
            default => $output,
        };
        
        fwrite($this->outputStream, $output);
    }

    /**
     * Gets a console "displayble" source for the dump.
     *
     * @return string
     */
    protected function displayableDumpSource()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 20);

        $file = $trace[6]['file'] ?? null;
        $line = $trace[6]['line'] ?? null;

        if (is_null($file) || is_null($line)) {
            return '';
        }

        $relativeFile = $file;

        if (str_starts_with($file, $this->basePath)) {
            $relativeFile = substr($file, strlen($this->basePath) + 1);
        }

        $source = sprintf('%s:%s', $relativeFile, $line);

        if ($editor = $this->editor()) {
            $source = sprintf(
                '<a href="%s://open?file=%s&line=%s">%s</a>',
                $editor,
                $file,
                $line,
                $source,
            );
        }

        return sprintf('<span style="color: #A0A0A0; font-family: Menlo"> // %s</span>', $source);
    }

    /**
     * Gets the application editor, if any.
     *
     * @return string|null
     */
    protected function editor()
    {
        try {
            return config('app.editor');
        } catch (Throwable $e) {
            // ...
        }
    }
}
