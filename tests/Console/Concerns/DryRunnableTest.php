<?php

namespace Illuminate\Tests\Console\Concerns;

use Illuminate\Console\Command;
use Illuminate\Console\Concerns\DryRunnable;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class DryRunnableTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testConfigureDryRunAddsOption(): void
    {
        $command = new class extends Command
        {
            use DryRunnable;

            protected $name = 'test:command';

            public function __construct()
            {
                parent::__construct();
                $this->configureDryRun();
            }

            public function handle()
            {
                //
            }
        };

        $this->assertTrue($command->getDefinition()->hasOption('dry-run'));
        $this->assertEquals(
            'Preview the operations that would be performed without executing them',
            $command->getDefinition()->getOption('dry-run')->getDescription()
        );
    }

    public function testIsDryRunReturnsTrueWhenOptionSet(): void
    {
        $command = new class extends Command
        {
            use DryRunnable;

            protected $name = 'test:command';

            public function __construct()
            {
                parent::__construct();
                $this->configureDryRun();
            }

            public function handle()
            {
                return $this->isDryRun();
            }
        };

        $laravel = m::mock(\Illuminate\Contracts\Foundation\Application::class);
        $laravel->shouldReceive('make')
            ->with(OutputStyle::class, m::any())
            ->andReturn(m::mock(OutputStyle::class));
        $laravel->shouldReceive('make')
            ->with(Factory::class, m::any())
            ->andReturn(m::mock(Factory::class));
        $laravel->shouldReceive('call')
            ->once()
            ->andReturnUsing(function ($callback) use ($command) {
                return $callback[0]->{$callback[1]}();
            });

        $command->setLaravel($laravel);

        $input = new ArrayInput(['--dry-run' => true]);
        $output = new BufferedOutput;

        $command->run($input, $output);

        $this->assertTrue($command->handle());
    }

    public function testIsDryRunReturnsFalseWhenOptionNotSet(): void
    {
        $command = new class extends Command
        {
            use DryRunnable;

            protected $name = 'test:command';

            public function __construct()
            {
                parent::__construct();
                $this->configureDryRun();
            }

            public function handle()
            {
                return $this->isDryRun();
            }
        };

        $laravel = m::mock(\Illuminate\Contracts\Foundation\Application::class);
        $laravel->shouldReceive('make')
            ->with(OutputStyle::class, m::any())
            ->andReturn(m::mock(OutputStyle::class));
        $laravel->shouldReceive('make')
            ->with(Factory::class, m::any())
            ->andReturn(m::mock(Factory::class));
        $laravel->shouldReceive('call')
            ->once()
            ->andReturnUsing(function ($callback) use ($command) {
                return $callback[0]->{$callback[1]}();
            });

        $command->setLaravel($laravel);

        $input = new ArrayInput([]);
        $output = new BufferedOutput;

        $command->run($input, $output);

        $this->assertFalse($command->handle());
    }

    public function testRecordDryRunOperationStoresOperation(): void
    {
        $command = new class extends Command
        {
            use DryRunnable;

            protected $name = 'test:command';

            public function __construct()
            {
                parent::__construct();
                $this->configureDryRun();
            }

            public function handle()
            {
                $this->recordDryRunOperation('CREATE', 'Would create file', ['Path' => '/path/to/file']);
            }

            public function getOperations()
            {
                return $this->getDryRunOperations();
            }
        };

        $laravel = m::mock(\Illuminate\Contracts\Foundation\Application::class);
        $laravel->shouldReceive('make')
            ->with(OutputStyle::class, m::any())
            ->andReturn(m::mock(OutputStyle::class));
        $laravel->shouldReceive('make')
            ->with(Factory::class, m::any())
            ->andReturn(m::mock(Factory::class));
        $laravel->shouldReceive('call')
            ->once()
            ->andReturnUsing(function ($callback) use ($command) {
                return $callback[0]->{$callback[1]}();
            });

        $command->setLaravel($laravel);

        $input = new ArrayInput([]);
        $output = new BufferedOutput;

        $command->run($input, $output);

        $operations = $command->getOperations();

        $this->assertCount(1, $operations);
        $this->assertEquals('CREATE', $operations[0]['type']);
        $this->assertEquals('Would create file', $operations[0]['description']);
        $this->assertEquals(['Path' => '/path/to/file'], $operations[0]['details']);
    }

    public function testClearDryRunOperationsRemovesAllOperations(): void
    {
        $command = new class extends Command
        {
            use DryRunnable;

            protected $name = 'test:command';

            public function __construct()
            {
                parent::__construct();
                $this->configureDryRun();
            }

            public function handle()
            {
                $this->recordDryRunOperation('CREATE', 'Would create file 1');
                $this->recordDryRunOperation('CREATE', 'Would create file 2');

                $this->clearDryRunOperations();
            }

            public function getOperations()
            {
                return $this->getDryRunOperations();
            }
        };

        $laravel = m::mock(\Illuminate\Contracts\Foundation\Application::class);
        $laravel->shouldReceive('make')
            ->with(OutputStyle::class, m::any())
            ->andReturn(m::mock(OutputStyle::class));
        $laravel->shouldReceive('make')
            ->with(Factory::class, m::any())
            ->andReturn(m::mock(Factory::class));
        $laravel->shouldReceive('call')
            ->once()
            ->andReturnUsing(function ($callback) use ($command) {
                return $callback[0]->{$callback[1]}();
            });

        $command->setLaravel($laravel);

        $input = new ArrayInput([]);
        $output = new BufferedOutput;

        $command->run($input, $output);

        $operations = $command->getOperations();

        $this->assertEmpty($operations);
    }

    public function testDisplayDryRunOperationsShowsFormattedOutput(): void
    {
        $command = new class extends Command
        {
            use DryRunnable;

            protected $name = 'test:command';

            public function __construct()
            {
                parent::__construct();
                $this->configureDryRun();
            }

            public function handle()
            {
                $this->recordDryRunOperation('CREATE', 'Would create Model', [
                    'Path' => '/app/Models/Post.php',
                    'Class' => 'Post',
                ]);

                $this->displayDryRunOperations();
            }
        };

        $input = new ArrayInput(['--dry-run' => true]);
        $output = new BufferedOutput;

        $outputStyle = m::mock(OutputStyle::class);
        $components = m::mock(Factory::class);

        $laravel = m::mock(\Illuminate\Contracts\Foundation\Application::class);
        $laravel->shouldReceive('make')
            ->with(OutputStyle::class, m::any())
            ->andReturn($outputStyle);
        $laravel->shouldReceive('make')
            ->with(Factory::class, m::any())
            ->andReturn($components);
        $laravel->shouldReceive('call')
            ->once()
            ->andReturnUsing(function ($callback) use ($command) {
                return $callback[0]->{$callback[1]}();
            });

        $command->setLaravel($laravel);

        $components->shouldReceive('warn')
            ->with('DRY RUN MODE - No changes will be made')
            ->once();

        $components->shouldReceive('info')
            ->with('The following 1 operation(s) would be performed:')
            ->once();

        $components->shouldReceive('twoColumnDetail')
            ->with(m::pattern('/<fg=cyan>\[1\] CREATE<\/>/'), 'Would create Model')
            ->once();

        $components->shouldReceive('info')
            ->with('Run the command without --dry-run to execute these operations.')
            ->once();

        $outputStyle->shouldReceive('newLine')->atLeast()->once();
        $outputStyle->shouldReceive('writeln')->atLeast()->once();

        $command->run($input, $output);
    }
}
