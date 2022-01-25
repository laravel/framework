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
        $view = m::mock(Factory::class);
        $view->shouldReceive('make')->twice()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');
        $view->shouldReceive('render')->once()->andReturn('rendered.plain');

        $mailer = new Mailer('array', $view, new ArrayTransport);

        $sentMessage = $mailer->send(['foo', 'bar'], ['data'], function (Message $message) {
            $message->to('taylor@laravel.com')->from('hello@laravel.com');
        });

        $expected = <<<Text
        Content-Type: text/html; charset=utf-8\r
        Content-Transfer-Encoding: quoted-printable\r
        \r
        rendered.view
        Text;

        $this->assertStringContainsString($expected, $sentMessage->toString());

        $expected = <<<Text
        Content-Type: text/plain; charset=utf-8\r
        Content-Transfer-Encoding: quoted-printable\r
        \r
        rendered.plain
        Text;

        $this->assertStringContainsString($expected, $sentMessage->toString());
    }

    public function testMailerSendSendsMessageWithProperPlainViewContentWhenExplicit()
    {
        $view = m::mock(Factory::class);
        $view->shouldReceive('make')->twice()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');
        $view->shouldReceive('render')->once()->andReturn('rendered.plain');

        $mailer = new Mailer('array', $view, new ArrayTransport);

        $sentMessage = $mailer->send(['html' => 'foo', 'text' => 'bar'], ['data'], function (Message $message) {
            $message->to('taylor@laravel.com')->from('hello@laravel.com');
        });

        $expected = <<<Text
        Content-Type: text/html; charset=utf-8\r
        Content-Transfer-Encoding: quoted-printable\r
        \r
        rendered.view
        Text;

        $this->assertStringContainsString($expected, $sentMessage->toString());

        $expected = <<<Text
        Content-Type: text/plain; charset=utf-8\r
        Content-Transfer-Encoding: quoted-printable\r
        \r
        rendered.plain
        Text;

        $this->assertStringContainsString($expected, $sentMessage->toString());
    }

    public function testGlobalFromIsRespectedOnAllMessages()
    {
        $view = m::mock(Factory::class);
        $view->shouldReceive('make')->once()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');
        $mailer = new Mailer('array', $view, new ArrayTransport);
        $mailer->alwaysFrom('hello@laravel.com');

        $sentMessage = $mailer->send('foo', ['data'], function (Message $message) {
            $message->to('taylor@laravel.com');
        });

        $this->assertSame('taylor@laravel.com', $sentMessage->getEnvelope()->getRecipients()[0]->getAddress());
    }

    public function testGlobalReplyToIsRespectedOnAllMessages()
    {
        unset($_SERVER['__mailer.test']);
        $mailer = $this->getMailer();
        $view = m::mock(stdClass::class);
        $mailer->getViewFactory()->shouldReceive('make')->once()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');
        $this->setSwiftMailer($mailer);
        $mailer->alwaysReplyTo('taylorotwell@gmail.com', 'Taylor Otwell');
        $mailer->getSwiftMailer()->shouldReceive('send')->once()->with(m::type(Swift_Message::class), [])->andReturnUsing(function ($message) {
            $this->assertEquals(['taylorotwell@gmail.com' => 'Taylor Otwell'], $message->getReplyTo());
        });
        $mailer->send('foo', ['data'], function ($m) {
            //
        });
    }

    public function testGlobalToIsRespectedOnAllMessages()
    {
        unset($_SERVER['__mailer.test']);
        $mailer = $this->getMailer();
        $view = m::mock(stdClass::class);
        $mailer->getViewFactory()->shouldReceive('make')->once()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');
        $this->setSwiftMailer($mailer);
        $mailer->alwaysTo('taylorotwell@gmail.com', 'Taylor Otwell');
        $mailer->getSwiftMailer()->shouldReceive('send')->once()->with(m::type(Swift_Message::class), [])->andReturnUsing(function ($message) {
            $this->assertEquals(['taylorotwell@gmail.com' => 'Taylor Otwell'], $message->getTo());
        });
        $mailer->send('foo', ['data'], function ($m) {
            //
        });
    }

    public function testGlobalReturnPathIsRespectedOnAllMessages()
    {
        $view = m::mock(Factory::class);
        $view->shouldReceive('make')->once()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');

        $mailer = new Mailer('array', $view, new ArrayTransport);
        $mailer->alwaysReturnPath('taylorotwell@gmail.com');

        $sentMessage = $mailer->send('foo', ['data'], function (Message $message) {
            $message->to('taylor@laravel.com')->from('hello@laravel.com');
        });

        $this->assertStringContainsString('Return-Path: <taylorotwell@gmail.com>', $sentMessage->toString());
    }

    public function testEventsAreDispatched()
    {
        $view = m::mock(Factory::class);
        $view->shouldReceive('make')->once()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');

        $events = m::mock(Dispatcher::class);
        $events->shouldReceive('until')->once()->with(m::type(MessageSending::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(MessageSent::class));

        $mailer = new Mailer('array', $view, new ArrayTransport, $events);

        $mailer->send('foo', ['data'], function (Message $message) {
            $message->to('taylor@laravel.com')->from('hello@laravel.com');
        });
    }

    public function testMacroable()
    {
        Mailer::macro('foo', function () {
            return 'bar';
        });

        $mailer = new Mailer('array', m::mock(Factory::class), new ArrayTransport);

        $this->assertSame(
            'bar', $mailer->foo()
        );
    }
}
