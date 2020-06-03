<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Mail\MailManager;
use Orchestra\Testbench\TestCase;

class MailManagerTest extends TestCase
{
    /**
     * @dataProvider emptyTransportConfigDataProvider
     */
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

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported mail transport [{$transport}]");
        $this->app['mail.manager']->mailer('custom_smtp');
    }

    public function emptyTransportConfigDataProvider()
    {
        return [
            [null], [''], [' '],
        ];
    }

    public function testForgetMailer()
    {
        $this->app['config']->set('mail.mailers.custom_smtp', [
            'transport' => 'smtp',
            'host' => 'example.com',
            'port' => '25',
            'encryption' => 'tls',
            'username' => 'username',
            'password' => 'password',
            'timeout' => 10,
        ]);

        /** @var MailManager $mailManager */
        $mailManager = $this->app['mail.manager'];
        $mailManager->mailer('custom_smtp');

        $mailersProperty = new \ReflectionProperty($mailManager, 'mailers');
        $mailersProperty->setAccessible(true);

        $this->assertArrayHasKey('custom_smtp', $mailersProperty->getValue($mailManager), 'Mailer must exist in the $mailers-property');

        $mailManager->forgetMailer('custom_smtp');

        $this->assertArrayNotHasKey('custom_smtp', $mailersProperty->getValue($mailManager), 'Mailer must not exist in the $mailers-property as it must have been removed with MailManager::forgetMailer()');
    }
}
