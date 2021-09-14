<?php

namespace Illuminate\Tests\Mail;

use Orchestra\Testbench\TestCase;
use Symfony\Component\Mailer\Transport\FailoverTransport;

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

        $transport = app('mailer')->getSymfonyTransport();
        $this->assertInstanceOf(FailoverTransport::class, $transport);
    }

    public function testGetFailoverTransportWithLaravel6StyleMailConfiguration()
    {
        $this->app['config']->set('mail.driver', 'failover');

        $this->app['config']->set('mail.mailers', [
            'sendmail',
            'array',
        ]);

        $this->app['config']->set('mail.sendmail', '/usr/sbin/sendmail -bs');

        $transport = app('mailer')->getSymfonyTransport();
        $this->assertInstanceOf(FailoverTransport::class, $transport);
    }
}
