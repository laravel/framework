<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Tests\App\Mail\MailableWithCallbackBuild;
use PHPUnit\Framework\TestCase;

class MailMailableDataTest extends TestCase
{
    public function testMailableDataIsNotLost(): void
    {
        $mailable = new MailableWithCallbackBuild;

        $testData = [
            'first_name' => 'James',
            '__laravel_mailable' => get_class($mailable),
        ];

        $mailable->build(function ($m) use ($testData) {
            $m->view('view', $testData);
        });
        $this->assertSame($testData, $mailable->buildViewData());

        $mailable = new MailableWithCallbackBuild;
        $mailable->build(function ($m) use ($testData) {
            $m->view('view', $testData)
                ->text('text-view');
        });
        $this->assertSame($testData, $mailable->buildViewData());
    }
}
