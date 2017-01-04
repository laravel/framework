<?php

use Illuminate\Mail\Mailable;

class MailMailableTest extends PHPUnit_Framework_TestCase
{
    public function testMailableBuildsViewData()
    {
        $mailable = new WelcomeMailableStub;

        $mailable->build();

        $expected = [
            'first_name' => 'Taylor',
            'last_name' => 'Otwell',
            'framework' => 'Laravel',
        ];

        $this->assertSame($expected, $mailable->buildViewData());
    }
}

class WelcomeMailableStub extends Mailable
{
    public $framework = 'Laravel';

    protected $version = '5.3';

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->with('first_name', 'Taylor')
            ->withLastName('Otwell');
    }
}
