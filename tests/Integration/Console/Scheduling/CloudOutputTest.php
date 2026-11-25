<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Mockery as m;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;

#[RequiresOperatingSystem('Linux')]
class CloudOutputTest extends TestCase
{
    protected $outputPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputPath = sys_get_temp_dir().'/laravel-cloud-'.uniqid().'.log';

        $_SERVER['LARAVEL_CLOUD'] = '1';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['LARAVEL_CLOUD'], $_SERVER['SHELL_VERBOSITY'], $_ENV['SHELL_VERBOSITY']);

        putenv('SHELL_VERBOSITY');

        @unlink($this->outputPath);

        parent::tearDown();
    }

    public function test_scheduled_commands_do_not_inherit_quiet_verbosity_on_cloud()
    {
        putenv('SHELL_VERBOSITY=-1');
        $_ENV['SHELL_VERBOSITY'] = $_SERVER['SHELL_VERBOSITY'] = -1;

        $mutex = m::mock(EventMutex::class);
        $mutex->shouldReceive('create')->andReturn(true);
        $mutex->shouldReceive('forget');

        $script = 'require '.var_export(realpath(__DIR__.'/../../../../vendor/autoload.php'), true).';'
            .' (new Symfony\Component\Console\SingleCommandApplication)'
            .'->setCode(function (Symfony\Component\Console\Input\InputInterface $input, Symfony\Component\Console\Output\OutputInterface $output) { $output->writeln("first line"); $output->writeln("second line"); return 0; })'
            .'->run();';

        (new Event($mutex, escapeshellarg(PHP_BINARY).' -r '.escapeshellarg($script)))
            ->sendOutputTo($this->outputPath)
            ->run($this->app);

        $this->assertSame("first line\nsecond line\n", file_get_contents($this->outputPath));
    }
}
