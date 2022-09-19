<?php

namespace Illuminate\Foundation\Console;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper as BaseCliDumper;
use Symfony\Component\VarDumper\VarDumper;

class CliDumper extends BaseCliDumper
{
    /**
     * The base path of the application.
     *
     * @var string
     */
    protected $basePath;

    /**
     * The output instance.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * Creates a new Cli Dumper instance.
     *
     * @param  string  $basePath
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    public function __construct($basePath, $output)
    {
        parent::__construct();

        $this->basePath = $basePath;
        $this->output = $output;
    }

    /**
     * Creates a new Cli Dumper instance, and registers it as the default dumper.
     *
     * @param  string  $basePath
     * @return void
     */
    public static function register($basePath)
    {
        $cloner = tap(new VarCloner())->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);
        $output = new ConsoleOutput(OutputInterface::VERBOSITY_NORMAL);

        $dumper = new static($basePath, $output);

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
        $output = (string) $this->dump($data, true);

        $lines = explode("\n", $output);

        $lines[0] .= $this->displayableDumpSource();

        $this->output->write(implode("\n", $lines));
    }

    /**
     * {@inheritDoc}
     */
    protected function supportsColors(): bool
    {
        return $this->output->isDecorated();
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

        return sprintf(' <fg=gray>// <fg=gray;href=file://%s#L%s>%s:%s</></>', $file, $line, $relativeFile, $line);
    }
}
