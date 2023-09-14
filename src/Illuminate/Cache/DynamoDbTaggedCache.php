<?php

namespace Illuminate\Cache;

use Aws\DynamoDb\Exception\DynamoDbException;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\InteractsWithTime;

class DynamoDbTaggedCache extends TaggedCache
{
    use InteractsWithTime;

    /**
     * @var DynamoDbStore $store
     */
    protected $store;
    protected DynamoDbStoreConfig $config;
    protected string $tagAttribute = 'TagAttribute';

    /**
     * Create a new tagged cache instance.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @param  \Illuminate\Cache\TagSet  $tags
     * @return void
     */
    public function __construct(
        Store $store, 
        DynamoDbStoreConfig $config, 
        TagSet $tags
    ) {
        parent::__construct($store, $tags);

        $this->config = $config;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param \DateTimeInterface|\DateInterval|int|null  $ttl
     * 
     * @return bool
     */
    public function put($key, $value, $ttl = null)
    {
        $this->store->getClient()->putItem([
            'TableName' => $this->config->getTableName(),
            'Item' => [
                $this->config->getKeyAttribute() => [
                    'S' => $this->store->getPrefix().$key,
                ],
                $this->config->getValueAttribute() => [
                    $this->type($value) => $this->serialize($value),
                ],
                $this->config->getExpirationAttribute() => [
                    'N' => (string) $this->toTimestamp($ttl),
                ],
                $this->tagAttribute => [
                    'S' => implode(',', $this->getTags()->getNames())
                ]
            ],
        ]);

        return true;
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * @param  array  $values
     * @param  int|null  $seconds
     * @return bool
     */
    public function putMany(array $values, $ttl = null)
    {
        if (count($values) === 0) {
            return true;
        }

        $expiration = $ttl ? $this->toTimestamp($ttl) : Carbon::now()->addYears(5)->getTimestamp();

        $this->store->getClient()->batchWriteItem([
            'RequestItems' => [
                $this->config->getTableName() => collect($values)->map(function ($value, $key) use ($expiration) {
                    return [
                        'PutRequest' => [
                            'Item' => [
                                $this->config->getKeyAttribute() => [
                                    'S' => $this->config->getPrefix().$key,
                                ],
                                $this->config->getValueAttribute() => [
                                    $this->type($value) => $this->serialize($value),
                                ],
                                $this->config->getExpirationAttribute() => [
                                    'N' => (string) $expiration,
                                ],
                                $this->tagAttribute => [
                                    'S' => implode(',', $this->getTags()->getNames())
                                ]
                            ],
                        ],
                    ];
                })->values()->all(),
            ],
        ]);

        return true;
    }

    /**
     * Store an item in the cache if the key does not exist.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * 
     * @return bool
     */
    public function add($key, $value, $ttl = null)
    {
        try {
            $this->store->getClient()->putItem([
                'TableName' => $this->config->getTableName(),
                'Item' => [
                    $this->config->getKeyAttribute() => [
                        'S' => $this->config->getPrefix().$key,
                    ],
                    $this->config->getValueAttribute() => [
                        $this->type($value) => $this->serialize($value),
                    ],
                    $this->config->getExpirationAttribute() => [
                        'N' => (string) $this->toTimestamp($ttl),
                    ],
                    $this->tagAttribute => [
                        'S' => implode(',', $this->getTags()->getNames())
                    ]
                ],
                'ConditionExpression' => 'attribute_not_exists(#key) OR #expires_at < :now',
                'ExpressionAttributeNames' => [
                    '#key' => $this->config->getKeyAttribute(),
                    '#expires_at' => $this->config->getExpirationAttribute(),
                ],
                'ExpressionAttributeValues' => [
                    ':now' => [
                        'N' => (string) Carbon::now()->getTimestamp(),
                    ],
                ],
            ]);

            return true;
        } catch (DynamoDbException $e) {
            if (str_contains($e->getMessage(), 'ConditionalCheckFailed')) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  array|string  $key
     * @param  mixed $default
     * @return mixed
     */
    public function get($key, $default = null): mixed
    {
        return $this->store->get($key);
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        return $this->store->increment($key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        return $this->store->decrement($key, $value);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever($key, $value)
    {
        return $this->put($key, $value, Carbon::now()->addYears(5)->getTimestamp());
    }

    /**
     * Flushing items matching the tag
     */
    public function flush()
    {
        $keys = [];
        $exclusiveStartKey = null;

        do {
            $scanRequest = [
                'TableName' => $this->config->getTableName(),
                'FilterExpression' => 'contains(#attr, :search)',
                'ProjectionExpression' => $this->config->getKeyAttribute(),
                'ExpressionAttributeNames' => [
                    "#attr" => $this->tagAttribute
                ],
                'ExpressionAttributeValues' => [
                    ':search' => [
                        'S' => implode(',', $this->getTags()->getNames())
                    ]
                ],
                'Select' => 'SPECIFIC_ATTRIBUTES'
            ];

            if ($exclusiveStartKey) {
                $scanRequest['ExclusiveStartKey'] = $exclusiveStartKey;
            }

            $response = $this->store->getClient()->scan($scanRequest);
            $exclusiveStartKey = $response->get('LastEvaluatedKey') ?? null;

            $keys = array_merge($keys, array_map(function ($item) {
                return $item[$this->config->getKeyAttribute()]['S'];
            }, $response->get('Items')));
        } while($exclusiveStartKey);

        $this->store->batchDelete($keys);

        return true;
    }

    /**
     * Serialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function serialize($value)
    {
        return is_numeric($value) ? (string) $value : serialize($value);
    }

    /**
     * Unserialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function unserialize($value)
    {
        if (filter_var($value, FILTER_VALIDATE_INT) !== false) {
            return (int) $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return unserialize($value);
    }

    /**
     * Get the DynamoDB type for the given value.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function type($value)
    {
        return is_numeric($value) ? 'N' : 'S';
    }

    /**
     * Get the UNIX timestamp for the given number of seconds.
     *
     * @param  int  $seconds
     * @return int
     */
    protected function toTimestamp($seconds)
    {
        return $seconds > 0
                    ? $this->availableAt($seconds)
                    : Carbon::now()->getTimestamp();
    }
}
