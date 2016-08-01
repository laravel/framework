<?php

use Mockery as m;
use Illuminate\Console\Scheduling\Schedule;

class ConsoleEventSchedulerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testExecCreatesNewCommand()
    {
        $escape = '\\' === DIRECTORY_SEPARATOR ? '"' : '\'';
        $escapeReal = '\\' === DIRECTORY_SEPARATOR ? '\\"' : '"';

        $schedule = new Schedule;
        $schedule->exec('path/to/command');
        $schedule->exec('path/to/command -f --foo="bar"');
        $schedule->exec('path/to/command', ['-f']);
        $schedule->exec('path/to/command', ['--foo' => 'bar']);
        $schedule->exec('path/to/command', ['-f', '--foo' => 'bar']);
        $schedule->exec('path/to/command', ['--title' => 'A "real" test']);

        $events = $schedule->events();
        $this->assertEquals('path/to/command', $events[0]->command);
        $this->assertEquals('path/to/command -f --foo="bar"', $events[1]->command);
        $this->assertEquals('path/to/command -f', $events[2]->command);
        $this->assertEquals("path/to/command --foo={$escape}bar{$escape}", $events[3]->command);
        $this->assertEquals("path/to/command -f --foo={$escape}bar{$escape}", $events[4]->command);
        $this->assertEquals("path/to/command --title={$escape}A {$escapeReal}real{$escapeReal} test{$escape}", $events[5]->command);
    }

    public function testCommandCreatesNewArtisanCommand()
    {
        $escape = '\\' === DIRECTORY_SEPARATOR ? '"' : '\'';

        $schedule = new Schedule;
        $schedule->command('queue:listen');
        $schedule->command('queue:listen --tries=3');
        $schedule->command('queue:listen', ['--tries' => 3]);

        $events = $schedule->events();
        $binary = $escape.PHP_BINARY.$escape;
        $this->assertEquals($binary.' artisan queue:listen', $events[0]->command);
        $this->assertEquals($binary.' artisan queue:listen --tries=3', $events[1]->command);
        $this->assertEquals($binary.' artisan queue:listen --tries=3', $events[2]->command);
    }
}
