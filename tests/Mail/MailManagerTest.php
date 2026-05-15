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

        $this->expectExceptionObject(new InvalidArgumentException("Unsupported mail transport [{$transport}]"));
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

    public function testMailManagerCanResolveBackedEnumMailer(): void
    {
        $this->app['config']->set('mail.mailers.array', [
            'transport' => 'array',
        ]);

        $mailer1 = $this->app['mail.manager']->mailer(MailerName::ArrayMailer);
        $mailer2 = $this->app['mail.manager']->mailer('array');

        $this->assertSame($mailer1, $mailer2);
    }

    public function testMailManagerCanResolveBackedEnumDriver(): void
    {
        $this->app['config']->set('mail.mailers.array', [
            'transport' => 'array',
        ]);

        $mailer1 = $this->app['mail.manager']->driver(MailerName::ArrayMailer);
        $mailer2 = $this->app['mail.manager']->driver('array');

        $this->assertSame($mailer1, $mailer2);
    }

    public function testSetDefaultDriverAcceptsBackedEnum(): void
    {
        $this->app['config']->set('mail.mailers.array', [
            'transport' => 'array',
        ]);

        $this->app['mail.manager']->setDefaultDriver(MailerName::ArrayMailer);

        $this->assertSame('array', $this->app['config']->get('mail.default'));
    }

    public function testPurgeAcceptsBackedEnum(): void
    {
        $this->app['config']->set('mail.mailers.array', [
            'transport' => 'array',
        ]);

        $manager = $this->app['mail.manager'];

        $mailer1 = $manager->mailer(MailerName::ArrayMailer);
        $manager->purge(MailerName::ArrayMailer);
        $mailer2 = $manager->mailer(MailerName::ArrayMailer);

        $this->assertNotSame($mailer1, $mailer2);
    }

    public static function emptyTransportConfigDataProvider()
    {
        return [
            [null], [''], [' '],
        ];
    }
}

enum MailerName: string
{
    case ArrayMailer = 'array';
}
