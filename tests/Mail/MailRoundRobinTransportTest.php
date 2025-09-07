<?php

namespace Illuminate\Tests\Mail;

use Orchestra\Testbench\TestCase;
use Symfony\Component\Mailer\Transport\RoundRobinTransport;

class MailRoundRobinTransportTest extends TestCase
{
    public function testGetRoundRobinTransportWithConfiguredTransports(): void
    {
        $this->app['config']->set('mail.default', 'roundrobin');

        $this->app['config']->set('mail.mailers', [
            'roundrobin' => [
                'transport' => 'roundrobin',
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
        $this->assertInstanceOf(RoundRobinTransport::class, $transport);
    }

    public function testGetRoundRobinTransportWithLaravel6StyleMailConfiguration(): void
    {
        $this->app['config']->set('mail.driver', 'roundrobin');

        $this->app['config']->set('mail.mailers', [
            'sendmail',
            'array',
        ]);

        $this->app['config']->set('mail.sendmail', '/usr/sbin/sendmail -bs');

        $transport = app('mailer')->getSymfonyTransport();
        $this->assertInstanceOf(RoundRobinTransport::class, $transport);
    }
}
