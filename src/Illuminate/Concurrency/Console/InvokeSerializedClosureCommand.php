<?php

namespace Illuminate\Concurrency\Console;

use Illuminate\Concurrency\TaskResult;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'invoke-serialized-closure')]
class InvokeSerializedClosureCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'invoke-serialized-closure {code? : The serialized closure}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invoke the given serialized closure';

    /**
     * Indicates whether the command should be shown in the Artisan command list.
     *
     * @var bool
     */
    protected $hidden = true;

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function handle()
    {
        try {
            $this->output->write(json_encode(TaskResult::success($this->laravel->call(match (true) {
                ! is_null($this->argument('code')) => unserialize($this->argument('code')),
                isset($_SERVER['LARAVEL_INVOKABLE_CLOSURE']) => unserialize(
                    base64_decode($_SERVER['LARAVEL_INVOKABLE_CLOSURE'])
                ),
                default => fn () => null,
            }))));
        } catch (Throwable $e) {
            report($e);

            $this->output->write(json_encode(TaskResult::failure($e)));
        }
    }
}
