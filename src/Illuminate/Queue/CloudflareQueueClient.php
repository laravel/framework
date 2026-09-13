<?php

namespace Illuminate\Queue;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use InvalidArgumentException;
use RuntimeException;

class CloudflareQueueClient
{
    /**
     * Create a new Cloudflare Queues API client instance.
     */
    public function __construct(
        protected Factory $http,
        protected array $config,
    ) {
        //
    }

    /**
     * Pull a batch of messages from the queue.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pull(int $batchSize, int $visibilityTimeoutMs): array
    {
        $response = $this->request()->post('messages/pull', [
            'batch_size' => $batchSize,
            'visibility_timeout_ms' => $visibilityTimeoutMs,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Cloudflare Queue pull failed [{$response->status()}]: {$response->body()}"
            );
        }

        return $response->json('result.messages', []);
    }

    /**
     * Acknowledge messages by lease ID, removing them from the queue permanently.
     *
     * @param  array<int, string>  $leaseIds
     */
    public function ack(array $leaseIds): void
    {
        if ($leaseIds === []) {
            return;
        }

        $response = $this->request()->post('messages/ack', [
            'acks' => array_map(fn ($id) => ['lease_id' => $id], $leaseIds),
            'retries' => [],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Cloudflare Queue ack failed [{$response->status()}]: {$response->body()}"
            );
        }
    }

    /**
     * Release messages back to the queue with an optional delay.
     *
     * @param  array<int, array{lease_id: string, delay_seconds?: int}>  $retries
     */
    public function retry(array $retries): void
    {
        if ($retries === []) {
            return;
        }

        foreach ($retries as $retry) {
            if (! isset($retry['lease_id'])) {
                throw new InvalidArgumentException('Each retry entry must contain a lease_id.');
            }
        }

        $response = $this->request()->post('messages/ack', [
            'acks' => [],
            'retries' => $retries,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Cloudflare Queue retry failed [{$response->status()}]: {$response->body()}"
            );
        }
    }

    /**
     * Send a single message to the queue.
     */
    public function send(string $payload, int $delaySeconds = 0): void
    {
        $body = [
            'body' => $payload,
            'content_type' => 'text',
        ];

        if ($delaySeconds > 0) {
            $body['delay_seconds'] = $delaySeconds;
        }

        $response = $this->request()->post('messages', $body);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Cloudflare Queue send failed [{$response->status()}]: {$response->body()}"
            );
        }
    }

    /**
     * Send multiple messages to the queue in a single request.
     *
     * @param  array<int, array{body: string, delay_seconds?: int}>  $messages
     */
    public function bulkSend(array $messages): void
    {
        if ($messages === []) {
            return;
        }

        $payload = [
            'messages' => array_map(function (array $message) {
                $entry = [
                    'body' => $message['body'],
                    'content_type' => 'text',
                ];

                if (($message['delay_seconds'] ?? 0) > 0) {
                    $entry['delay_seconds'] = $message['delay_seconds'];
                }

                return $entry;
            }, $messages),
        ];

        $response = $this->request()->post('messages/batch', $payload);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Cloudflare Queue batch send failed [{$response->status()}]: {$response->body()}"
            );
        }
    }

    /**
     * Fetch queue metadata, including backlog metrics.
     *
     * @return array<string, mixed>
     */
    public function info(): array
    {
        $response = $this->request()->get('');

        if (! $response->successful()) {
            return [];
        }

        return $response->json('result', []);
    }

    /**
     * Purge all messages from the queue.
     */
    public function purge(): void
    {
        $response = $this->request()->post('purge');

        if (! $response->successful()) {
            throw new RuntimeException(
                "Cloudflare Queue purge failed [{$response->status()}]: {$response->body()}"
            );
        }
    }

    /**
     * Build the configured HTTP client for Cloudflare Queues API requests.
     */
    protected function request(): PendingRequest
    {
        $token = $this->config['token'] ?? $this->config['api_token'] ?? null;

        return $this->http->asJson()
            ->withUserAgent('Laravel')
            ->baseUrl(sprintf(
                'https://api.cloudflare.com/client/v4/accounts/%s/queues/%s/',
                $this->config['account_id'],
                $this->config['queue_id'],
            ))
            ->withToken($token)
            ->retry(3, 100, throw: false);
    }
}
