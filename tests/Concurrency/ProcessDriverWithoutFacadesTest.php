<?php

namespace Illuminate\Tests\Concurrency;

use Illuminate\Concurrency\ProcessDriver;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Log\Context\Repository as ContextRepository;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

class ProcessDriverWithoutFacadesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }

    public function testRunPassesContextWhenFacadesAreNotRegistered()
    {
        $app = new Application(__DIR__);
        $app->make(ContextRepository::class)->add('foo', 'bar');

        $factory = new ProcessFactory;
        $factory->fake(fn ($process) => $factory->result($this->signedEnvelope($process, 'result')));

        $this->assertSame(['result'], (new ProcessDriver($factory))->run([fn () => 'result']));

        $factory->assertRan(function ($process) {
            $context = json_decode($process->environment['__LARAVEL_CONTEXT'], true);

            return unserialize($context['data']['foo']) === 'bar';
        });
    }

    public function testDeferPassesContextWhenFacadesAreNotRegistered()
    {
        $app = new Application(__DIR__);
        $app->make(ContextRepository::class)->add('foo', 'bar');

        $factory = new ProcessFactory;
        $factory->fake();

        (new ProcessDriver($factory))->defer([fn () => 'result'])();

        $factory->assertRan(function ($process) {
            $context = json_decode($process->environment['__LARAVEL_CONTEXT'], true);

            return unserialize($context['data']['foo']) === 'bar';
        });
    }

    protected function signedEnvelope($process, mixed $result): string
    {
        $encoded = base64_encode(json_encode(['successful' => true, 'result' => serialize($result)]));
        $signature = hash_hmac('sha256', $encoded, $process->environment['LARAVEL_CONCURRENCY_HMAC_KEY']);

        return "\n@@LARAVEL-CONCURRENCY-V1@@{$encoded}@@{$signature}@@\n";
    }
}
