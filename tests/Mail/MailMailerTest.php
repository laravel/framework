<?php

use Mockery as m;
use SuperClosure\Serializer;

class MailMailerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testMailerSendSendsMessageWithProperViewContent()
    {
        unset($_SERVER['__mailer.test']);
        $mailer = $this->getMock('Illuminate\Mail\Mailer', ['createMessage'], $this->getMocks());
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
        $mailer->send('foo', ['data'], function ($m) { $_SERVER['__mailer.test'] = $m; });
        unset($_SERVER['__mailer.test']);
    }

    public function testMailerSendSendsMessageWithProperPlainViewContent()
    {
        unset($_SERVER['__mailer.test']);
        $mailer = $this->getMock('Illuminate\Mail\Mailer', ['createMessage'], $this->getMocks());
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
        $mailer->send(['foo', 'bar'], ['data'], function ($m) { $_SERVER['__mailer.test'] = $m; });
        unset($_SERVER['__mailer.test']);
    }

    public function testMailerSendSendsMessageWithProperPlainViewContentWhenExplicit()
    {
        unset($_SERVER['__mailer.test']);
        $mailer = $this->getMock('Illuminate\Mail\Mailer', ['createMessage'], $this->getMocks());
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
        $mailer->send(['html' => 'foo', 'text' => 'bar'], ['data'], function ($m) { $_SERVER['__mailer.test'] = $m; });
        unset($_SERVER['__mailer.test']);
    }

    public function testMailerCanQueueMessagesToItself()
    {
        list($view, $swift) = $this->getMocks();
        $mailer = new Illuminate\Mail\Mailer($view, $swift);
        $mailer->setQueue($queue = m::mock('Illuminate\Contracts\Queue\Queue'));
        $queue->shouldReceive('push')->once()->with('mailer@handleQueuedMessage', ['view' => 'foo', 'data' => [1], 'callback' => 'callable'], null);

        $mailer->queue('foo', [1], 'callable');
    }

    public function testMailerCanQueueMessagesToItselfOnAnotherQueue()
    {
        list($view, $swift) = $this->getMocks();
        $mailer = new Illuminate\Mail\Mailer($view, $swift);
        $mailer->setQueue($queue = m::mock('Illuminate\Contracts\Queue\Queue'));
        $queue->shouldReceive('push')->once()->with('mailer@handleQueuedMessage', ['view' => 'foo', 'data' => [1], 'callback' => 'callable'], 'queue');

        $mailer->queueOn('queue', 'foo', [1], 'callable');
    }

    public function testMailerCanQueueMessagesToItselfWithSerializedClosures()
    {
        list($view, $swift) = $this->getMocks();
        $mailer = new Illuminate\Mail\Mailer($view, $swift);
        $mailer->setQueue($queue = m::mock('Illuminate\Contracts\Queue\Queue'));
        $serialized = (new Serializer)->serialize($closure = function () {});
        $queue->shouldReceive('push')->once()->with('mailer@handleQueuedMessage', ['view' => 'foo', 'data' => [1], 'callback' => $serialized], null);

        $mailer->queue('foo', [1], $closure);
    }

    public function testMailerCanQueueMessagesToItselfLater()
    {
        list($view, $swift) = $this->getMocks();
        $mailer = new Illuminate\Mail\Mailer($view, $swift);
        $mailer->setQueue($queue = m::mock('Illuminate\Contracts\Queue\Queue'));
        $queue->shouldReceive('later')->once()->with(10, 'mailer@handleQueuedMessage', ['view' => 'foo', 'data' => [1], 'callback' => 'callable'], null);

        $mailer->later(10, 'foo', [1], 'callable');
    }

    public function testMailerCanQueueMessagesToItselfLaterOnAnotherQueue()
    {
        list($view, $swift) = $this->getMocks();
        $mailer = new Illuminate\Mail\Mailer($view, $swift);
        $mailer->setQueue($queue = m::mock('Illuminate\Contracts\Queue\Queue'));
        $queue->shouldReceive('later')->once()->with(10, 'mailer@handleQueuedMessage', ['view' => 'foo', 'data' => [1], 'callback' => 'callable'], 'queue');

        $mailer->laterOn('queue', 10, 'foo', [1], 'callable');
    }

    public function testMailerCanResolveMailerClasses()
    {
        $mailer = $this->getMock('Illuminate\Mail\Mailer', ['createMessage'], $this->getMocks());
        $message = m::mock('Swift_Mime_Message');
        $mailer->expects($this->once())->method('createMessage')->will($this->returnValue($message));
        $view = m::mock('StdClass');
        $container = new Illuminate\Container\Container;
        $mailer->setContainer($container);
        $mockMailer = m::mock('StdClass');
        $container['FooMailer'] = $container->share(function () use ($mockMailer) {
            return $mockMailer;
        });
        $mockMailer->shouldReceive('mail')->once()->with($message);
        $mailer->getViewFactory()->shouldReceive('make')->once()->with('foo', ['data', 'message' => $message])->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');
        $message->shouldReceive('setBody')->once()->with('rendered.view', 'text/html');
        $message->shouldReceive('setFrom')->never();
        $this->setSwiftMailer($mailer);
        $message->shouldReceive('getSwiftMessage')->once()->andReturn($message);
        $mailer->getSwiftMailer()->shouldReceive('send')->once()->with($message, []);
        $mailer->send('foo', ['data'], 'FooMailer');
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
        $mailer->send('foo', ['data'], function ($m) {});
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

        $mailer->send('foo', ['data'], function ($m) {});

        $this->assertEquals(['taylorotwell@gmail.com'], $mailer->failures());
    }

    protected function getMailer()
    {
        return new Illuminate\Mail\Mailer(m::mock('Illuminate\Contracts\View\Factory'), m::mock('Swift_Mailer'));
    }

    public function setSwiftMailer($mailer)
    {
        $swift = m::mock('Swift_Mailer');
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
}
