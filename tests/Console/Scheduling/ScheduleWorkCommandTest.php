<?php

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Console\Scheduling\ScheduleWorkCommand;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ScheduleWorkCommandTest extends TestCase
{
    public function testScheduleRunProcessUsesApplicationBasePathAsWorkingDirectory()
    {
        $originalContainer = Container::getInstance();

        Container::setInstance(new class extends Container
        {
            public function basePath($path = '')
            {
                return '/test-base-path';
            }
        });

        try {
            $command = new class extends ScheduleWorkCommand
            {
                public function createProcess($command)
                {
                    return $this->createScheduleRunProcess($command);
                }
            };

            $process = $command->createProcess('php artisan schedule:run');

            $this->assertInstanceOf(Process::class, $process);
            $this->assertSame('/test-base-path', $process->getWorkingDirectory());
        } finally {
            Container::setInstance($originalContainer);
        }
    }
}
