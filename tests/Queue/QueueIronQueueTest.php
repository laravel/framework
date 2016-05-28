<?php

use Mockery as m;
use SuperClosure\Serializer;

class QueueIronQueueTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testPushProperlyPushesJobOntoIron()
    {
        $queue = new Illuminate\Queue\IronQueue($iron = m::mock('IronMQ\IronMQ'), m::mock('Illuminate\Http\Request'), 'default', true);
        $crypt = m::mock('Illuminate\Contracts\Encryption\Encrypter');
        $queue->setEncrypter($crypt);
        $crypt->shouldReceive('encrypt')->once()->with(json_encode(['job' => 'foo', 'data' => [1, 2, 3], 'attempts' => 1, 'queue' => 'default']))->andReturn('encrypted');
        $iron->shouldReceive('postMessage')->once()->with('default', 'encrypted', [])->andReturn((object) ['id' => 1]);
        $queue->push('foo', [1, 2, 3]);
    }

    public function testPushProperlyPushesJobOntoIronWithoutEncryption()
    {
        $queue = new Illuminate\Queue\IronQueue($iron = m::mock('IronMQ\IronMQ'), m::mock('Illuminate\Http\Request'), 'default');
        $crypt = m::mock('Illuminate\Contracts\Encryption\Encrypter');
        $queue->setEncrypter($crypt);
        $crypt->shouldReceive('encrypt')->never();
        $iron->shouldReceive('postMessage')->once()->with('default', json_encode(['job' => 'foo', 'data' => [1, 2, 3], 'attempts' => 1, 'queue' => 'default']), [])->andReturn((object) ['id' => 1]);
        $queue->push('foo', [1, 2, 3]);
    }

    public function testPushProperlyPushesJobOntoIronWithClosures()
    {
        $queue = new Illuminate\Queue\IronQueue($iron = m::mock('IronMQ\IronMQ'), m::mock('Illuminate\Http\Request'), 'default', true);
        $crypt = m::mock('Illuminate\Contracts\Encryption\Encrypter');
        $queue->setEncrypter($crypt);
        $name = 'Foo';
        $closure = (new Serializer)->serialize($innerClosure = function () use ($name) {
            return $name;
        });
        $crypt->shouldReceive('encrypt')->once()->with($closure)->andReturn('serial_closure');
        $crypt->shouldReceive('encrypt')->once()->with(json_encode([
            'job' => 'IlluminateQueueClosure', 'data' => ['closure' => 'serial_closure'], 'attempts' => 1, 'queue' => 'default',
        ]))->andReturn('encrypted');
        $iron->shouldReceive('postMessage')->once()->with('default', 'encrypted', [])->andReturn((object) ['id' => 1]);
        $queue->push($innerClosure);
    }

    public function testDelayedPushProperlyPushesJobOntoIron()
    {
        $queue = new Illuminate\Queue\IronQueue($iron = m::mock('IronMQ\IronMQ'), m::mock('Illuminate\Http\Request'), 'default', true);
        $crypt = m::mock('Illuminate\Contracts\Encryption\Encrypter');
        $queue->setEncrypter($crypt);
        $crypt->shouldReceive('encrypt')->once()->with(json_encode([
            'job' => 'foo', 'data' => [1, 2, 3], 'attempts' => 1, 'queue' => 'default',
        ]))->andReturn('encrypted');
        $iron->shouldReceive('postMessage')->once()->with('default', 'encrypted', ['delay' => 5])->andReturn((object) ['id' => 1]);
        $queue->later(5, 'foo', [1, 2, 3]);
    }

    public function testDelayedPushProperlyPushesJobOntoIronWithTimestamp()
    {
        $now = Carbon\Carbon::now();
        $queue = $this->getMock('Illuminate\Queue\IronQueue', ['getTime'], [$iron = m::mock('IronMQ\IronMQ'), m::mock('Illuminate\Http\Request'), 'default', true]);
        $crypt = m::mock('Illuminate\Contracts\Encryption\Encrypter');
        $queue->setEncrypter($crypt);
        $queue->expects($this->once())->method('getTime')->will($this->returnValue($now->getTimestamp()));
        $crypt->shouldReceive('encrypt')->once()->with(json_encode(['job' => 'foo', 'data' => [1, 2, 3], 'attempts' => 1, 'queue' => 'default']))->andReturn('encrypted');
        $iron->shouldReceive('postMessage')->once()->with('default', 'encrypted', ['delay' => 5])->andReturn((object) ['id' => 1]);
        $queue->later($now->addSeconds(5), 'foo', [1, 2, 3]);
    }

    public function testPopProperlyPopsJobOffOfIron()
    {
        $queue = new Illuminate\Queue\IronQueue($iron = m::mock('IronMQ\IronMQ'), m::mock('Illuminate\Http\Request'), 'default', true);
        $crypt = m::mock('Illuminate\Contracts\Encryption\Encrypter');
        $queue->setEncrypter($crypt);
        $queue->setContainer(m::mock('Illuminate\Container\Container'));
        $iron->shouldReceive('getMessage')->once()->with('default')->andReturn($job = m::mock('IronMQ_Message'));
        $job->body = 'foo';
        $crypt->shouldReceive('decrypt')->once()->with('foo')->andReturn('foo');
        $result = $queue->pop();

        $this->assertInstanceOf('Illuminate\Queue\Jobs\IronJob', $result);
    }

    public function testPopProperlyPopsJobOffOfIronWithoutEncryption()
    {
        $queue = new Illuminate\Queue\IronQueue($iron = m::mock('IronMQ\IronMQ'), m::mock('Illuminate\Http\Request'), 'default');
        $crypt = m::mock('Illuminate\Contracts\Encryption\Encrypter');
        $queue->setEncrypter($crypt);
        $queue->setContainer(m::mock('Illuminate\Container\Container'));
        $iron->shouldReceive('getMessage')->once()->with('default')->andReturn($job = m::mock('IronMQ_Message'));
        $job->body = 'foo';
        $crypt->shouldReceive('decrypt')->never();
        $result = $queue->pop();

        $this->assertInstanceOf('Illuminate\Queue\Jobs\IronJob', $result);
    }

    public function testPushedJobsCanBeMarshaled()
    {
        $queue = $this->getMock('Illuminate\Queue\IronQueue', ['createPushedIronJob'], [$iron = m::mock('IronMQ\IronMQ'), $request = m::mock('Illuminate\Http\Request'), 'default', true]);
        $crypt = m::mock('Illuminate\Contracts\Encryption\Encrypter');
        $queue->setEncrypter($crypt);
        $request->shouldReceive('header')->once()->with('iron-message-id')->andReturn('message-id');
        $request->shouldReceive('getContent')->once()->andReturn($content = json_encode(['foo' => 'bar']));
        $crypt->shouldReceive('decrypt')->once()->with($content)->andReturn($content);
        $job = (object) ['id' => 'message-id', 'body' => json_encode(['foo' => 'bar']), 'pushed' => true];
        $queue->expects($this->once())->method('createPushedIronJob')->with($this->equalTo($job))->will($this->returnValue($mockIronJob = m::mock('StdClass')));
        $mockIronJob->shouldReceive('fire')->once();

        $response = $queue->marshal();

        $this->assertInstanceOf('Illuminate\Http\Response', $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
