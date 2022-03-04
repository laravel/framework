<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\Mailer;
use Illuminate\Support\HtmlString;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;
use Swift_Mailer;
use Swift_Message;
use Swift_Mime_SimpleMessage;
use Swift_Transport;

class MailMailerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testMailerSendSendsMessageWithProperViewContent()
    {
        unset($_SERVER['__mailer.test']);
        $mailer = $this->getMockBuilder(Mailer::class)->onlyMethods(['createMessage'])->setConstructorArgs($this->getMocks())->getMock();
        $message = m::mock(Swift_Mime_SimpleMessage::class);
        $mailer->expects($this->once())->method('createMessage')->willReturn($message);
        $view = m::mock(stdClass::class);
        $mailer->getViewFactory()->shouldReceive('make')->once()->with('foo', ['data', 'message' => $message])->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');
        $message->shouldReceive('setBody')->once()->with('rendered.view', 'text/html');
        $message->shouldReceive('setFrom')->never();
        $this->setSwiftMailer($mailer);
        $message->shouldReceive('getSwiftMessage')->once()->andReturn($message);
        $mailer->getSwiftMailer()->shouldReceive('send')->once()->with($message, []);
        $mailer->send('foo', ['data'], function ($m) {
            $_SERVER['__mailer.test'] = $m;
        });
        unset($_SERVER['__mailer.test']);
    }

    public function testMailerSendSendsMessageWithProperViewContentUsingHtmlStrings()
    {
        unset($_SERVER['__mailer.test']);
        $mailer = $this->getMockBuilder(Mailer::class)->onlyMethods(['createMessage'])->setConstructorArgs($this->getMocks())->getMock();
        $message = m::mock(Swift_Mime_SimpleMessage::class);
        $mailer->expects($this->once())->method('createMessage')->willReturn($message);
        $view = m::mock(stdClass::class);
        $mailer->getViewFactory()->shouldReceive('make')->never();
        $view->shouldReceive('render')->never();
        $message->shouldReceive('setBody')->once()->with('rendered.view', 'text/html');
        $message->shouldReceive('addPart')->once()->with('rendered.text', 'text/plain');
        $message->shouldReceive('setFrom')->never();
        $this->setSwiftMailer($mailer);
        $message->shouldReceive('getSwiftMessage')->once()->andReturn($message);
        $mailer->getSwiftMailer()->shouldReceive('send')->once()->with($message, []);
        $mailer->send(['html' => new HtmlString('rendered.view'), 'text' => new HtmlString('rendered.text')], ['data'], function ($m) {
            $_SERVER['__mailer.test'] = $m;
        });
        unset($_SERVER['__mailer.test']);
    }

    public function testMailerSendSendsMessageWithProperViewContentUsingHtmlMethod()
    {
        unset($_SERVER['__mailer.test']);
        $mailer = $this->getMockBuilder(Mailer::class)->onlyMethods(['createMessage'])->setConstructorArgs($this->getMocks())->getMock();
        $message = m::mock(Swift_Mime_SimpleMessage::class);
        $mailer->expects($this->once())->method('createMessage')->willReturn($message);
        $view = m::mock(stdClass::class);
        $mailer->getViewFactory()->shouldReceive('make')->never();
        $view->shouldReceive('render')->never();
        $message->shouldReceive('setBody')->once()->with('rendered.view', 'text/html');
        $message->shouldReceive('setFrom')->never();
        $this->setSwiftMailer($mailer);
        $message->shouldReceive('getSwiftMessage')->once()->andReturn($message);
        $mailer->getSwiftMailer()->shouldReceive('send')->once()->with($message, []);
        $mailer->html('rendered.view', function ($m) {
            $_SERVER['__mailer.test'] = $m;
        });
        unset($_SERVER['__mailer.test']);
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
        unset($_SERVER['__mailer.test']);
    }

    public function testMailerSendSendsMessageWithProperPlainViewContentWhenExplicit()
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
        $mailer->send(['html' => 'foo', 'text' => 'bar'], ['data'], function ($m) {
            $_SERVER['__mailer.test'] = $m;
        });
        unset($_SERVER['__mailer.test']);
    }

    public function testGlobalFromIsRespectedOnAllMessages()
    {
        unset($_SERVER['__mailer.test']);
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
        unset($_SERVER['__mailer.test']);
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

    public function testFailedRecipientsAreAppendedAndCanBeRetrieved()
    {
        unset($_SERVER['__mailer.test']);
        $mailer = $this->getMailer();
        $mailer->getSwiftMailer()->shouldReceive('getTransport')->andReturn($transport = m::mock(Swift_Transport::class));
        $transport->shouldReceive('stop');
        $view = m::mock(stdClass::class);
        $mailer->getViewFactory()->shouldReceive('make')->once()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');
        $swift = new FailingSwiftMailerStub;
        $mailer->setSwiftMailer($swift);

        $mailer->send('foo', ['data'], function ($m) {
            //
        });

        $this->assertEquals(['taylorotwell@gmail.com'], $mailer->failures());
    }

    public function testEventsAreDispatched()
    {
        unset($_SERVER['__mailer.test']);
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
        return new Mailer('smtp', m::mock(Factory::class), m::mock(Swift_Mailer::class), $events);
    }

    public function setSwiftMailer($mailer)
    {
        $swift = m::mock(Swift_Mailer::class);
        $swift->shouldReceive('createMessage')->andReturn(new Swift_Message);
        $swift->shouldReceive('getTransport')->andReturn($transport = m::mock(Swift_Transport::class));
        $transport->shouldReceive('stop');
        $mailer->setSwiftMailer($swift);

        return $mailer;
    }

    protected function getMocks()
    {
        return ['smtp', m::mock(Factory::class), m::mock(Swift_Mailer::class)];
    }
}

class FailingSwiftMailerStub
{
    public function send($message, &$failed)
    {
        $failed[] = 'taylorotwell@gmail.com';
    }

    public function getTransport()
    {
        $transport = m::mock(Swift_Transport::class);
        $transport->shouldReceive('stop');

        return $transport;
    }

    public function createMessage()
    {
        return new Swift_Message;
    }
}
