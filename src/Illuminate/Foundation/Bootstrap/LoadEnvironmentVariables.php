<?php

namespace Illuminate\Foundation\Bootstrap;

use Dotenv\Dotenv;
use Illuminate\Support\Env;
use Dotenv\Exception\InvalidFileException;
use Symfony\Component\Console\Input\ArgvInput;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Console\Output\ConsoleOutput;

class LoadEnvironmentVariables
{
    /**
     * The console input object.
     *
     * @var ArgvInput
     */
    private $input;

    public function __construct(ArgvInput $input)
    {
        $this->input = $input;
    }

    /**
     * Bootstrap the given application.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        if ($app->configurationIsCached()) {
            return;
        }

        $this->checkForSpecificEnvironmentFile($app);

        try {
            $this->createDotenv($app)->safeLoad();
        } catch (InvalidFileException $e) {
            $this->writeErrorAndDie($e);
        }
    }

    /**
     * Detect if a custom environment file matching the APP_ENV exists.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @return void
     */
    protected function checkForSpecificEnvironmentFile($app)
    {
        if ($app->runningInConsole() && $this->setFileFromCliParameter($app)) {
            return;
        }

        if (! env('APP_ENV')) {
            return;
        }

        $this->setEnvironmentFilePath(
            $app, $app->environmentFile().'.'.env('APP_ENV')
        );
    }

    /**
     * Attempt to set the environment file from CLI parameters.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return  bool
     */
    protected function setFileFromCliParameter($app)
    {
        if ($this->input->hasParameterOption('--env-file')) {
            $filename = $this->input->getParameterOption('--env-file');
        } elseif ($this->input->hasParameterOption('--env')) {
            $filename = $app->environmentFile().'.'.$this->input->getParameterOption('--env');
        }

        return isset($filename) && $this->setEnvironmentFilePath($app, $filename);
    }

    /**
     * Load a custom environment file.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  string  $file
     * @return bool
     */
    protected function setEnvironmentFilePath($app, $file)
    {
        if (file_exists($app->environmentPath().'/'.$file)) {
            $app->loadEnvironmentFrom($file);

            return true;
        }

        return false;
    }

    /**
     * Create a Dotenv instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return \Dotenv\Dotenv
     */
    protected function createDotenv($app)
    {
        return Dotenv::create(
            $app->environmentPath(),
            $app->environmentFile(),
            Env::getFactory()
        );
    }

    /**
     * Write the error information to the screen and exit.
     *
     * @param  \Dotenv\Exception\InvalidFileException  $e
     * @return void
     */
    protected function writeErrorAndDie(InvalidFileException $e)
    {
        $output = (new ConsoleOutput)->getErrorOutput();

        $output->writeln('The environment file is invalid!');
        $output->writeln($e->getMessage());

        die(1);
    }
}
