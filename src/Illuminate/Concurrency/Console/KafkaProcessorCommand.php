<?php

namespace Illuminate\Concurrency\Console;

use Illuminate\Console\Command;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Producer;

class KafkaProcessorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'concurrency:kafka-processor
                          {--brokers=localhost:9092 : Kafka bootstrap servers}
                          {--task-topic=laravel-concurrency-tasks : Kafka topic for tasks}
                          {--result-topic=laravel-concurrency-results : Kafka topic for results}
                          {--deferred-topic=laravel-concurrency-deferred : Kafka topic for deferred tasks}
                          {--group-id=laravel-concurrency-group : Kafka consumer group ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process concurrent tasks from Kafka';

    /**
     * The Kafka consumer instance.
     *
     * @var \RdKafka\KafkaConsumer
     */
    protected $consumer;

    /**
     * The Kafka producer instance.
     *
     * @var \RdKafka\Producer
     */
    protected $producer;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $brokers = $this->option('brokers');
        $taskTopic = $this->option('task-topic');
        $resultTopic = $this->option('result-topic');
        $deferredTopic = $this->option('deferred-topic');
        $groupId = $this->option('group-id');

        $this->producer = $this->createProducer($brokers);
        $this->consumer = $this->createConsumer($brokers, $groupId, [$taskTopic, $deferredTopic]);

        $this->info('Starting Kafka processor...');
        $this->info("Listening for tasks on topics: {$taskTopic}, {$deferredTopic}");
        $this->info("Sending results to topic: {$resultTopic}");

        while (true) {
            try {
                // Poll for messages with a 1000ms timeout
                $message = $this->consumer->consume(1000);

                // Skip invalid messages
                if ($message === null || $message->err !== RD_KAFKA_RESP_ERR_NO_ERROR) {
                    continue;
                }

                // Process the message
                $this->processMessage($message, $resultTopic);

                // Poll to handle delivery reports
                $this->producer->poll(0);
            } catch (\Exception $e) {
                $this->error("Error processing message: {$e->getMessage()}");
            }
        }

        return 0;
    }

    /**
     * Process a Kafka message.
     *
     * @param  \RdKafka\Message  $message
     * @param  string  $resultTopic
     * @return void
     */
    protected function processMessage($message, $resultTopic)
    {
        $payload = json_decode($message->payload, true);

        if (! isset($payload['task_id']) || ! isset($payload['task'])) {
            $this->warn('Invalid task message format');

            return;
        }

        $taskId = $payload['task_id'];
        $task = unserialize($payload['task']);

        $this->info("Processing task: {$taskId}");

        try {
            // Execute the task
            $result = $task();

            // Send the result back
            $this->sendResult($taskId, $result, null, $resultTopic);

            $this->info("Task {$taskId} completed successfully");
        } catch (\Exception $e) {
            $this->error("Task {$taskId} failed: {$e->getMessage()}");

            // Send the error back
            $this->sendResult($taskId, null, $e->getMessage(), $resultTopic);
        }
    }

    /**
     * Send a result to the specified Kafka topic.
     *
     * @param  string  $taskId
     * @param  mixed  $result
     * @param  string|null  $error
     * @param  string  $topic
     * @return void
     */
    protected function sendResult($taskId, $result, $error, $topic)
    {
        $kafkaTopic = $this->producer->newTopic($topic);

        $payload = json_encode([
            'task_id' => $taskId,
            'result' => $result !== null ? serialize($result) : null,
            'error' => $error,
        ]);

        $kafkaTopic->produce(RD_KAFKA_PARTITION_UA, 0, $payload, $taskId);
    }

    /**
     * Create a Kafka producer instance.
     *
     * @param  string  $brokers
     * @return \RdKafka\Producer
     */
    protected function createProducer($brokers)
    {
        $conf = new Conf();
        $conf->set('bootstrap.servers', $brokers);

        return new Producer($conf);
    }

    /**
     * Create a Kafka consumer instance.
     *
     * @param  string  $brokers
     * @param  string  $groupId
     * @param  array  $topics
     * @return \RdKafka\KafkaConsumer
     */
    protected function createConsumer($brokers, $groupId, array $topics)
    {
        $conf = new Conf();
        $conf->set('bootstrap.servers', $brokers);
        $conf->set('group.id', $groupId);
        $conf->set('auto.offset.reset', 'latest');

        $consumer = new KafkaConsumer($conf);
        $consumer->subscribe($topics);

        return $consumer;
    }
}
