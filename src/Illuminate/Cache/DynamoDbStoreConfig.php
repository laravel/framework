<?php

namespace Illuminate\Cache;

class DynamoDbStoreConfig
{
    private string $tableName;
    private string $keyAttribute;
    private string $prefix;
    private string $valueAttribute;
    private string $expirationAttribute;

    public function __construct(
        string $tableName,
        string $keyAttribute,
        string $prefix,
        string $valueAttribute,
        string $expirationAttribute
    )
    {
        $this->tableName = $tableName;
        $this->keyAttribute = $keyAttribute;
        $this->prefix = $prefix;
        $this->valueAttribute = $valueAttribute;
        $this->expirationAttribute = $expirationAttribute;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getKeyAttribute(): string
    {
        return $this->keyAttribute;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getValueAttribute(): string
    {
        return $this->valueAttribute;
    }

    public function getExpirationAttribute(): string
    {
        return $this->expirationAttribute;
    }
}
