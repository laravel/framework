<?php

namespace Illuminate\Tests\Mail;

use stdClass;
use Mockery as m;
use Swift_Mailer;
use Swift_Transport;
use Illuminate\Mail\Mailer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\View\Factory;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Mail\Transport\ArrayTransport;

class MailMailerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testMailerSendSendsMessageWithProperViewContent()
    {
        unset($_SERVER['__mailer.test']);
        $mailer = $this->getMockBuilder(Mailer::class)->setMethods(['createMessage'])->setConstructorArgs($this->getMocks())->getMock();
        $message = m::mock(Email::class);
        $mailer->expects($this->once())->method('createMessage')->will($this->returnValue($message));
        $view = m::mock(stdClass::class);
        $mailer->getViewFactory()->shouldReceive('make')->once()->with('foo', ['data', 'message' => $message])->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');
        $message->shouldReceive('html')->once()->with('rendered.view');
        $message->shouldReceive('from')->never();
        $message->shouldReceive('getSymfonyEmail')->once()->andReturn($message);
        $mailer->getTransport()->shouldReceive('send')->once()->with($message, []);
        $mailer->send('foo', ['data'], function ($m) {
            $_SERVER['__mailer.test'] = $m;
        });
        unset($_SERVER['__mailer.test']);
    }

    public function testMailerSendSendsMessageWithProperViewContentUsingHtmlStrings()
    {
        unset($_SERVER['__mailer.test']);
        $mailer = $this->getMockBuilder(Mailer::class)->setMethods(['createMessage'])->setConstructorArgs($this->getMocks())->getMock();
        $message = m::mock(Email::class);
        $mailer->expects($this->once())->method('createMessage')->will($this->returnValue($message));
        $view = m::mock(stdClass::class);
        $mailer->getViewFactory()->shouldReceive('make')->never();
        $view->shouldReceive('render')->never();
        $message->shouldReceive('html')->once()->with('rendered.view');
        $message->shouldReceive('text')->once()->with('rendered.text');
        $message->shouldReceive('from')->never();
        $message->shouldReceive('getSymfonyEmail')->once()->andReturn($message);
        $mailer->getTransport()->shouldReceive('send')->once()->with($message, []);
        $mailer->send(['html' => new HtmlString('rendered.view'), 'text' => new HtmlString('rendered.text')], ['data'], function ($m) {
            $_SERVER['__mailer.test'] = $m;
        });
        unset($_SERVER['__mailer.test']);
    }

    public function testMailerSendSendsMessageWithProperViewContentUsingHtmlMethod()
    {
        unset($_SERVER['__mailer.test']);
        $mailer = $this->getMockBuilder(Mailer::class)->setMethods(['createMessage'])->setConstructorArgs($this->getMocks())->getMock();
        $message = m::mock(Email::class);
        $mailer->expects($this->once())->method('createMessage')->will($this->returnValue($message));
        $view = m::mock(stdClass::class);
        $mailer->getViewFactory()->shouldReceive('make')->never();
        $view->shouldReceive('render')->never();
        $message->shouldReceive('html')->once()->with('rendered.view');
        $message->shouldReceive('from')->never();
        $message->shouldReceive('getSymfonyEmail')->once()->andReturn($message);
        $mailer->getTransport()->shouldReceive('send')->once()->with($message, []);
        $mailer->html('rendered.view', function ($m) {
            $_SERVER['__mailer.test'] = $m;
        });
        unset($_SERVER['__mailer.test']);
    }

    public function testMailerSendSendsMessageWithProperPlainViewContent()
    {
        unset($_SERVER['__mailer.test']);
        $mailer = $this->getMockBuilder(Mailer::class)->setMethods(['createMessage'])->setConstructorArgs($this->getMocks())->getMock();
        $message = m::mock(Email::class);
        $mailer->expects($this->once())->method('createMessage')->will($this->returnValue($message));
        $view = m::mock(stdClass::class);
        $mailer->getViewFactory()->shouldReceive('make')->once()->with('foo', ['data', 'message' => $message])->andReturn($view);
        $mailer->getViewFactory()->shouldReceive('make')->once()->with('bar', ['data', 'message' => $message])->andReturn($view);
        $view->shouldReceive('render')->twice()->andReturn('rendered.view');
        $message->shouldReceive('html')->once()->with('rendered.view');
        $message->shouldReceive('text')->once()->with('rendered.view');
        $message->shouldReceive('setFrom')->never();
        $message->shouldReceive('getSymfonyEmail')->once()->andReturn($message);
        $mailer->getTransport()->shouldReceive('send')->once()->with($message, []);
        $mailer->send(['foo', 'bar'], ['data'], function ($m) {
            $_SERVER['__mailer.test'] = $m;
        });
        unset($_SERVER['__mailer.test']);
    }

    public function testMailerSendSendsMessageWithProperPlainViewContentWhenExplicit()
    {
        unset($_SERVER['__mailer.test']);
        $mailer = $this->getMockBuilder(Mailer::class)->setMethods(['createMessage'])->setConstructorArgs($this->getMocks())->getMock();
        $message = m::mock(Email::class);
        $mailer->expects($this->once())->method('createMessage')->will($this->returnValue($message));
        $view = m::mock(stdClass::class);
        $mailer->getViewFactory()->shouldReceive('make')->once()->with('foo', ['data', 'message' => $message])->andReturn($view);
        $mailer->getViewFactory()->shouldReceive('make')->once()->with('bar', ['data', 'message' => $message])->andReturn($view);
        $view->shouldReceive('render')->twice()->andReturn('rendered.view');
        $message->shouldReceive('html')->once()->with('rendered.view');
        $message->shouldReceive('text')->once()->with('rendered.view');
        $message->shouldReceive('setFrom')->never();
        $message->shouldReceive('getSymfonyEmail')->once()->andReturn($message);
        $mailer->getTransport()->shouldReceive('send')->once()->with($message, []);
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
        $mailer->alwaysFrom('taylorotwell@gmail.com', 'Taylor Otwell');
        $mailer->getTransport()->shouldReceive('send')->once()->with(m::type(Email::class), [])->andReturnUsing(function ($message) {
            $this->assertEquals('Taylor Otwell <taylorotwell@gmail.com>', $message->getFrom()[0]->toString());
        });
        $mailer->send('foo', ['data'], function ($m) {
            //
        });
    }

    public function testFailedRecipientsAreAppendedAndCanBeRetrieved()
    {
        $this->markTestIncomplete();
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
        $mailer->getTransport()->shouldReceive('send')->once()->with(m::type(Email::class), []);
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

        $this->assertEquals(
            'bar', $mailer->foo()
        );
    }

    protected function getMailer($events = null)
    {
        return new Mailer(m::mock(Factory::class), m::mock(ArrayTransport::class), $events);
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
        return [m::mock(Factory::class), m::mock(ArrayTransport::class)];
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
