<?php

namespace Illuminate\Support\Facades;

use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;

/**
 * @method static int handle( \Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) Run the console application.
 * @method static void terminate( \Symfony\Component\Console\Input\InputInterface $input, int $status) Terminate the application.
 * @method static \Illuminate\Foundation\Console\ClosureCommand command( string $signature, \Closure $callback) Register a Closure based command with the application.
 * @method static void registerCommand( \Symfony\Component\Console\Command\Command $command) Register the given command with the console application.
 * @method static int call( string $command, array $parameters, \Symfony\Component\Console\Output\OutputInterface $outputBuffer) Run an Artisan console command by name.
 * @method static \Illuminate\Foundation\Bus\PendingDispatch queue( string $command, array $parameters) Queue the given console command.
 * @method static array all() Get all of the commands registered with the console.
 * @method static string output() Get the output for the last run command.
 * @method static void bootstrap() Bootstrap the application for artisan commands.
 * @method static void setArtisan( \Illuminate\Console\Application $artisan) Set the Artisan application instance.
 *
 * @see \Illuminate\Contracts\Console\Kernel
 */
class Artisan extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ConsoleKernelContract::class;
    }
}
