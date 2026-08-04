<?php

namespace Illuminate\Tests\Foundation\ConsoleDumps;

use Illuminate\Foundation\ConsoleDumps\DumpClient;
use Illuminate\Foundation\ConsoleDumps\DumpHelper;
use Illuminate\Support\Benchmark;
use Illuminate\Support\ValidatedInput;
use Orchestra\Testbench\TestCase;
use Symfony\Component\VarDumper\Caster\ScalarStub;

class DumpHelperTest extends TestCase
{
    public function testItIsRegisteredAsASingleton()
    {
        $this->assertSame(
            $this->app->make(DumpHelper::class),
            $this->app->make(DumpHelper::class),
        );
    }

    public function testItDumpsAndReturnsASingleValue()
    {
        $client = new FakeDumpClient;
        $helper = new DumpHelper($client, $this->app);

        $line = __LINE__ + 1;
        $result = $helper->dump('value');

        $this->assertSame('value', $result);
        $this->assertSame('value', $client->dumps[0][0]);
        $this->assertSame(__FILE__, $client->dumps[0][1]['source']['file']);
        $this->assertSame($line, $client->dumps[0][1]['source']['line']);
    }

    public function testItDumpsAndReturnsMultipleValues()
    {
        $client = new FakeDumpClient;
        $helper = new DumpHelper($client, $this->app);

        $result = $helper->dump(first: 'one', second: 'two');

        $this->assertSame(['first' => 'one', 'second' => 'two'], $result);
        $this->assertSame('one', $client->dumps[0][0]);
        $this->assertSame('two', $client->dumps[1][0]);
        $this->assertSame('first', $client->dumps[0][2]);
        $this->assertSame('second', $client->dumps[1][2]);
    }

    public function testItDumpsAMarkerWhenNoValuesAreGiven()
    {
        $client = new FakeDumpClient;
        $helper = new DumpHelper($client, $this->app);

        $this->assertNull($helper->dump());
        $this->assertInstanceOf(ScalarStub::class, $client->dumps[0][0]);
    }

    public function testTheGlobalHelperResolvesTheDumpHelper()
    {
        $client = new FakeDumpClient;
        $this->app->instance(DumpHelper::class, new DumpHelper($client, $this->app));

        $line = __LINE__ + 1;
        $result = dc('value');

        $this->assertSame('value', $result);
        $this->assertSame(__FILE__, $client->dumps[0][1]['source']['file']);
        $this->assertSame($line, $client->dumps[0][1]['source']['line']);
    }

    public function testDumpableObjectsCanBeSentToTheConsole()
    {
        $client = new FakeDumpClient;
        $this->app->instance(DumpHelper::class, new DumpHelper($client, $this->app));

        $input = new ValidatedInput(['name' => 'Taylor']);

        $line = __LINE__ + 1;
        $result = $input->dc();

        $this->assertSame($input, $result);
        $this->assertSame($input, $client->dumps[0][0]);
        $this->assertSame(__FILE__, $client->dumps[0][1]['source']['file']);
        $this->assertSame($line, $client->dumps[0][1]['source']['line']);
    }

    public function testBenchmarkCanBeSentToTheConsoleWhileReturningTheResult()
    {
        $client = new FakeDumpClient;
        $this->app->instance(DumpHelper::class, new DumpHelper($client, $this->app));

        $expected = new \stdClass;
        $calls = 0;

        $line = __LINE__ + 1;
        $result = Benchmark::dc(function () use ($expected, &$calls) {
            $calls++;

            return $expected;
        });

        $this->assertSame($expected, $result);
        $this->assertSame(1, $calls);
        $this->assertStringEndsWith('ms', $client->dumps[0][0]);
        $this->assertSame('duration', $client->dumps[0][2]);
        $this->assertSame($expected, $client->dumps[1][0]);
        $this->assertSame('result', $client->dumps[1][2]);
        $this->assertSame(__FILE__, $client->dumps[0][1]['source']['file']);
        $this->assertSame($line, $client->dumps[0][1]['source']['line']);
    }

    public function testItNeverAllowsClientFailuresToAffectTheApplication()
    {
        $helper = new DumpHelper(new FailingDumpClient, $this->app);

        $this->assertSame('value', $helper->dump('value'));
    }

    public function testItDumpsWithoutAConfiguredCompiledViewPath()
    {
        $this->app['config']->set('view.compiled');

        $client = new FakeDumpClient;
        $helper = new DumpHelper($client, $this->app);

        $this->assertSame('value', $helper->dump('value'));
        $this->assertSame('value', $client->dumps[0][0]);
    }
}

class FakeDumpClient extends DumpClient
{
    public array $dumps = [];

    public function __construct()
    {
        //
    }

    public function dump(mixed $value, array $context = [], ?string $label = null): void
    {
        $this->dumps[] = [$value, $context, $label];
    }
}

class FailingDumpClient extends DumpClient
{
    public function __construct()
    {
        //
    }

    public function dump(mixed $value, array $context = [], ?string $label = null): void
    {
        throw new \RuntimeException('Unable to dump the value.');
    }
}
