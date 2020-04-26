<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Mail\MailManager;
use Orchestra\Testbench\TestCase;

class MailManagerTest extends TestCase
{

    /**
     * @dataProvider emptyTransportConfigDataProvider
     * @covers \Illuminate\Mail\MailManager::createTransport
     */
    public function testEmptyTransportConfig(?string $transport)
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

        /** @var MailManager $mailManager */
        $mailManager = app('mail.manager');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Empty value for \"transport\"-key in \"mail.mailers\"-config found. Please check that every mailer in your config/mail.php has a \"transport\"-key.");
        $mailManager->mailer("custom_smtp");
    }

    public function emptyTransportConfigDataProvider() : array
    {
        return [
          [null], [""], [" "]
        ];
    }
}
