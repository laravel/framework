<?php

namespace Illuminate\Tests\Integration\Mail;

use Orchestra\Testbench\TestCase;
use ReflectionProperty;
use Symfony\Component\Mailer\Transport\FailoverTransport;
use Symfony\Component\Mailer\Transport\RoundRobinTransport;

class MailFailoverTransportTest extends TestCase
{
    public function testGetFailoverTransportWithConfiguredTransports(): void
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

    public function testGetFailoverTransportWithConfiguredRetryPeriod(): void
    {
        $this->app['config']->set('mail.default', 'failover');

        $this->app['config']->set('mail.mailers', [
            'failover' => [
                'transport' => 'failover',
                'mailers' => [
                    'sendmail',
                    'array',
                ],
                'retry_after' => 5,
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
        $this->assertSame(5, (new ReflectionProperty(RoundRobinTransport::class, 'retryPeriod'))->getValue($transport));
    }

    public function testGetFailoverTransportWithLaravel6StyleMailConfiguration(): void
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
