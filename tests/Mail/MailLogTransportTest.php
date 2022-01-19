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
        $this->app['config']->set('mail.driver', 'log');

        $this->app['config']->set('mail.log_channel', 'mail');

        $this->app['config']->set('logging.channels.mail', [
            'driver' => 'single',
            'path' => 'mail.log',
        ]);

        $transport = app('mailer')->getSymfonyTransport();
        $this->assertInstanceOf(LogTransport::class, $transport);

        $logger = $transport->logger();
        $this->assertInstanceOf(LoggerInterface::class, $logger);

        $this->assertInstanceOf(Logger::class, $monolog = $logger->getLogger());
        $this->assertCount(1, $handlers = $monolog->getHandlers());
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);
    }

    public function testGetLogTransportWithPsrLogger()
    {
        $this->app['config']->set('mail.driver', 'log');

        $logger = $this->app->instance('log', new NullLogger);

        $transportLogger = app('mailer')->getSymfonyTransport()->logger();

        $this->assertEquals($logger, $transportLogger);
    }
}
