<?php

use Mockery as m;
use Illuminate\Console\Scheduling\Event;

class EventTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testBuildCommand()
    {
        $quote = (DIRECTORY_SEPARATOR == '\\') ? '"' : "'";

        $event = new Event('php -i', m::mock('Illuminate\Contracts\Cache\Repository'));

        $defaultOutput = (DIRECTORY_SEPARATOR == '\\') ? 'NUL' : '/dev/null';
        $this->assertSame("php -i > {$quote}{$defaultOutput}{$quote} 2>&1 &", $event->buildCommand());
    }

    public function testBuildCommandSendOutputTo()
    {
        $quote = (DIRECTORY_SEPARATOR == '\\') ? '"' : "'";

        $event = new Event('php -i', m::mock('Illuminate\Contracts\Cache\Repository'));

        $event->sendOutputTo('/dev/null');
        $this->assertSame("php -i > {$quote}/dev/null{$quote} 2>&1 &", $event->buildCommand());

        $event = new Event('php -i', m::mock('Illuminate\Contracts\Cache\Repository'));

        $event->sendOutputTo('/my folder/foo.log');
        $this->assertSame("php -i > {$quote}/my folder/foo.log{$quote} 2>&1 &", $event->buildCommand());
    }

    public function testBuildCommandAppendOutput()
    {
        $quote = (DIRECTORY_SEPARATOR == '\\') ? '"' : "'";

        $event = new Event('php -i', m::mock('Illuminate\Contracts\Cache\Repository'));

        $event->appendOutputTo('/dev/null');
        $this->assertSame("php -i >> {$quote}/dev/null{$quote} 2>&1 &", $event->buildCommand());
    }

    /**
     * @expectedException LogicException
     */
    public function testEmailOutputToThrowsExceptionIfOutputFileWasNotSpecified()
    {
        $event = new Event('php -i', m::mock('Illuminate\Contracts\Cache\Repository'));
        $event->emailOutputTo('foo@example.com');

        $event->buildCommand();
    }
}
