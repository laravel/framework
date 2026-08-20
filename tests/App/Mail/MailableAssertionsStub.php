<?php

namespace Illuminate\Tests\App\Mail;

use Illuminate\Mail\Mailable;

class MailableAssertionsStub extends Mailable
{
    protected function renderForAssertions()
    {
        $text = <<<'EOD'
        # List
        - First Item
        - Second Item
        - Third Item
        - Fourth & Fifth Item
        - Sixth Item
        - It's a wonderful day
        EOD;

        $html = <<<'EOD'
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        </style>
        </head>
        <body>
        <h1>List</h1>
        <ul>
        <li>First Item</li>
        <li>Second Item</li>
        <li>Third Item</li>
        <li>Fourth &amp; Fifth Item</li>
        <li>Sixth Item</li>
        <li>It's a wonderful day</li>
        </ul>
        </body>
        </html>
        EOD;

        return [$html, $text];
    }
}
