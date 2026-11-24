<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Mockery as m;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

#[RequiresOperatingSystem('Linux')]
class CloudOutputTest extends TestCase
{
    protected $socketPath;

    protected $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->socketPath = sys_get_temp_dir().'/laravel-cloud-'.uniqid().'.sock';
        $this->server = stream_socket_server('unix://'.$this->socketPath);

        $_SERVER['LARAVEL_CLOUD'] = '1';
        $_SERVER['LARAVEL_CLOUD_LOG_SOCKET'] = 'unix://'.$this->socketPath;
    }

    protected function tearDown(): void
    {
        unset(
            $_SERVER['LARAVEL_CLOUD'],
            $_SERVER['LARAVEL_CLOUD_LOG_SOCKET'],
            $_SERVER['__LARAVEL_CLOUD_LOG_OUTPUT'],
            $_SERVER['SHELL_VERBOSITY'],
            $_ENV['SHELL_VERBOSITY'],
        );

        putenv('SHELL_VERBOSITY');

        fclose($this->server);
        @unlink($this->socketPath);

        parent::tearDown();
    }

    public function test_scheduled_command_output_is_sent_to_the_cloud_log_socket_when_the_scheduler_is_quiet()
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

        (new Event($mutex, escapeshellarg(PHP_BINARY).' -r '.escapeshellarg($script)))->run($this->app);

        $this->assertSame("first line\nsecond line\n", $this->readSocket());
    }

    public function test_commands_marked_for_cloud_logging_write_their_output_to_the_cloud_log_socket()
    {
        $_SERVER['__LARAVEL_CLOUD_LOG_OUTPUT'] = '1';

        Artisan::command('cloud:output-test', function () {
            $this->info('from the command');
        });

        $output = new BufferedOutput;

        $this->app[Kernel::class]->handle(new ArrayInput(['command' => 'cloud:output-test']), $output);

        $this->assertSame("from the command\n", $this->readSocket());
        $this->assertSame('', $output->fetch());
    }

    protected function readSocket()
    {
        $client = stream_socket_accept($this->server, 1);

        stream_set_blocking($client, false);

        return stream_get_contents($client);
    }
}
