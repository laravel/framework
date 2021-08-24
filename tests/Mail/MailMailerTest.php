<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Message;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\HtmlString;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;
use Swift_Message;
use Swift_Mime_SimpleMessage;
use Swift_Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

class MailMailerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['__mailer.test']);

        m::close();
    }

    public function testMailerSendSendsMessageWithProperViewContent()
    {
        $view = m::mock(Factory::class);
        $view->shouldReceive('make')->once()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');

        $mailer = new Mailer('array', $view, new ArrayTransport);

        $sentMessage = $mailer->send('foo', ['data'], function (Message $message) {
            $message->to('taylor@laravel.com')->from('hello@laravel.com');
        });

        $this->assertStringContainsString('rendered.view', $sentMessage->toString());
    }

    public function testMailerSendSendsMessageWithProperViewContentUsingHtmlStrings()
    {
        $view = m::mock(Factory::class);
        $view->shouldReceive('render')->never();

        $mailer = new Mailer('array', $view, new ArrayTransport);

        $sentMessage = $mailer->send(
            ['html' => new HtmlString('<p>Hello Laravel</p>'), 'text' => new HtmlString('Hello World')],
            ['data'],
            function (Message $message) {
                $message->to('taylor@laravel.com')->from('hello@laravel.com');
            }
        );

        $this->assertStringContainsString('<p>Hello Laravel</p>', $sentMessage->toString());
        $this->assertStringContainsString('Hello World', $sentMessage->toString());
    }

    public function testMailerSendSendsMessageWithProperViewContentUsingHtmlMethod()
    {
        $view = m::mock(Factory::class);
        $view->shouldReceive('render')->never();

        $mailer = new Mailer('array', $view, new ArrayTransport);

        $sentMessage = $mailer->html('<p>Hello World</p>', function (Message $message) {
            $message->to('taylor@laravel.com')->from('hello@laravel.com');
        });

        $this->assertStringContainsString('<p>Hello World</p>', $sentMessage->toString());
    }

    public function testMailerSendSendsMessageWithProperPlainViewContent()
    {
        unset($_SERVER['__mailer.test']);
        $mailer = $this->getMockBuilder(Mailer::class)->onlyMethods(['createMessage'])->setConstructorArgs($this->getMocks())->getMock();
        $message = m::mock(Swift_Mime_SimpleMessage::class);
        $mailer->expects($this->once())->method('createMessage')->willReturn($message);
        $view = m::mock(stdClass::class);
        $mailer->getViewFactory()->shouldReceive('make')->once()->with('foo', ['data', 'message' => $message])->andReturn($view);
        $mailer->getViewFactory()->shouldReceive('make')->once()->with('bar', ['data', 'message' => $message])->andReturn($view);
        $view->shouldReceive('render')->twice()->andReturn('rendered.view');
        $message->shouldReceive('setBody')->once()->with('rendered.view', 'text/html');
        $message->shouldReceive('addPart')->once()->with('rendered.view', 'text/plain');
        $message->shouldReceive('setFrom')->never();
        $this->setSwiftMailer($mailer);
        $message->shouldReceive('getSwiftMessage')->once()->andReturn($message);
        $mailer->getSwiftMailer()->shouldReceive('send')->once()->with($message, []);
        $mailer->send(['foo', 'bar'], ['data'], function ($m) {
            $_SERVER['__mailer.test'] = $m;
        });
    }

    public function testMailerSendSendsMessageWithProperPlainViewContentWhenExplicit()
    {
        $mailer = $this->getMockBuilder(Mailer::class)->onlyMethods(['createMessage'])->setConstructorArgs($this->getMocks())->getMock();
        $message = m::mock(Swift_Mime_SimpleMessage::class);
        $mailer->expects($this->once())->method('createMessage')->willReturn($message);
        $view = m::mock(stdClass::class);
        $mailer->getViewFactory()->shouldReceive('make')->once()->with('foo', ['data', 'message' => $message])->andReturn($view);
        $mailer->getViewFactory()->shouldReceive('make')->once()->with('bar', ['data', 'message' => $message])->andReturn($view);
        $view->shouldReceive('render')->twice()->andReturn('rendered.view');
        $message->shouldReceive('setBody')->once()->with('rendered.view', 'text/html');
        $message->shouldReceive('addPart')->once()->with('rendered.view', 'text/plain');
        $message->shouldReceive('setFrom')->never();
        $this->setSwiftMailer($mailer);
        $message->shouldReceive('getSwiftMessage')->once()->andReturn($message);
        $mailer->getSwiftMailer()->shouldReceive('send')->once()->with($message, []);
        $mailer->send(['html' => 'foo', 'text' => 'bar'], ['data'], function ($m) {
            $_SERVER['__mailer.test'] = $m;
        });
    }

    public function testGlobalFromIsRespectedOnAllMessages()
    {
        $mailer = $this->getMailer();
        $view = m::mock(stdClass::class);
        $mailer->getViewFactory()->shouldReceive('make')->once()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');
        $this->setSwiftMailer($mailer);
        $mailer->alwaysFrom('taylorotwell@gmail.com', 'Taylor Otwell');
        $mailer->getSwiftMailer()->shouldReceive('send')->once()->with(m::type(Swift_Message::class), [])->andReturnUsing(function ($message) {
            $this->assertEquals(['taylorotwell@gmail.com' => 'Taylor Otwell'], $message->getFrom());
        });
        $mailer->send('foo', ['data'], function ($m) {
            //
        });
    }

    public function testGlobalReturnPathIsRespectedOnAllMessages()
    {
        $mailer = $this->getMailer();
        $view = m::mock(stdClass::class);
        $mailer->getViewFactory()->shouldReceive('make')->once()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');
        $this->setSwiftMailer($mailer);
        $mailer->alwaysReturnPath('taylorotwell@gmail.com');
        $mailer->getSwiftMailer()->shouldReceive('send')->once()->with(m::type(Swift_Message::class), [])->andReturnUsing(function ($message) {
            $this->assertSame('taylorotwell@gmail.com', $message->getReturnPath());
        });
        $mailer->send('foo', ['data'], function ($m) {
            //
        });
    }

    public function testEventsAreDispatched()
    {
        $events = m::mock(Dispatcher::class);
        $events->shouldReceive('until')->once()->with(m::type(MessageSending::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(MessageSent::class));
        $mailer = $this->getMailer($events);
        $view = m::mock(stdClass::class);
        $mailer->getViewFactory()->shouldReceive('make')->once()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');
        $this->setSwiftMailer($mailer);
        $mailer->getSwiftMailer()->shouldReceive('send')->once()->with(m::type(Swift_Message::class), []);
        $mailer->send('foo', ['data'], function ($m) {
            //
        });
    }

    public function testMacroable()
    {
        Mailer::macro('foo', function () {
            return 'bar';
        });

        $mailer = $this->getMailer();

        $this->assertSame(
            'bar', $mailer->foo()
        );
    }

    protected function getMailer($events = null)
    {
        return new Mailer('smtp', m::mock(Factory::class), m::mock(TransportInterface::class), $events);
    }

    public function setSwiftMailer($mailer)
    {
        $transport = m::mock(TransportInterface::class);
        $transport->shouldReceive('createMessage')->andReturn(new Message(new Email()));
        $transport->shouldReceive('getTransport')->andReturn($transport = m::mock(Swift_Transport::class));
        $transport->shouldReceive('stop');
        $mailer->setSymfonyTransport($transport);

        return $mailer;
    }

    protected function getMocks()
    {
        return ['smtp', m::mock(Factory::class), m::mock(TransportInterface::class)];
    }
}
