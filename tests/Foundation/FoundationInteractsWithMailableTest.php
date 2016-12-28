<?php

use Mockery as m;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Foundation\Testing\Concerns\InteractsWithMailable;

class FoundationInteractsWithMailableTest extends PHPUnit_Framework_TestCase
{
    use InteractsWithMailable;

    public $app;

    public function tearDown()
    {
        m::close();
    }

    protected function mockRender(Mailable $mailable, $template, array $data, $result)
    {
        $this->app = m::mock(Application::class);

        $this->app->shouldReceive('call')
            ->withArgs([[$mailable, 'build']])
            ->andReturnUsing(function () use ($mailable) {
                $mailable->build();
            });

        $view = m::mock(View::class);
        $view->shouldReceive('render')
            ->andReturn($result);

        $factory = m::mock(ViewFactory::class);
        $factory->shouldReceive('make')
            ->once()
            ->withArgs([$template, $data])
            ->andReturn($view);

        $this->app->shouldReceive('make')
            ->once()
            ->withArgs(['view'])
            ->andReturn($factory);
    }

    public function testRenderView()
    {
        $mailable = new MailableStub;

        $this->mockRender($mailable, 'mail.view', ['framework' => 'Laravel'], '[View]');

        $this->assertSame('[View]', $this->renderView($mailable));
    }

    public function testRenderTextView()
    {
        $mailable = new MailableStub;

        $this->mockRender($mailable, 'mail.text-view', ['framework' => 'Laravel'], '[Text View]');

        $this->assertSame('[Text View]', $this->renderTextView($mailable));
    }
}

class MailableStub extends Mailable
{
    public function build()
    {
        $this->view('mail.view')
            ->text('mail.text-view')
            ->with(['framework' => 'Laravel']);
    }
}
