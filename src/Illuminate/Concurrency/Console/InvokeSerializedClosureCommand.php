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
        $key = $this->pullEnvelopeKey();

        try {
            $this->output->write($this->frameEnvelope($key, json_encode([
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

            $this->output->write($this->frameEnvelope($key, json_encode([
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
     * Get the envelope key passed by the parent process and remove it from the environment.
     */
    protected function pullEnvelopeKey(): string
    {
        $key = $_SERVER['LARAVEL_CONCURRENCY_HMAC_KEY'] ?? $_ENV['LARAVEL_CONCURRENCY_HMAC_KEY'] ?? getenv('LARAVEL_CONCURRENCY_HMAC_KEY');

        unset($_SERVER['LARAVEL_CONCURRENCY_HMAC_KEY'], $_ENV['LARAVEL_CONCURRENCY_HMAC_KEY']);

        putenv('LARAVEL_CONCURRENCY_HMAC_KEY');

        return is_string($key) && $key !== '' ? $key : bin2hex(random_bytes(32));
    }

    /**
     * Frame the given result payload with a marker and an HMAC signature.
     */
    protected function frameEnvelope(string $key, string $payload): string
    {
        $encoded = base64_encode($payload);

        $signature = hash_hmac('sha256', $encoded, $key);

        return "\n@@LARAVEL-CONCURRENCY-V1@@{$encoded}@@{$signature}@@\n";
    }
}
