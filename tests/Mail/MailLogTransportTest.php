<?php

namespace Illuminate\Tests\Mail;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Orchestra\Testbench\TestCase;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Illuminate\Mail\Transport\LogTransport;

class MailLogTransportTest extends TestCase
{
    public function testGetLogTransportWithDefaultChannel()
    {
        $manager = $this->app['swift.transport'];

        $transport = $manager->driver('log');
        $this->assertInstanceOf(LogTransport::class, $transport);

        $logger = $this->readAttribute($transport, 'logger');
        $this->assertInstanceOf(LoggerInterface::class, $logger);

        $this->assertInstanceOf(Logger::class, $monolog = $logger->getLogger());
        $this->assertCount(1, $handlers = $monolog->getHandlers());
        $this->assertInstanceOf(RotatingFileHandler::class, $handlers[0]);
    }

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

        $logger = $this->readAttribute($transport, 'logger');
        $this->assertInstanceOf(LoggerInterface::class, $logger);

        $this->assertInstanceOf(Logger::class, $monolog = $logger->getLogger());
        $this->assertCount(1, $handlers = $monolog->getHandlers());
        $this->assertInstanceOf(StreamHandler::class, $handler = $handlers[0]);
        $this->assertEquals('mail.log', $handler->getUrl());
    }
}
