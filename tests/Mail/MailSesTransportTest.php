<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Mail\MailManager;
use PHPUnit\Framework\TestCase;

class MailSesTransportTest extends TestCase
{
    public function testGetTransport()
    {
        $container = new Container;

        $container->singleton('config', function () {
            return new Repository([
                'services.ses' => [
                    'region' => 'us-east-1',
                ],
            ]);
        });

        $manager = new MailManager($container);

        $transport = $manager->createSymfonyTransport(['transport' => 'ses']);

        $this->assertSame('ses+api://random_key@us-east-1', $transport->__toString());
    }
}
