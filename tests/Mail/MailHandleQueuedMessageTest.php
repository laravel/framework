<?php

use Illuminate\Mail\Jobs\HandleQueuedMessage;
use Illuminate\Contracts\Database\ModelIdentifier;

class MailHandleQueuedMessageTest extends PHPUnit_Framework_TestCase
{
    public function test_it_serializes_models_to_model_identifiers_and_serializes_callback()
    {
        $job = new HandleQueuedMessage('view', ['user' => new HandleQueuedMessageModelStub], function () {
            //
        });

        $job->__sleep();

        $this->assertInstanceOf(ModelIdentifier::class, $job->data['user']);
        $this->assertTrue(is_string($job->callback));
    }

    public function test_it_restores_models_and_callback()
    {
        $job = new HandleQueuedMessageWithFakeRestore('view', ['user' => new HandleQueuedMessageModelStub], function () {
            //
        });

        $job->__sleep();

        $this->assertInstanceOf(ModelIdentifier::class, $job->data['user']);
        $this->assertTrue(is_string($job->callback));

        $job->__wakeup();

        $this->assertEquals(123, $job->data['user']);
        $this->assertInstanceOf('Closure', $job->callback);
    }
}

class HandleQueuedMessageModelStub extends Illuminate\Database\Eloquent\Model
{
}

class HandleQueuedMessageWithFakeRestore extends HandleQueuedMessage
{
    protected function getRestoredPropertyValue($value)
    {
        return 123;
    }
}
