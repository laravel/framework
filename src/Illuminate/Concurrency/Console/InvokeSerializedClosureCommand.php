<?php

namespace Illuminate\Concurrency\Console;

use Illuminate\Console\Command;
use ReflectionClass;
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
            $this->output->write($this->frameEnvelope(json_encode([
                'successful' => true,
                'result' => serialize($this->laravel->call(match (true) {
                    ! is_null($this->argument('code')) => unserialize($this->argument('code')),
                    isset($_SERVER['LARAVEL_INVOKABLE_CLOSURE']) => unserialize(
                        base64_decode($_SERVER['LARAVEL_INVOKABLE_CLOSURE'])
                    ),
                    default => fn () => null,
                })),
            ])));
        } catch (Throwable $e) {
            report($e);

            $reflection = new ReflectionClass($e);
            $constructor = $reflection->getConstructor();
            $parameters = [];

            if ($constructor) {
                $declaringClass = $constructor->getDeclaringClass()->getName();

                if ($declaringClass === $reflection->getName()) {
                    foreach ($constructor->getParameters() as $parameter) {
                        $parameters[$parameter->name] = $e->{$parameter->name} ?? null;
                    }
                }
            }

            $this->output->write($this->frameEnvelope(json_encode([
                'successful' => false,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'parameters' => $parameters,
            ])));
        }
    }

    /**
     * Frame the result envelope with a sentinel line and an HMAC keyed by the
     * concurrency MAC key (LARAVEL_CONCURRENCY_HMAC_KEY, passed by the parent
     * with the spawn, falling back to the application key) so that task output
     * cannot be mistaken for the result channel.
     */
    protected function frameEnvelope(string $payload): string
    {
        $encoded = base64_encode($payload);
        $macKey = getenv('LARAVEL_CONCURRENCY_HMAC_KEY') ?: (string) $this->laravel->make('config')->get('app.key');
        $mac = hash_hmac('sha256', $encoded, $macKey);

        return "\n@@LARAVEL-CONCURRENCY-V1@@{$encoded}@@{$mac}@@\n";
    }
}
