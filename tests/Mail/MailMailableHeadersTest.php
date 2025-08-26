<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Mail\Mailables\Headers;
use PHPUnit\Framework\TestCase;

class MailMailableHeadersTest extends TestCase
{
    public function test()
    {
        $headers = new Headers(
            '434571BC.8070702@example.net',
            [
                '<19980506192030.26456.qmail@cr.yp.to>',
                '<19980507220459.5655.qmail@warren.demon.co.uk>',
                '<19980508103652.B21462@iconnect.co.ke>',
                '<19980509035615.40087@rucus.ru.ac.za>',
            ],
        );

        $this->assertSame(
            '<19980506192030.26456.qmail@cr.yp.to> <19980507220459.5655.qmail@warren.demon.co.uk> <19980508103652.B21462@iconnect.co.ke> <19980509035615.40087@rucus.ru.ac.za>',
            $headers->referencesString(),
        );
    }
}
