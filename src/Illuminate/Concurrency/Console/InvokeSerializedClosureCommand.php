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
            $this->output->write(json_encode([
                'successful' => true,
                'result' => serialize($this->laravel->call(match (true) {
                    ! is_null($this->argument('code')) => unserialize($this->argument('code')),
                    isset($_SERVER['LARAVEL_INVOKABLE_CLOSURE']) => unserialize(
                        base64_decode($_SERVER['LARAVEL_INVOKABLE_CLOSURE'])
                    ),
                    default => fn () => null,
                })),
            ]));
        } catch (Throwable $e) {
            report($e);

            $this->output->write(json_encode([
                'successful' => false,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'parameters' => $this->exceptionParameters($e),
            ]));
        }
    }

    /**
     * Get the constructor arguments that may be used to recreate the given exception.
     *
     * Arguments are only captured when every constructor parameter is backed by a
     * property holding a JSON-representable value; otherwise an empty array is
     * returned and the exception will be recreated from its message instead.
     *
     * @param  \Throwable  $e
     * @return array<string, mixed>
     */
    protected function exceptionParameters(Throwable $e)
    {
        $reflection = new ReflectionClass($e);
        $constructor = $reflection->getConstructor();

        if (! $constructor || $constructor->getDeclaringClass()->getName() !== $reflection->getName()) {
            return [];
        }

        $parameters = [];

        foreach ($constructor->getParameters() as $parameter) {
            if (! $reflection->hasProperty($parameter->name)) {
                return [];
            }

            $property = $reflection->getProperty($parameter->name);

            if ($property->isStatic() || ! $property->isInitialized($e)) {
                return [];
            }

            $value = $property->getValue($e);

            if (! is_null($value) && ! is_scalar($value) && ! is_array($value)) {
                return [];
            }

            $parameters[$parameter->name] = $value;
        }

        return $parameters;
    }
}
