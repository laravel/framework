<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Mail\Transport\ArrayTransport;
use Orchestra\Testbench\TestCase;

class MailFailoverTransportTest extends TestCase
{
    public function testGetFailoverTransportWithConfiguredTransports()
    {
        $this->app['config']->set('mail.default', 'failover');

        $this->app['config']->set('mail.mailers', [
            'failover' => [
                'transport' => 'failover',
                'mailers' => [
                    'sendmail',
                    'array',
                ],
            ],

            'sendmail' => [
                'transport' => 'sendmail',
                'path' => '/usr/sbin/sendmail -bs',
            ],

            'array' => [
                'transport' => 'array',
            ],
        ]);

        $transport = app('mailer')->getSwiftMailer()->getTransport();
        $this->assertInstanceOf(\Swift_FailoverTransport::class, $transport);

        $transports = $transport->getTransports();
        $this->assertCount(2, $transports);
        $this->assertInstanceOf(\Swift_SendmailTransport::class, $transports[0]);
        $this->assertEquals('/usr/sbin/sendmail -bs', $transports[0]->getCommand());
        $this->assertInstanceOf(ArrayTransport::class, $transports[1]);
    }

    public function testGetFailoverTransportWithLaravel6StyleMailConfiguration()
    {
        $this->app['config']->set('mail.driver', 'failover');

        $this->app['config']->set('mail.mailers', [
            'sendmail',
            'array',
        ]);

        $this->app['config']->set('mail.sendmail', '/usr/sbin/sendmail -bs');

        $transport = app('mailer')->getSwiftMailer()->getTransport();
        $this->assertInstanceOf(\Swift_FailoverTransport::class, $transport);

        $transports = $transport->getTransports();
        $this->assertCount(2, $transports);
        $this->assertInstanceOf(\Swift_SendmailTransport::class, $transports[0]);
        $this->assertEquals('/usr/sbin/sendmail -bs', $transports[0]->getCommand());
        $this->assertInstanceOf(ArrayTransport::class, $transports[1]);
    }
}
