<?php

namespace Illuminate\Tests\Mail;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Collection;
use Illuminate\Mail\TransportFactory;

class MailManagerTest extends TestCase
{
    protected $app;
    protected $transport;

    public function setUp()
    {
        $this->app = m::mock('Illuminate\Foundation\Application');
        $this->app->shouldReceive('make');
        $this->app->shouldReceive('offsetGet')->withArgs(['config'])->andReturn(
            new Collection([
                'mail.default' => 'second',
                'mail.connections' => [
                    'first' => [
                        'driver'  => 'ses',
                        'service' => 'ses',
                    ],
                    'second' => [
                        'driver' => 'smtp',
                        'host'   => 'smtp.example.org',
                    ],
                    'third' => [
                        'driver'  => 'ses',
                        'service' => 'ses2',
                    ],
                    'fourth' => [],
                ],
                'services.ses' => [
                    'key' => 'foo',
                ],
                'services.ses2' => [
                    'key' => 'bar',
                ],
            ])
        );

        $this->transport = m::mock('Illuminate\Mail\TransportFactory');
    }

    public function tearDown()
    {
        parent::tearDown();

        m::close();
    }

    public function testIfRequestedConnectionWillBeResolved()
    {
        $mail = $this->getMockBuilder('Illuminate\Mail\MailManager')
            ->setConstructorArgs([$this->app, $this->transport])
            ->setMethods(['makeConnection'])
            ->getMock();

        $mail->expects($this->once())->method('makeConnection')->with('first');
        $mail->connection('first');
    }

    public function testIfDefaultConnectionWillBeResolvedWhenUnspecified()
    {
        $mail = $this->getMockBuilder('Illuminate\Mail\MailManager')
            ->setConstructorArgs([$this->app, $this->transport])
            ->setMethods(['makeConnection'])
            ->getMock();

        $mail->expects($this->once())->method('makeConnection')->with('second');
        $mail->connection();
    }

    public function testIfConfigurationIsRetrieved()
    {
        $mail = $this->getMockBuilder('Illuminate\Mail\MailManager')
            ->setConstructorArgs([$this->app, $this->transport])
            ->setMethods(['makeTransport'])
            ->getMock();

        $mail->expects($this->once())->method('makeTransport')->with('smtp', [
            'driver' => 'smtp',
            'host'   => 'smtp.example.org',
        ]);
        $mail->connection('second');
    }

    public function testIfServiceConfigurationIsMerged()
    {
        $mail = $this->getMockBuilder('Illuminate\Mail\MailManager')
            ->setConstructorArgs([$this->app, $this->transport])
            ->setMethods(['makeTransport'])
            ->getMock();

        $mail->expects($this->once())->method('makeTransport')->with('ses', [
            'driver'  => 'ses',
            'service' => [
                'key' => 'foo',
            ],
        ]);
        $mail->connection('first');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Mail connection [fifth] not configured.
     */
    public function testIfConfigurationIsntSetExceptionIsThrown()
    {
        $mail = new MailManager($this->app, $this->transport);
        $mail->connection('fifth');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage A driver must be specified.
     */
    public function testIfDriverIsntSetExceptionIsThrown()
    {
        $mail = new MailManager($this->app, $this->transport);
        $mail->connection('fourth');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unsupported driver [foo]
     */
    public function testExceptionIsThrownOnUnsupportedDriver()
    {
        $factory = new TransportFactory([]);
        $factory->create('foo', []);
    }
}
