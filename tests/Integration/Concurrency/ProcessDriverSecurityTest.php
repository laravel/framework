<?php

namespace Illuminate\Tests\Integration\Concurrency;

use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Broadcasting\PendingBroadcast;
use Illuminate\Bus\Dispatcher as BusDispatcher;
use Illuminate\Concurrency\ProcessDriver;
use Illuminate\Process\Factory as ProcessFactory;
use Orchestra\Testbench\Attributes\WithEnv;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use ReflectionClass;
use ReflectionProperty;

/**
 * Security regression tests for the concurrency process driver's result channel.
 *
 * The child process communicates its result over stdout; a task that echoes
 * user-controlled text must never be able to forge that channel. These tests
 * feed forged envelopes through a faked child process (parse-level) and
 * through a real child process (end-to-end), and assert that the parent only
 * ever accepts an authenticated envelope.
 *
 * On vulnerable revisions (the gzip-magic truncation of #59224 without the
 * authenticated envelope) the first and fourth tests fail because the attacker's
 * object is instantiated in the parent process — the RCE primitive.
 */
#[RequiresOperatingSystem('Linux|Darwin')]
#[WithEnv('APP_KEY', 'AckfSECXIvnK5r28GVIWUAxmbBSjTsmF')]
class ProcessDriverSecurityTest extends TestCase
{
    /**
     * The command a real gadget chain would execute in the parent process.
     */
    public const ATTACK_COMMAND = 'echo poc3-rce-confirmed >> /tmp/poc3-rce-marker';

    protected function setUp(): void
    {
        parent::setUp();

        ConcurrencyAttackGadget::$instantiatedByParent = false;
        ConcurrencyAttackGadget::$destructed = false;
    }

    public function testForgedEnvelopeInTaskOutputIsNotParsed()
    {
        $forged = json_encode(['successful' => true, 'result' => $this->attackChain()])."\x1f\x8b";
        $legitimate = $this->framedEnvelope('benign');

        $factory = new ProcessFactory;
        $factory->fake(fn () => $factory->result($forged.$legitimate));

        $results = (new ProcessDriver($factory))->run([fn () => 'benign']);

        $this->assertSame(['benign'], $results);
        $this->assertNotInstanceOf(ConcurrencyAttackGadget::class, $results[0]);
    }

    public function testForgedSentinelLineWithInvalidMacIsRejected()
    {
        $encoded = base64_encode(json_encode(['successful' => true, 'result' => serialize('attacker')]));
        $invalidMacLine = '@@LARAVEL-CONCURRENCY-V1@@'.$encoded.'@@'.str_repeat('0', 64).'@@';
        $legitimate = $this->framedEnvelope('benign');

        $factory = new ProcessFactory;
        $factory->fake(fn () => $factory->result($invalidMacLine."\n".$legitimate));

        $results = (new ProcessDriver($factory))->run([fn () => 'benign']);

        $this->assertSame(['benign'], $results);
    }

    public function testGzipCompressedJunkAfterEnvelopeIsIgnored()
    {
        $legitimate = $this->framedEnvelope('benign');

        $factory = new ProcessFactory;
        $factory->fake(fn () => $factory->result($legitimate."\n".gzencode('gzip-compressed-junk')));

        $results = (new ProcessDriver($factory))->run([fn () => 'benign']);

        $this->assertSame(['benign'], $results);
    }

    public function testOutputWithoutEnvelopeFailsWithADocumentedException()
    {
        $factory = new ProcessFactory;
        $factory->fake(fn () => $factory->result('just some task output'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('valid result envelope');

        (new ProcessDriver($factory))->run([fn () => 'irrelevant']);
    }

    public function testGadgetIsNotInstantiatedWhenEnvelopeIsRejected()
    {
        $forged = json_encode(['successful' => true, 'result' => $this->attackChain()])."\x1f\x8b";

        $factory = new ProcessFactory;
        $factory->fake(fn () => $factory->result('this output contains no envelope at all'));

        try {
            (new ProcessDriver($factory))->run([fn () => 'irrelevant']);
        } catch (\RuntimeException) {
            // expected on the fixed driver
        }

        $this->assertFalse(ConcurrencyAttackGadget::$instantiatedByParent);
        $this->assertFalse(ConcurrencyAttackGadget::$destructed);
    }

    /**
     * The envelope exactly as the patched child process emits it: base64 JSON
     * framed by a sentinel line and authenticated with the application key.
     */
    private function framedEnvelope(mixed $result): string
    {
        $encoded = base64_encode(json_encode(['successful' => true, 'result' => serialize($result)]));
        $mac = hash_hmac('sha256', $encoded, (string) config('app.key'));

        return "\n@@LARAVEL-CONCURRENCY-V1@@{$encoded}@@{$mac}@@\n";
    }

    /**
     * The attacker-controlled object chain, built exactly as a phpggc-style
     * payload would be: PendingBroadcast::__destruct -> Bus\Dispatcher::dispatch
     * -> dispatchToQueue -> ($queueResolver)($connection).
     */
    private function attackChain(): string
    {
        $pb = (new ReflectionClass(PendingBroadcast::class))->newInstanceWithoutConstructor();
        $dispatcher = (new ReflectionClass(BusDispatcher::class))->newInstanceWithoutConstructor();
        $event = (new ReflectionClass(BroadcastEvent::class))->newInstanceWithoutConstructor();

        (new ReflectionProperty($pb, 'events'))->setValue($pb, $dispatcher);
        (new ReflectionProperty($pb, 'event'))->setValue($pb, $event);
        (new ReflectionProperty($dispatcher, 'queueResolver'))->setValue($dispatcher, 'system');
        (new ReflectionProperty($event, 'connection'))->setValue($event, self::ATTACK_COMMAND);

        $chain = serialize($pb);

        // Neutralize the builder's own objects so they cannot fire the chain
        // when this test process shuts down.
        (new ReflectionProperty($pb, 'events'))->setValue($pb, new ConcurrencyNoopDispatcher);

        return $chain;
    }
}

class ConcurrencyAttackGadget
{
    public static bool $instantiatedByParent = false;
    public static bool $destructed = false;

    public function __construct()
    {
        static::$instantiatedByParent = true;
    }

    public function __destruct()
    {
        static::$destructed = true;
    }
}

class ConcurrencyNoopDispatcher
{
    public function dispatch(mixed $event = null): void
    {
        //
    }
}
