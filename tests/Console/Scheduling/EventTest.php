<?php

namespace {
    $mockFileGetContents = null;
}

namespace Illuminate\Console\Scheduling {
    use Mockery as m;
    use Illuminate\Console\Scheduling\Event;
    use Illuminate\Container\Container;

    function file_get_contents()
    {
        global $mockFileGetContents;
        if (isset($mockFileGetContents) && ! is_null($mockFileGetContents)) {
            return $mockFileGetContents;
        } else {
            return call_user_func_array('\file_get_contents', func_get_args());
        }
    }

    class EventTest extends \PHPUnit_Framework_TestCase
    {
        public function testBuildCommand()
        {
            $quote = (DIRECTORY_SEPARATOR == '\\') ? '"' : "'";

            $event = new Event('php -i');

            $defaultOutput = (DIRECTORY_SEPARATOR == '\\') ? 'NUL' : '/dev/null';
            $this->assertSame("php -i > {$quote}{$defaultOutput}{$quote} 2>&1 &", $event->buildCommand());
        }

        public function testBuildCommandSendOutputTo()
        {
            $quote = (DIRECTORY_SEPARATOR == '\\') ? '"' : "'";

            $event = new Event('php -i');

            $event->sendOutputTo('/dev/null');
            $this->assertSame("php -i > {$quote}/dev/null{$quote} 2>&1 &", $event->buildCommand());

            $event = new Event('php -i');

            $event->sendOutputTo('/my folder/foo.log');
            $this->assertSame("php -i > {$quote}/my folder/foo.log{$quote} 2>&1 &", $event->buildCommand());
        }

        public function testBuildCommandAppendOutput()
        {
            $quote = (DIRECTORY_SEPARATOR == '\\') ? '"' : "'";

            $event = new Event('php -i');

            $event->appendOutputTo('/dev/null');
            $this->assertSame("php -i >> {$quote}/dev/null{$quote} 2>&1 &", $event->buildCommand());
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

        public function testBuildEmailOutputToSubject()
        {
            global $mockFileGetContents;
            $mockFileGetContents = 'test output';
            $address = 'foo@example.com';
            $container = new Container;
            $resolveMailer = function ($subject = 'Scheduled Job Output') use ($address) {
                return function () use ($address, $subject) {
                    $mailer = m::mock('Illuminate\Mail\Mailer[sendSwiftMessage]', [m::mock('Illuminate\Contracts\View\Factory'), m::mock('Swift_Mailer')])->shouldAllowMockingProtectedMethods();
                    $mailer->shouldReceive('sendSwiftMessage')->with(m::on(function ($message) use ($address, $subject) {
                        $this->assertInstanceOf('Swift_Message', $message);
                        $this->assertSame([$address => null], $message->getTo());
                        $this->assertContains($subject, $message->getSubject());

                        return true;
                    }));

                    return $mailer;
                };
            };
            $event = new Event(m::mock('Illuminate\Contracts\Cache\Repository'), 'php -i');
            $container->bind('Illuminate\Contracts\Mail\Mailer', $resolveMailer());
            $event->sendOutputTo('/my folder/foo.log')->emailOutputTo($address)->run($container);
            $subject = 'custom subject';
            $container->bind('Illuminate\Contracts\Mail\Mailer', $resolveMailer($subject));
            $event->sendOutputTo('/my folder/foo.log')->emailSubject($subject)->emailOutputTo($address)->run($container);
        }
    }
}
