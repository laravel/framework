<?php

use Illuminate\Console\Scheduling\Event;

class EventTest extends PHPUnit_Framework_TestCase
{
    public function testBuildCommand()
    {
        $event = new Event('php -i');

        $defaultOutput = (DIRECTORY_SEPARATOR == '\\') ? 'NUL' : '/dev/null';
        $this->assertSame("php -i > '{$defaultOutput}' 2>&1 &", $event->buildCommand());
    }

    public function testBuildCommandSendOutputTo()
    {
        $event = new Event('php -i');

        $event->sendOutputTo('/dev/null');
        $this->assertSame("php -i > '/dev/null' 2>&1 &", $event->buildCommand());

        $event = new Event('php -i');

        $event->sendOutputTo('/my folder/foo.log');
        $this->assertSame("php -i > '/my folder/foo.log' 2>&1 &", $event->buildCommand());
    }

    public function testBuildCommandAppendOutput()
    {
        $event = new Event('php -i');

        $event->appendOutputTo('/dev/null');
        $this->assertSame("php -i >> '/dev/null' 2>&1 &", $event->buildCommand());
    }

    /**
     * @expectedException LogicException
     */
    public function testEmailOutputToThrowsExceptionIfOutputFileWasNotSpecified()
    {
        $event = new Event('php -i');
        $event->emailOutputTo('foo@example.com');

        $event->buildCommand();
    }
}
