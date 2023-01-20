<?php

namespace Illuminate\Tests\Database;

use Illuminate\Contracts\Support\Arrayable;

enum StringStatus: string
{
    case draft = 'draft';
    case pending = 'pending';
    case done = 'done';
}

enum IntegerStatus: int
{
    case draft = 0;
    case pending = 1;
    case done = 2;
}

enum ArrayableStatus: string implements Arrayable
{
    case pending = 'pending';
    case done = 'done';

    public function description(): string
    {
        return match ($this) {
            self::pending => 'pending status description',
            self::done => 'done status description'
        };
    }

    public function toArray()
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'description' => $this->description(),
        ];
    }
}
