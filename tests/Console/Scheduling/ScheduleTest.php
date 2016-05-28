<?php

use Illuminate\Console\Scheduling\Schedule;

class ScheduleTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Illuminate\Console\Scheduling\Schedule
     */
    private $schedule;

    protected function setUp()
    {
        $this->schedule = new Schedule();
    }

    public function testParsesOptiong()
    {
        $options = [
            '--empty-option'         => true,
            '--empty-option-missing' => false,
            '--integer'              => 123,
            '--string'               => 'word',
        ];

        $command = $this->schedule->command('command', $options)->command;

        $command = preg_split('/\s+/', $command);
        $command = array_slice($command, -3, 3);

        $commandExpected = [
            '--empty-option',
            '--integer=123',
            "--string='word'",
        ];

        $this->assertEquals($commandExpected, $command);
    }
}
