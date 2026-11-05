<?php

namespace Illuminate\Tests\Integration\Concurrency\Console;

use Illuminate\Concurrency\Console\InvokeSerializedClosureCommand;
use Illuminate\Concurrency\ProcessDriver;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Laravel\SerializableClosure\SerializableClosure;
use Orchestra\Testbench\TestCase;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Console\Output\BufferedOutput;

class CustomParameterException extends RuntimeException
{
    public function __construct(
        public string $customParam,
        string $message = '',
    ) {
        parent::__construct($message ?: "Exception with param: {$customParam}");
    }
}

class InvokeSerializedClosureCommandTest extends TestCase
{
    private const KEY = 'test-envelope-key';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app[Kernel::class]->registerCommand(new InvokeSerializedClosureCommand);

        $_SERVER['LARAVEL_CONCURRENCY_HMAC_KEY'] = self::KEY;
    }

    protected function tearDown(): void
    {
        unset($_SERVER['LARAVEL_CONCURRENCY_HMAC_KEY']);

        parent::tearDown();
    }

    public function testItCanInvokeSerializedClosureFromArgument()
    {
        // Create a simple closure and serialize it
        $closure = fn () => 'Hello, World!';
        $serialized = serialize(new SerializableClosure($closure));

        // Create a new output buffer
        $output = new BufferedOutput;

        // Call the command with the serialized closure
        Artisan::call('invoke-serialized-closure', [
            'code' => $serialized,
        ], $output);

        // Extract the signed result envelope...
        $result = $this->envelopeFrom($output->fetch());

        // Verify the result
        $this->assertTrue($result['successful']);
        $this->assertSame('Hello, World!', unserialize($result['result']));
    }

    public function testItCanInvokeSerializedClosureFromEnvironment()
    {
        // Create a simple closure and serialize it
        $closure = fn () => 'From Environment';
        $serialized = serialize(new SerializableClosure($closure));

        // Set the environment variable
        $_SERVER['LARAVEL_INVOKABLE_CLOSURE'] = base64_encode($serialized);

        // Create a new output buffer
        $output = new BufferedOutput;

        // Call the command without arguments
        Artisan::call('invoke-serialized-closure', [], $output);

        // Extract the signed result envelope...
        $result = $this->envelopeFrom($output->fetch());

        // Verify the result
        $this->assertTrue($result['successful']);
        $this->assertSame('From Environment', unserialize($result['result']));

        // Clean up
        unset($_SERVER['LARAVEL_INVOKABLE_CLOSURE']);
    }

    public function testItReturnsNullWhenNoClosureIsProvided()
    {
        // Create a new output buffer
        $output = new BufferedOutput;

        // Call the command without arguments
        Artisan::call('invoke-serialized-closure', [], $output);

        // Extract the signed result envelope...
        $result = $this->envelopeFrom($output->fetch());

        // Verify the result
        $this->assertTrue($result['successful']);
        $this->assertNull(unserialize($result['result']));
    }

    public function testItHandlesExceptionsGracefully()
    {
        // Create a closure that throws an exception
        $closure = fn () => throw new RuntimeException('Test exception');
        $serialized = serialize(new SerializableClosure($closure));

        // Create a new output buffer
        $output = new BufferedOutput;

        // Call the command with the serialized closure
        Artisan::call('invoke-serialized-closure', [
            'code' => $serialized,
        ], $output);

        // Extract the signed result envelope...
        $result = $this->envelopeFrom($output->fetch());

        // Verify the exception was caught
        $this->assertFalse($result['successful']);
        $this->assertSame('RuntimeException', $result['exception']);
        $this->assertSame('Test exception', $result['message']);
    }

    public function testItHandlesCustomExceptionWithParameters()
    {
        // Create a closure that throws an exception with parameters
        $closure = fn () => throw new CustomParameterException('Test param');
        $serialized = serialize(new SerializableClosure($closure));

        // Create a new output buffer
        $output = new BufferedOutput;

        // Call the command with the serialized closure
        Artisan::call('invoke-serialized-closure', [
            'code' => $serialized,
        ], $output);

        // Extract the signed result envelope...
        $result = $this->envelopeFrom($output->fetch());

        // Verify the exception was caught and parameters were captured
        $this->assertFalse($result['successful']);
        $this->assertArrayHasKey('parameters', $result);
        $this->assertSame('Test param', $result['parameters']['customParam'] ?? null);
    }

    public function testEnvelopeKeyIsRemovedFromTheEnvironmentBeforeTheTaskRuns()
    {
        putenv('LARAVEL_CONCURRENCY_HMAC_KEY='.self::KEY);

        $closure = fn () => [
            $_SERVER['LARAVEL_CONCURRENCY_HMAC_KEY'] ?? null,
            $_ENV['LARAVEL_CONCURRENCY_HMAC_KEY'] ?? null,
            getenv('LARAVEL_CONCURRENCY_HMAC_KEY'),
        ];

        $output = new BufferedOutput;

        Artisan::call('invoke-serialized-closure', [
            'code' => serialize(new SerializableClosure($closure)),
        ], $output);

        $result = $this->envelopeFrom($output->fetch());

        $this->assertSame([null, null, false], unserialize($result['result']));
    }

    /**
     * Extract the signed result envelope from the command output using the process driver's parser.
     */
    private function envelopeFrom(string $output): array
    {
        $driver = $this->app->make(ProcessDriver::class);
        $method = new ReflectionMethod($driver, 'parseEnvelope');

        return $method->invoke($driver, $output, self::KEY);
    }
}
