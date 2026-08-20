<?php

namespace Illuminate\Tests\App\Mail;

use Illuminate\Queue\Attributes\Connection;
use Illuminate\Queue\Attributes\Delay;
use Illuminate\Queue\Attributes\Queue as QueueAttribute;

#[Connection('sqs')]
#[Delay(30)]
#[QueueAttribute('delayed-mail-queue')]
class MailableQueueableStubWithDelayQueueAndConnectionAttributes extends MailableQueueableStub
{
    //
}
