<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\LogRotateCommand;
use Orchestra\Testbench\TestCase;

class LogRotateCommandTest extends TestCase
{
    private $logsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logsPath = sys_get_temp_dir().'/laravel-log-rotate-test-'.uniqid();
        (new Filesystem)->makeDirectory($this->logsPath);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->logsPath);

        parent::tearDown();
    }

    public function testItRotatesALogFile()
    {
        file_put_contents("{$this->logsPath}/laravel.log", 'log content');

        $this->artisan(LogRotateCommand::class, ['--path' => $this->logsPath])
            ->assertExitCode(0);

        $this->assertFileDoesNotExist("{$this->logsPath}/laravel.log");
        $this->assertCount(1, glob("{$this->logsPath}/laravel.log-*.1"));
    }

    public function testItSkipsEmptyLogFiles()
    {
        file_put_contents("{$this->logsPath}/laravel.log", '');

        $this->artisan(LogRotateCommand::class, ['--path' => $this->logsPath])
            ->assertExitCode(0);

        $this->assertFileExists("{$this->logsPath}/laravel.log");
        $this->assertEmpty(glob("{$this->logsPath}/laravel.log-*"));
    }

    public function testItExitsCleanlyWithNoLogFiles()
    {
        $this->artisan(LogRotateCommand::class, ['--path' => $this->logsPath])
            ->assertExitCode(0);
    }

    public function testItBumpsExistingRotatedFiles()
    {
        file_put_contents("{$this->logsPath}/laravel.log-20260101-0000.1", 'old content');
        file_put_contents("{$this->logsPath}/laravel.log", 'new content');

        $this->artisan(LogRotateCommand::class, ['--path' => $this->logsPath])
            ->assertExitCode(0);

        $this->assertFileExists("{$this->logsPath}/laravel.log-20260101-0000.2");
        $this->assertSame('old content', file_get_contents("{$this->logsPath}/laravel.log-20260101-0000.2"));
        $this->assertCount(1, glob("{$this->logsPath}/laravel.log-*.1"));
        $this->assertFileDoesNotExist("{$this->logsPath}/laravel.log");
    }

    public function testItBumpsMultipleSequencesHighToLow()
    {
        file_put_contents("{$this->logsPath}/laravel.log-20260101-0000.1", 'seq 1');
        file_put_contents("{$this->logsPath}/laravel.log-20260101-0000.2", 'seq 2');
        file_put_contents("{$this->logsPath}/laravel.log-20260101-0000.3", 'seq 3');
        file_put_contents("{$this->logsPath}/laravel.log", 'current');

        $this->artisan(LogRotateCommand::class, ['--path' => $this->logsPath])
            ->assertExitCode(0);

        $this->assertSame('seq 3', file_get_contents("{$this->logsPath}/laravel.log-20260101-0000.4"));
        $this->assertSame('seq 2', file_get_contents("{$this->logsPath}/laravel.log-20260101-0000.3"));
        $this->assertSame('seq 1', file_get_contents("{$this->logsPath}/laravel.log-20260101-0000.2"));
        $this->assertCount(1, glob("{$this->logsPath}/laravel.log-*.1"));
    }

    public function testItPrunesOldRotationsBeyondKeepLimit()
    {
        file_put_contents("{$this->logsPath}/laravel.log-20260101-0000.1", 'keep');
        file_put_contents("{$this->logsPath}/laravel.log-20260101-0000.2", 'keep');
        file_put_contents("{$this->logsPath}/laravel.log-20260101-0000.3", 'prune');

        $this->artisan(LogRotateCommand::class, ['--path' => $this->logsPath, '--keep' => 3])
            ->assertExitCode(0);

        $this->assertFileExists("{$this->logsPath}/laravel.log-20260101-0000.2");
        $this->assertFileExists("{$this->logsPath}/laravel.log-20260101-0000.3");
        $this->assertFileDoesNotExist("{$this->logsPath}/laravel.log-20260101-0000.4");
    }

    public function testItHandlesMultipleLogFiles()
    {
        file_put_contents("{$this->logsPath}/laravel.log", 'app logs');
        file_put_contents("{$this->logsPath}/browser.log", 'browser logs');

        $this->artisan(LogRotateCommand::class, ['--path' => $this->logsPath])
            ->assertExitCode(0);

        $this->assertFileDoesNotExist("{$this->logsPath}/laravel.log");
        $this->assertFileDoesNotExist("{$this->logsPath}/browser.log");
        $this->assertCount(1, glob("{$this->logsPath}/laravel.log-*.1"));
        $this->assertCount(1, glob("{$this->logsPath}/browser.log-*.1"));
    }

    public function testSequenceSkippingWhenFileDoesNotExistForSomeRotations()
    {
        file_put_contents("{$this->logsPath}/laravel.log-20260101-0000.1", 'laravel batch 1');
        file_put_contents("{$this->logsPath}/browser.log-20260101-0000.1", 'browser batch 1');
        file_put_contents("{$this->logsPath}/laravel.log", 'laravel batch 2');

        $this->artisan(LogRotateCommand::class, ['--path' => $this->logsPath])
            ->assertExitCode(0);

        $this->assertFileExists("{$this->logsPath}/laravel.log-20260101-0000.2");
        $this->assertFileExists("{$this->logsPath}/browser.log-20260101-0000.2");
        $this->assertCount(1, glob("{$this->logsPath}/laravel.log-*.1"));
        $this->assertEmpty(glob("{$this->logsPath}/browser.log-*.1"));
    }

    public function testMultiRunCumulativeState()
    {
        file_put_contents("{$this->logsPath}/laravel.log", 'run 1 laravel');
        file_put_contents("{$this->logsPath}/browser.log", 'run 1 browser');

        $this->artisan(LogRotateCommand::class, ['--path' => $this->logsPath])
            ->assertExitCode(0);

        file_put_contents("{$this->logsPath}/laravel.log", 'run 2 laravel');

        $this->artisan(LogRotateCommand::class, ['--path' => $this->logsPath])
            ->assertExitCode(0);

        file_put_contents("{$this->logsPath}/laravel.log", 'run 3 laravel');

        $this->artisan(LogRotateCommand::class, ['--path' => $this->logsPath])
            ->assertExitCode(0);

        $browserFiles = glob("{$this->logsPath}/browser.log-*");
        $this->assertCount(1, $browserFiles);
        $this->assertStringEndsWith('.3', $browserFiles[0]);
        $this->assertSame('run 1 browser', file_get_contents($browserFiles[0]));

        $this->assertCount(1, glob("{$this->logsPath}/laravel.log-*.1"));
        $this->assertCount(1, glob("{$this->logsPath}/laravel.log-*.2"));
        $this->assertCount(1, glob("{$this->logsPath}/laravel.log-*.3"));
    }

    public function testItDoesNotCreateEmptyFileAfterRotation()
    {
        file_put_contents("{$this->logsPath}/laravel.log", 'content');

        $this->artisan(LogRotateCommand::class, ['--path' => $this->logsPath])
            ->assertExitCode(0);

        $this->assertFileDoesNotExist("{$this->logsPath}/laravel.log");
    }

    public function testItFailsWhenDirectoryDoesNotExist()
    {
        $this->artisan(LogRotateCommand::class, ['--path' => '/nonexistent/path'])
            ->assertExitCode(1);
    }

    public function testItResolvesPathFromLoggingConfig()
    {
        $this->app['config']->set('logging.channels.single', [
            'driver' => 'single',
            'path' => "{$this->logsPath}/laravel.log",
        ]);

        file_put_contents("{$this->logsPath}/laravel.log", 'content');

        $this->artisan(LogRotateCommand::class)
            ->assertExitCode(0);

        $this->assertFileDoesNotExist("{$this->logsPath}/laravel.log");
        $this->assertCount(1, glob("{$this->logsPath}/laravel.log-*.1"));
    }

    public function testItIncludesDateInRotatedFilename()
    {
        file_put_contents("{$this->logsPath}/laravel.log", 'content');
        touch("{$this->logsPath}/laravel.log", mktime(14, 30, 0, 6, 15, 2026));

        $this->artisan(LogRotateCommand::class, ['--path' => $this->logsPath])
            ->assertExitCode(0);

        $this->assertFileExists("{$this->logsPath}/laravel.log-20260615-1430.1");
    }

    public function testItIgnoresNonLogFiles()
    {
        file_put_contents("{$this->logsPath}/notes.txt", 'not a log');
        file_put_contents("{$this->logsPath}/laravel.log", 'a log');

        $this->artisan(LogRotateCommand::class, ['--path' => $this->logsPath])
            ->assertExitCode(0);

        $this->assertFileExists("{$this->logsPath}/notes.txt");
        $this->assertFileDoesNotExist("{$this->logsPath}/laravel.log");
    }
}
