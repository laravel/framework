<?php

use Mockery as m;
use Illuminate\Mail\Transport\PreviewTransport;

class MailPreviewTransportTest extends PHPUnit_Framework_TestCase
{
    public function testSend()
    {
        $message = new Swift_Message('Foo subject', '<html>Body</html>');
        $message->setFrom('myself@example.com');
        $message->setTo('me@example.com');

        $files = m::mock('Illuminate\Filesystem\Filesystem');

        $transport = new PreviewTransport(
            $files,
            'framework/emails'
        );

        $files->shouldReceive('exists')->once()->with('framework/emails')->andReturn(true);

        $files->shouldReceive('files')->once()->with('framework/emails')->andReturn([]);

        $files->shouldReceive('put')->once()->with(
            'framework/emails/me_at_examplecom_foo_subject.html',
            '<!--From:myself@example.com, to:me@example.com, subject:Foo subject--><html>Body</html>'
        );

        $transport->send($message);
    }

    public function testCreatesPreviewDirectory()
    {
        $message = new Swift_Message('Foo subject', '<html>Body</html>');
        $message->setFrom('myself@example.com');
        $message->setTo('me@example.com');

        $files = m::mock('Illuminate\Filesystem\Filesystem');

        $transport = new PreviewTransport(
            $files,
            'framework/emails'
        );

        $files->shouldReceive('exists')->once()->with('framework/emails')->andReturn(false);

        $files->shouldReceive('makeDirectory')->once()->with('framework/emails');

        $files->shouldReceive('put')->once()->with(
            'framework/emails/.gitignore',
            "*\n!.gitignore"
        );

        self::getMethod('createEmailPreviewDirectory')->invokeArgs($transport, [$message]);
    }

    protected static function getMethod($name)
    {
        $class = new ReflectionClass(PreviewTransport::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }
}
