<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Mail\Transport\LogTransport;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Orchestra\Testbench\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class MailLogTransportTest extends TestCase
{
    public function testGetLogTransportWithConfiguredChannel()
    {
        $this->app['config']->set('mail.log_channel', 'mail');
        $this->app['config']->set('logging.channels.mail', [
            'driver' => 'single',
            'path' => 'mail.log',
        ]);

        $manager = $this->app['swift.transport'];

        $transport = $manager->driver('log');
        $this->assertInstanceOf(LogTransport::class, $transport);

        $logger = $transport->logger();
        $this->assertInstanceOf(LoggerInterface::class, $logger);

        $this->assertInstanceOf(Logger::class, $monolog = $logger->getLogger());
        $this->assertCount(1, $handlers = $monolog->getHandlers());
        $this->assertInstanceOf(StreamHandler::class, $handler = $handlers[0]);
    }

    public function testGetLogTransportWithPsrLogger()
    {
        $logger = $this->app->instance('log', new NullLogger());

        $manager = $this->app['swift.transport'];

        $this->assertEquals($logger, $manager->driver('log')->logger());
    }
}
