<?php

use Illuminate\Mail\TransportManager;
use Illuminate\Support\Collection;

class MailSendGridTransportTest extends PHPUnit_Framework_TestCase
{
    public function testGetTransport()
    {
        $app = [
            'config' => new Collection([
                'services.sendgrid' => [
                    'key' => 'foo',
                ],
            ]),
        ];

        $manager   = new TransportManager($app);
        $transport = $manager->driver('sendgrid');

        $key = $this->readAttribute($transport, 'key');

        $this->assertEquals('foo', $key);
    }
}