<?php

namespace Illuminate\Tests\Mail;

use InvalidArgumentException;
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

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported mail transport [{$transport}]");
        $this->app['mail.manager']->mailer('custom_smtp');
    }

    public static function emptyTransportConfigDataProvider()
    {
        return [
            [null], [''], [' '],
        ];
    }
}
