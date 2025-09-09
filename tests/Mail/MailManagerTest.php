<?php

namespace Illuminate\Tests\Mail;

use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class MailManagerTest extends TestCase
{
    #[DataProvider('emptyTransportConfigDataProvider')]
    public function testEmptyTransportConfig($transport): void
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

    #[TestWith([null, 5876])]
    #[TestWith([null, 465])]
    #[TestWith(['smtp', 25])]
    #[TestWith(['smtp', 2525])]
    #[TestWith(['smtps', 465])]
    #[TestWith(['smtp', 465])]
    public function testMailUrlConfig($scheme, $port): void
    {
        $this->app['config']->set('mail.mailers.smtp_url', [
            'scheme' => $scheme,
            'url' => "smtp://usr:pwd@127.0.0.2:{$port}",
        ]);

        $mailer = $this->app['mail.manager']->mailer('smtp_url');
        $transport = $mailer->getSymfonyTransport();

        $this->assertInstanceOf(EsmtpTransport::class, $transport);
        $this->assertSame('usr', $transport->getUsername());
        $this->assertSame('pwd', $transport->getPassword());
        $this->assertSame('127.0.0.2', $transport->getStream()->getHost());
        $this->assertSame($port, $transport->getStream()->getPort());
        $this->assertSame($port === 465, $transport->getStream()->isTLS());
        $this->assertTrue($transport->isAutoTls());
    }

    #[TestWith([null, 5876])]
    #[TestWith([null, 465])]
    #[TestWith(['smtp', 25])]
    #[TestWith(['smtp', 2525])]
    #[TestWith(['smtps', 465])]
    #[TestWith(['smtp', 465])]
    public function testMailUrlConfigWithAutoTls($scheme, $port): void
    {
        $this->app['config']->set('mail.mailers.smtp_url', [
            'scheme' => $scheme,
            'url' => "smtp://usr:pwd@127.0.0.2:{$port}?auto_tls=true",
        ]);

        $mailer = $this->app['mail.manager']->mailer('smtp_url');
        $transport = $mailer->getSymfonyTransport();

        $this->assertInstanceOf(EsmtpTransport::class, $transport);
        $this->assertSame('usr', $transport->getUsername());
        $this->assertSame('pwd', $transport->getPassword());
        $this->assertSame('127.0.0.2', $transport->getStream()->getHost());
        $this->assertSame($port, $transport->getStream()->getPort());
        $this->assertSame($port === 465, $transport->getStream()->isTLS());
        $this->assertTrue($transport->isAutoTls());
    }

    #[TestWith([null, 5876])]
    #[TestWith([null, 465])]
    #[TestWith(['smtp', 25])]
    #[TestWith(['smtp', 2525])]
    #[TestWith(['smtps', 465])]
    #[TestWith(['smtp', 465])]
    public function testMailUrlConfigWithAutoTlsDisabled($scheme, $port): void
    {
        $this->app['config']->set('mail.mailers.smtp_url', [
            'scheme' => $scheme,
            'url' => "smtp://usr:pwd@127.0.0.2:{$port}?auto_tls=false",
        ]);

        $mailer = $this->app['mail.manager']->mailer('smtp_url');
        $transport = $mailer->getSymfonyTransport();

        $this->assertInstanceOf(EsmtpTransport::class, $transport);
        $this->assertSame('usr', $transport->getUsername());
        $this->assertSame('pwd', $transport->getPassword());
        $this->assertSame('127.0.0.2', $transport->getStream()->getHost());
        $this->assertSame($port, $transport->getStream()->getPort());
        $this->assertFalse($transport->isAutoTls());
        $this->assertSame($port === 465 && $scheme !== 'smtp', $transport->getStream()->isTLS());
    }

    public function testBuild(): void
    {
        $config = [
            'transport' => 'smtp',
            'host' => '127.0.0.2',
            'port' => 5876,
            'encryption' => 'tls',
            'username' => 'usr',
            'password' => 'pwd',
            'timeout' => 5,
        ];

        $mailer = $this->app['mail.manager']->build($config);
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
