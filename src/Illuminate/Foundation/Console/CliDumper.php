<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Foundation\Concerns\ResolvesDumpSource;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper as BaseCliDumper;
use Symfony\Component\VarDumper\VarDumper;

class CliDumper extends BaseCliDumper
{
    use ResolvesDumpSource;

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
     * If the dumper is currently dumping.
     *
     * @var bool
     */
    protected $dumping = false;

    /**
     * Create a new CLI dumper instance.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  string  $basePath
     * @return void
     */
    public function __construct($output, $basePath)
    {
        parent::__construct();

        $this->basePath = $basePath;
        $this->output = $output;
    }

    /**
     * Create a new CLI dumper instance and register it as the default dumper.
     *
     * @param  string  $basePath
     * @return void
     */
    public static function register($basePath)
    {
        $cloner = tap(new VarCloner())->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);

        $dumper = new static(new ConsoleOutput(), $basePath);

        VarDumper::setHandler(fn ($value) => $dumper->dumpWithSource($cloner->cloneVar($value)));
    }

    /**
     * Dump a variable with its source file / line.
     *
     * @param  \Symfony\Component\VarDumper\Cloner\Data  $data
     * @return void
     */
    public function dumpWithSource(Data $data)
    {
        if ($this->dumping) {
            $this->dump($data);

            return;
        }

        $this->dumping = true;

        $output = (string) $this->dump($data, true);
        $lines = explode("\n", $output);

        $lines[0] .= $this->getDumpSourceContent();

        $this->output->write(implode("\n", $lines));

        $this->dumping = false;
    }

    /**
     * Get the dump's source console content.
     *
     * @return string
     */
    protected function getDumpSourceContent()
    {
        if (is_null($dumpSource = $this->resolveDumpSource())) {
            return '';
        }

        [$file, $relativeFile, $line] = $dumpSource;

        return sprintf(' <fg=gray>// <fg=gray;href=file://%s#L%s>%s:%s</></>', $file, $line, $relativeFile, $line);
    }

    /**
     * {@inheritDoc}
     */
    protected function supportsColors(): bool
    {
        return $this->output->isDecorated();
    }
}
