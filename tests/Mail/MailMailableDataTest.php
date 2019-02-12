<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Mail\Mailable;
use PHPUnit\Framework\TestCase;

class MailMailableDataTest extends TestCase
{
    public function testMailableDataIsNotLost()
    {
        $testData = ['first_name' => 'James'];

        $mailable = new MailableStub;
        $mailable->build(function ($m) use ($testData) {
            $m->view('view', $testData);
        });
        $this->assertSame($testData, $mailable->buildViewData());

        $mailable = new MailableStub;
        $mailable->build(function ($m) use ($testData) {
            $m->view('view', $testData)
              ->text('text-view');
        });
        $this->assertSame($testData, $mailable->buildViewData());
    }
}

class MailableStub extends Mailable
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build($builder)
    {
        $builder($this);
    }
}
