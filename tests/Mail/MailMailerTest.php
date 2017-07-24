<?php

namespace Illuminate\Tests\Mail;

use Mockery as m;
use Illuminate\Mail\Mailer;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\HtmlString;

class MailMailerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testMailerSendSendsMessageWithProperViewContent()
    {
        unset($_SERVER['__mailer.test']);
        $mailer = $this->getMockBuilder('Illuminate\Mail\Mailer')->setMethods(['createMessage'])->setConstructorArgs($this->getMocks())->getMock();
        $message = m::mock('Swift_Mime_Message');
        $mailer->expects($this->once())->method('createMessage')->will($this->returnValue($message));
        $view = m::mock('StdClass');
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
        $mailer = $this->getMockBuilder('Illuminate\Mail\Mailer')->setMethods(['createMessage'])->setConstructorArgs($this->getMocks())->getMock();
        $message = m::mock('Swift_Mime_Message');
        $mailer->expects($this->once())->method('createMessage')->will($this->returnValue($message));
        $view = m::mock('StdClass');
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

    public function testMailerSendSendsMessageWithProperPlainViewContent()
    {
        unset($_SERVER['__mailer.test']);
        $mailer = $this->getMockBuilder('Illuminate\Mail\Mailer')->setMethods(['createMessage'])->setConstructorArgs($this->getMocks())->getMock();
        $message = m::mock('Swift_Mime_Message');
        $mailer->expects($this->once())->method('createMessage')->will($this->returnValue($message));
        $view = m::mock('StdClass');
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
        $mailer = $this->getMockBuilder('Illuminate\Mail\Mailer')->setMethods(['createMessage'])->setConstructorArgs($this->getMocks())->getMock();
        $message = m::mock('Swift_Mime_Message');
        $mailer->expects($this->once())->method('createMessage')->will($this->returnValue($message));
        $view = m::mock('StdClass');
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
        $view = m::mock('StdClass');
        $mailer->getViewFactory()->shouldReceive('make')->once()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');
        $this->setSwiftMailer($mailer);
        $mailer->alwaysFrom('taylorotwell@gmail.com', 'Taylor Otwell');
        $mailer->getSwiftMailer()->shouldReceive('send')->once()->with(m::type('Swift_Message'), [])->andReturnUsing(function ($message) {
            $this->assertEquals(['taylorotwell@gmail.com' => 'Taylor Otwell'], $message->getFrom());
        });
        $mailer->send('foo', ['data'], function ($m) {
        });
    }

    public function testFailedRecipientsAreAppendedAndCanBeRetrieved()
    {
        unset($_SERVER['__mailer.test']);
        $mailer = $this->getMailer();
        $mailer->getSwiftMailer()->shouldReceive('getTransport')->andReturn($transport = m::mock('Swift_Transport'));
        $transport->shouldReceive('stop');
        $view = m::mock('StdClass');
        $mailer->getViewFactory()->shouldReceive('make')->once()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');
        $swift = new FailingSwiftMailerStub;
        $mailer->setSwiftMailer($swift);

        $mailer->send('foo', ['data'], function ($m) {
        });

        $this->assertEquals(['taylorotwell@gmail.com'], $mailer->failures());
    }

    public function testEventsAreDispatched()
    {
        unset($_SERVER['__mailer.test']);
        $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('until')->once()->with(m::type('Illuminate\Mail\Events\MessageSending'));
        $events->shouldReceive('dispatch')->once()->with(m::type('Illuminate\Mail\Events\MessageSent'));
        $mailer = $this->getMailer($events);
        $view = m::mock('StdClass');
        $mailer->getViewFactory()->shouldReceive('make')->once()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');
        $this->setSwiftMailer($mailer);
        $mailer->getSwiftMailer()->shouldReceive('send')->once()->with(m::type('Swift_Message'), []);
        $mailer->send('foo', ['data'], function ($m) {
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
        return new Mailer(m::mock('Illuminate\Contracts\View\Factory'), m::mock('Swift_Mailer'), $events);
    }

    public function setSwiftMailer($mailer)
    {
        $swift = m::mock('Swift_Mailer');
        $swift->shouldReceive('createMessage')->andReturn(new \Swift_Message);
        $swift->shouldReceive('getTransport')->andReturn($transport = m::mock('Swift_Transport'));
        $transport->shouldReceive('stop');
        $mailer->setSwiftMailer($swift);

        return $mailer;
    }

    protected function getMocks()
    {
        return [m::mock('Illuminate\Contracts\View\Factory'), m::mock('Swift_Mailer')];
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
        $transport = m::mock('Swift_Transport');
        $transport->shouldReceive('stop');

        return $transport;
    }

    public function createMessage()
    {
        return new \Swift_Message();
    }
}
