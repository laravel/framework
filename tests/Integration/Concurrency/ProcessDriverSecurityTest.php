<?php

namespace Illuminate\Tests\Integration\Concurrency;

use Illuminate\Concurrency\ProcessDriver;
use Illuminate\Process\Factory as ProcessFactory;
use Orchestra\Testbench\Attributes\WithEnv;
use Orchestra\Testbench\TestCase;
use RuntimeException;

#[WithEnv('APP_KEY', 'AckfSECXIvnK5r28GVIWUAxmbBSjTsmF')]
class ProcessDriverSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ConcurrencyEnvelopeGadget::$woken = false;
    }

    public function testForgedResultInTaskOutputIsIgnored()
    {
        $forged = json_encode(['successful' => true, 'result' => serialize(new ConcurrencyEnvelopeGadget)])."\x1f\x8b";

        $factory = new ProcessFactory;
        $factory->fake(fn ($process) => $factory->result($forged.$this->signedEnvelope($process, 'benign')));

        $this->assertSame(['benign'], (new ProcessDriver($factory))->run([fn () => 'benign']));
        $this->assertFalse(ConcurrencyEnvelopeGadget::$woken);
    }

    public function testEnvelopeWithInvalidSignatureIsIgnored()
    {
        $factory = new ProcessFactory;
        $factory->fake(fn ($process) => $factory->result(
            $this->envelope(serialize(new ConcurrencyEnvelopeGadget), str_repeat('0', 64)).$this->signedEnvelope($process, 'benign')
        ));

        $this->assertSame(['benign'], (new ProcessDriver($factory))->run([fn () => 'benign']));
        $this->assertFalse(ConcurrencyEnvelopeGadget::$woken);
    }

    public function testEnvelopeSignedWithTheApplicationKeyIsRejected()
    {
        $this->assertForgedEnvelopeIsRejected((string) config('app.key'));
    }

    #[WithEnv('APP_KEY', '')]
    public function testEnvelopeSignedWithAnEmptyKeyIsRejected()
    {
        $this->assertSame('', (string) config('app.key'));

        $this->assertForgedEnvelopeIsRejected('');
    }

    public function testGzipCompressedOutputAfterEnvelopeIsIgnored()
    {
        $factory = new ProcessFactory;
        $factory->fake(fn ($process) => $factory->result(
            $this->signedEnvelope($process, 'benign').gzencode('gzip-compressed-output')
        ));

        $this->assertSame(['benign'], (new ProcessDriver($factory))->run([fn () => 'benign']));
    }

    public function testOutputWithoutEnvelopeThrowsException()
    {
        $factory = new ProcessFactory;
        $factory->fake(fn () => $factory->result('just some task output'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('did not return a valid result envelope. Output: just some task output');

        (new ProcessDriver($factory))->run([fn () => 'irrelevant']);
    }

    public function testEachTaskReceivesADistinctRandomKey()
    {
        $keys = [];

        $factory = new ProcessFactory;
        $factory->fake(function ($process) use ($factory, &$keys) {
            $keys[] = $process->environment['LARAVEL_CONCURRENCY_HMAC_KEY'];

            return $factory->result($this->signedEnvelope($process, 'result'));
        });

        (new ProcessDriver($factory))->run([fn () => 'result', fn () => 'result']);

        $this->assertCount(2, $keys);
        $this->assertCount(2, array_unique($keys));
        $this->assertNotContains((string) config('app.key'), $keys);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $keys[0]);
    }

    public function testDeferredTasksDoNotReceiveAKey()
    {
        $factory = new ProcessFactory;
        $factory->fake();

        $deferred = (new ProcessDriver($factory))->defer(fn () => null);

        $deferred();

        $factory->assertRan(fn ($process) => ! array_key_exists('LARAVEL_CONCURRENCY_HMAC_KEY', $process->environment));
    }

    /**
     * Assert that an envelope signed with the given key is not accepted.
     */
    protected function assertForgedEnvelopeIsRejected(string $key): void
    {
        $result = serialize(new ConcurrencyEnvelopeGadget);
        $encoded = base64_encode(json_encode(['successful' => true, 'result' => $result]));

        $factory = new ProcessFactory;
        $factory->fake(fn () => $factory->result($this->envelope($result, hash_hmac('sha256', $encoded, $key))));

        try {
            (new ProcessDriver($factory))->run([fn () => 'irrelevant']);

            $this->fail('The forged envelope was accepted.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('did not return a valid result envelope', $e->getMessage());
        }

        $this->assertFalse(ConcurrencyEnvelopeGadget::$woken);
    }

    /**
     * Build an envelope signed with the key the driver passed to the given process.
     */
    protected function signedEnvelope($process, mixed $result): string
    {
        $encoded = base64_encode(json_encode(['successful' => true, 'result' => serialize($result)]));

        return $this->envelope(
            serialize($result),
            hash_hmac('sha256', $encoded, $process->environment['LARAVEL_CONCURRENCY_HMAC_KEY']),
        );
    }

    /**
     * Build an envelope for the given serialized result and signature.
     */
    protected function envelope(string $result, string $signature): string
    {
        $encoded = base64_encode(json_encode(['successful' => true, 'result' => $result]));

        return "\n@@LARAVEL-CONCURRENCY-V1@@{$encoded}@@{$signature}@@\n";
    }
}

class ConcurrencyEnvelopeGadget
{
    public static bool $woken = false;

    public function __wakeup()
    {
        static::$woken = true;
    }
}
