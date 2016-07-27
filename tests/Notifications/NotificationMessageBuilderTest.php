<?php

use Illuminate\Notifications\MessageBuilder;

class MessageBuilderTest extends PHPUnit_Framework_TestCase
{
    public function testMessageBuilderAddsActionsCorrectly()
    {
        $builder = new MessageBuilder(new StdClass, 'line 1');
        $builder->action('Text', 'url');
        $builder->line('line 2');

        $this->assertEquals('line 1', $builder->elements[0]);
        $this->assertEquals('Text', $builder->elements[1]->text);
        $this->assertEquals('url', $builder->elements[1]->url);
        $this->assertEquals('line 2', $builder->elements[2]);
    }
}
