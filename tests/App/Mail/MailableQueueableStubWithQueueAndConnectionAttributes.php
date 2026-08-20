<?php

namespace Illuminate\Tests\App\Mail;

use Illuminate\Queue\Attributes\Connection;
use Illuminate\Queue\Attributes\Queue as QueueAttribute;

#[Connection('redis')]
#[QueueAttribute('mail-queue')]
class MailableQueueableStubWithQueueAndConnectionAttributes extends MailableQueueableStub
{
    //
}
