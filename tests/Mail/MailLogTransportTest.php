<?php

namespace Illuminate\Tests\Mail;

use Monolog\Logger;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Orchestra\Testbench\TestCase;
use Monolog\Handler\StreamHandler;
use Illuminate\Mail\Transport\LogTransport;

class MailLogTransportTest extends TestCase
{
    public function testGetLogTransportWithConfiguredChannel()
    {
        $this->app['config']->set('mail.mailers.log', [
            'transport' => 'log',
            'log_channel' => 'mail'
        ]);

        $this->app['config']->set('logging.channels.mail', [
            'driver' => 'single',
            'path' => 'mail.log',
        ]);

        $manager = $this->app['mailer'];

        $transport = $manager->driver('log')->getSwiftMailer()->getTransport();
        $this->assertInstanceOf(LogTransport::class, $transport);

        $logger = $this->readAttribute($transport, 'logger');
        $this->assertInstanceOf(LoggerInterface::class, $logger);

        $this->assertInstanceOf(Logger::class, $monolog = $logger->getLogger());
        $this->assertCount(1, $handlers = $monolog->getHandlers());
        $this->assertInstanceOf(StreamHandler::class, $handler = $handlers[0]);
    }

    public function testGetLogTransportWithPsrLogger()
    {
         $this->app['config']->set('mail.mailers.log', [
            'transport' => 'log',
            'log_channel' => 'mail'
        ]);

        $logger = $this->app->instance('log', new NullLogger());

        $manager = $this->app['mailer'];

        $transport = $manager->driver('log')->getSwiftMailer()->getTransport();

        $this->assertAttributeEquals($logger, 'logger', $transport);
    }
}
