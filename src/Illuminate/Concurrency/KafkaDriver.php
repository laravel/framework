<?php

namespace Illuminate\Concurrency;

use Closure;
use Exception;
use Illuminate\Contracts\Concurrency\Driver;
use Illuminate\Support\Arr;
use Illuminate\Support\Defer\DeferredCallback;
use Illuminate\Support\Str;
use Laravel\SerializableClosure\SerializableClosure;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Producer;
use RdKafka\TopicConf;

use function Illuminate\Support\defer;

class KafkaDriver implements Driver
{
    /**
     * The Kafka producer instance.
     *
     * @var \RdKafka\Producer
     */
    protected $producer;

    /**
     * The Kafka consumer instance.
     *
     * @var \RdKafka\KafkaConsumer
     */
    protected $consumer;

    /**
     * The Kafka topic to send tasks to.
     *
     * @var string
     */
    protected $taskTopic;

    /**
     * The Kafka topic to receive results from.
     *
     * @var string
     */
    protected $resultTopic;

    /**
     * The Kafka topic to send deferred tasks to.
     *
     * @var string
     */
    protected $deferredTopic;

    /**
     * The Kafka bootstrap servers.
     *
     * @var string
     */
    protected $brokers;

    /**
     * The Kafka consumer group ID.
     *
     * @var string
     */
    protected $groupId;

    /**
     * Create a new Kafka based concurrency driver.
     *
     * @param  string  $brokers
     * @param  string  $taskTopic
     * @param  string  $resultTopic
     * @param  string  $deferredTopic
     * @param  string  $groupId
     * @return void
     */
    public function __construct(
        string $brokers = 'localhost:9092',
        string $taskTopic = 'laravel-concurrency-tasks',
        string $resultTopic = 'laravel-concurrency-results',
        string $deferredTopic = 'laravel-concurrency-deferred',
        string $groupId = 'laravel-concurrency-group'
    ) {
        $this->brokers = $brokers;
        $this->taskTopic = $taskTopic;
        $this->resultTopic = $resultTopic;
        $this->deferredTopic = $deferredTopic;
        $this->groupId = $groupId;

        $this->producer = $this->createProducer();
    }

    /**
     * Run the given tasks concurrently and return an array containing the results.
     */
    public function run(Closure|array $tasks): array
    {
        $tasks = Arr::wrap($tasks);
        $taskIds = [];
        $results = [];

        // Create a consumer for the result topic
        $consumer = $this->createConsumer([$this->resultTopic]);

        // Generate task IDs and send tasks to Kafka
        foreach ($tasks as $key => $task) {
            $taskId = Str::uuid()->toString();
            $taskIds[$key] = $taskId;

            // Send task to Kafka
            $this->sendTask($taskId, $task, $this->taskTopic);
        }

        // Wait for results (with timeout)
        $startTime = microtime(true);
        $timeout = 60; // 60 seconds timeout

        while (count($results) < count($tasks) && (microtime(true) - $startTime) < $timeout) {
            // Poll for messages with a 100ms timeout
            $message = $consumer->consume(100);

            if ($message === null) {
                continue;
            }

            // Check if the message is valid
            if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
                $result = json_decode($message->payload, true);

                // Check if this is a result for one of our tasks
                if (isset($result['task_id']) && in_array($result['task_id'], $taskIds)) {
                    $key = array_search($result['task_id'], $taskIds);

                    // Check for errors
                    if (isset($result['error'])) {
                        throw new Exception($result['error']);
                    }

                    // Store the result
                    $results[$key] = unserialize($result['result']);
                }
            }
        }

        // Close the consumer
        $consumer->close();

        // Check for timeout
        if (count($results) < count($tasks)) {
            throw new Exception('Timed out while waiting for concurrent tasks to complete');
        }

        return $results;
    }

    /**
     * Start the given tasks in the background after the current task has finished.
     */
    public function defer(Closure|array $tasks): DeferredCallback
    {
        $tasks = Arr::wrap($tasks);

        return defer(function () use ($tasks) {
            foreach ($tasks as $task) {
                $taskId = Str::uuid()->toString();
                $this->sendTask($taskId, $task, $this->deferredTopic);
            }
        });
    }

    /**
     * Create a Kafka producer instance.
     *
     * @return \RdKafka\Producer
     */
    protected function createProducer()
    {
        $conf = new Conf();
        $conf->set('bootstrap.servers', $this->brokers);

        return new Producer($conf);
    }

    /**
     * Create a Kafka consumer instance.
     *
     * @param  array  $topics
     * @return \RdKafka\KafkaConsumer
     */
    protected function createConsumer(array $topics)
    {
        $conf = new Conf();
        $conf->set('bootstrap.servers', $this->brokers);
        $conf->set('group.id', $this->groupId);
        $conf->set('auto.offset.reset', 'latest');

        $consumer = new KafkaConsumer($conf);
        $consumer->subscribe($topics);

        return $consumer;
    }

    /**
     * Send a task to the specified Kafka topic.
     *
     * @param  string  $taskId
     * @param  \Closure  $task
     * @param  string  $topic
     * @return void
     */
    protected function sendTask(string $taskId, Closure $task, string $topic)
    {
        $kafkaTopic = $this->producer->newTopic($topic);

        $payload = json_encode([
            'task_id' => $taskId,
            'task' => serialize(new SerializableClosure($task)),
        ]);

        $kafkaTopic->produce(RD_KAFKA_PARTITION_UA, 0, $payload, $taskId);
        $this->producer->poll(0);
    }
} 