<?php

namespace Illuminate\Tests\Mail\Fixtures;

use Aws\Result;
use Aws\SesV2\SesV2Client;

/**
 * Declares the methods that SesV2Client otherwise only forwards through __call(), so they can be doubled.
 */
class SesV2ClientStub extends SesV2Client
{
    public function sendEmail(array $args = []): Result
    {
        return new Result;
    }
}
