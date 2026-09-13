<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Container\Container;
use Illuminate\Http\Client\Factory;
use Illuminate\Queue\CloudflareQueue;
use Illuminate\Queue\CloudflareQueueClient;
use Illuminate\Queue\Jobs\CloudflareJob;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class QueueCloudflareQueueTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testPushRawSendsMessageToCloudflare()
    {
        $http = new Factory;

        $http->fake([
            '*queues/queue-id/messages' => Factory::response([
                'success' => true,
                'result' => [],
            ], 200),
        ]);

        $queue = $this->createQueue($http);

        $uuid = $queue->pushRaw(json_encode(['uuid' => 'job-uuid', 'job' => 'test']));

        $this->assertSame('job-uuid', $uuid);

        $http->assertSent(function ($request) {
            return str_contains($request->url(), '/messages')
                && ! str_contains($request->url(), '/messages/')
                && $request['body'] === json_encode(['uuid' => 'job-uuid', 'job' => 'test'])
                && $request['content_type'] === 'text'
                && ! isset($request['delay_seconds']);
        });
    }

    public function testPushRawWrapsDelaysBeyondTwelveHours()
    {
        Carbon::setTestNow('2026-01-01 00:00:00');

        $http = new Factory;

        $http->fake([
            '*queues/queue-id/messages' => Factory::response([
                'success' => true,
                'result' => [],
            ], 200),
        ]);

        $queue = $this->createQueue($http);

        $payload = json_encode(['uuid' => 'job-uuid', 'job' => 'test']);

        $queue->pushRaw($payload, null, ['delay' => 50_000]);

        $http->assertSent(function ($request) {
            $body = json_decode($request['body'], true);

            return isset($body[CloudflareQueue::DELAY_WRAPPER_KEY])
                && $body['__cf_payload'] === json_encode(['uuid' => 'job-uuid', 'job' => 'test'])
                && $request['delay_seconds'] === CloudflareQueue::MAX_DELAY_SECONDS;
        });
    }

    public function testPopReturnsCloudflareJobFromPullResponse()
    {
        $payload = json_encode(['uuid' => 'job-uuid', 'job' => 'test', 'data' => []]);

        $http = new Factory;

        $http->fake([
            '*messages/pull' => Factory::response([
                'success' => true,
                'result' => [
                    'messages' => [
                        [
                            'id' => 'message-id',
                            'lease_id' => 'lease-id',
                            'attempts' => 1,
                            'body' => $payload,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $queue = $this->createQueue($http, batchSize: 1);

        $job = $queue->pop();

        $this->assertInstanceOf(CloudflareJob::class, $job);
        $this->assertSame($payload, $job->getRawBody());
        $this->assertSame('message-id', $job->getJobId());
    }

    public function testPopBuffersMultipleMessagesFromSinglePull()
    {
        $http = new Factory;

        $http->fake([
            '*messages/pull' => Factory::response([
                'success' => true,
                'result' => [
                    'messages' => [
                        ['id' => '1', 'lease_id' => 'lease-1', 'attempts' => 1, 'body' => '{"job":"one"}'],
                        ['id' => '2', 'lease_id' => 'lease-2', 'attempts' => 1, 'body' => '{"job":"two"}'],
                    ],
                ],
            ], 200),
        ]);

        $queue = $this->createQueue($http, batchSize: 2);

        $first = $queue->pop();
        $second = $queue->pop();

        $this->assertSame('1', $first->getJobId());
        $this->assertSame('2', $second->getJobId());

        $pullRequests = collect($http->recorded())->filter(
            fn ($pair) => str_contains($pair[0]->url(), '/messages/pull')
        );

        $this->assertCount(1, $pullRequests);
    }

    public function testPopReQueuesDelayWrapperForNextHop()
    {
        Carbon::setTestNow('2026-01-01 00:00:00');

        $originalPayload = json_encode(['uuid' => 'job-uuid', 'job' => 'test']);

        $wrapper = json_encode([
            CloudflareQueue::DELAY_WRAPPER_KEY => true,
            '__cf_execute_at' => Carbon::now()->addHours(20)->timestamp,
            '__cf_payload' => $originalPayload,
        ]);

        $http = new Factory;

        $http->fake([
            '*messages/pull' => $http->sequence()
                ->push([
                    'success' => true,
                    'result' => [
                        'messages' => [
                            [
                                'id' => 'wrapper-id',
                                'lease_id' => 'wrapper-lease',
                                'attempts' => 1,
                                'body' => $wrapper,
                            ],
                        ],
                    ],
                ], 200)
                ->push([
                    'success' => true,
                    'result' => ['messages' => []],
                ], 200),
            '*queues/queue-id/messages' => Factory::response([
                'success' => true,
                'result' => [],
            ], 200),
            '*messages/ack' => Factory::response([
                'success' => true,
                'result' => ['ackCount' => 1],
            ], 200),
        ]);

        $queue = $this->createQueue($http);

        $this->assertNull($queue->pop());

        $http->assertSent(function ($request) {
            if (! str_ends_with($request->url(), '/messages')) {
                return false;
            }

            $body = json_decode($request['body'], true);

            return isset($body[CloudflareQueue::DELAY_WRAPPER_KEY])
                && $request['delay_seconds'] === CloudflareQueue::MAX_DELAY_SECONDS;
        });

        $http->assertSent(function ($request) {
            return str_contains($request->url(), '/messages/ack')
                && $request['acks'] === [['lease_id' => 'wrapper-lease']];
        });
    }

    public function testClearPurgesQueueAndReturnsPreviousSize()
    {
        $http = new Factory;

        $http->fake(function ($request) {
            if (str_contains($request->url(), '/purge')) {
                return Factory::response(['success' => true, 'result' => []], 200);
            }

            return Factory::response([
                'success' => true,
                'result' => ['message_backlog_count' => 7],
            ], 200);
        });

        $queue = $this->createQueue($http);

        $this->assertSame(7, $queue->clear());
    }

    protected function createQueue(Factory $http, int $batchSize = 1): CloudflareQueue
    {
        $client = new CloudflareQueueClient($http, [
            'account_id' => 'account',
            'queue_id' => 'queue-id',
            'token' => 'token',
        ]);

        $queue = new CloudflareQueue($client, 'default', false, $batchSize, 30_000);
        $queue->setContainer(new Container);
        $queue->setConnectionName('cloudflare');

        return $queue;
    }
}
