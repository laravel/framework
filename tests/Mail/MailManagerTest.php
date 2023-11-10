<?php

namespace Illuminate\Tests\Mail;

use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class MailManagerTest extends TestCase
{
    #[DataProvider('emptyTransportConfigDataProvider')]
    public function testEmptyTransportConfig($transport)
    {
        $this->app['config']->set('mail.mailers.custom_smtp', [
            'transport' => $transport,
            'host' => null,
            'port' => null,
            'encryption' => null,
            'username' => null,
            'password' => null,
            'timeout' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported mail transport [{$transport}]");
        $this->app['mail.manager']->mailer('custom_smtp');
    }

    public function testMailUrlConfig()
    {
        $this->app['config']->set('mail.mailers.smtp_url', [
            'url' => 'smtp://usr:pwd@127.0.0.2:5876',
        ]);

        $mailer = $this->app['mail.manager']->mailer('smtp_url');
        $transport = $mailer->getSymfonyTransport();

        $this->assertInstanceOf(EsmtpTransport::class, $transport);
        $this->assertSame('usr', $transport->getUsername());
        $this->assertSame('pwd', $transport->getPassword());
        $this->assertSame('127.0.0.2', $transport->getStream()->getHost());
        $this->assertSame(5876, $transport->getStream()->getPort());
    }

    public static function emptyTransportConfigDataProvider()
    {
        return [
            [null], [''], [' '],
        ];
    }
}
