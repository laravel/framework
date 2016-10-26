<?php

use Illuminate\Notifications\Action;

class NotificationActionTest extends PHPUnit_Framework_TestCase
{
    public function testActionIsCreatedProperly()
    {
        $action = new Action('Text', 'url');

        $this->assertEquals('Text', $action->text);
        $this->assertEquals('url', $action->url);
    }
}
