<?php

namespace Illuminate\Tests\App\Mail;

use Illuminate\Mail\Mailable;

class MailableAssertionsBladeEscapedStub extends Mailable
{
    protected function renderForAssertions()
    {
        $text = "It's a wonderful day";

        $html = <<<'EOD'
        <!DOCTYPE html>
        <html>
        <body>
        <div>It&#039;s a wonderful day</div>
        </body>
        </html>
        EOD;

        /**
         * Since stub override `renderForAssertions()` we should expect that `$html` is available from either `$this->view` or `$this->markdown`.
         */

        return [$html, $text];
    }
}
