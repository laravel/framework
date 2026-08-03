<?php

namespace Illuminate\Tests\Foundation\ConsoleDumps;

use Illuminate\Foundation\Console\CliDumper;
use Illuminate\Foundation\ConsoleDumps\DumpRenderer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\VarDumper\Cloner\VarCloner;

class DumpRendererTest extends TestCase
{
    public function test_it_renders_dumps_with_their_source()
    {
        $output = new BufferedOutput;
        $renderer = new DumpRenderer(
            new CliDumper($output, '/app', '/app/storage/framework/views'),
            '/app',
        );

        $renderer->render(
            (new VarCloner)->cloneVar(['name' => 'Taylor']),
            [
                'source' => [
                    'file' => '/app/routes/web.php',
                    'line' => 10,
                ],
            ],
        );

        $rendered = $output->fetch();

        $this->assertStringContainsString('"Taylor"', $rendered);
        $this->assertStringContainsString('routes/web.php:10', $rendered);
    }

    public function test_it_ignores_invalid_source_context()
    {
        $output = new BufferedOutput;
        $renderer = new DumpRenderer(
            new CliDumper($output, '/app', '/app/storage/framework/views'),
            '/app',
        );

        $renderer->render(
            (new VarCloner)->cloneVar('value'),
            ['source' => ['file' => null, 'line' => 'invalid']],
        );

        $this->assertSame("\"value\"\n", $output->fetch());
    }

    public function test_it_ignores_non_array_source_context()
    {
        $output = new BufferedOutput;
        $renderer = new DumpRenderer(
            new CliDumper($output, '/app', '/app/storage/framework/views'),
            '/app',
        );

        $renderer->render(
            (new VarCloner)->cloneVar('value'),
            ['source' => 'invalid'],
        );

        $this->assertSame("\"value\"\n", $output->fetch());
    }
}
