<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Notifications\Action;
use PHPUnit\Framework\TestCase;

class NotificationActionTest extends TestCase
{
    public function testActionIsCreatedProperly()
    {
        $action = new Action('Text', 'url');

        $this->assertEquals('Text', $action->text);
        $this->assertEquals('url', $action->url);
    }
}
