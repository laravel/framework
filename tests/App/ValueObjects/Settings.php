<?php

namespace Illuminate\Tests\App\ValueObjects;

class Settings
{
    public ?bool $foo;
    public ?bool $bar;

    public function __construct(?bool $foo, ?bool $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }

    public static function from(array $data): Settings
    {
        return new self(
            $data['foo'] ?? null,
            $data['bar'] ?? null,
        );
    }

    public function toJson($options = 0): string
    {
        return json_encode(['foo' => $this->foo, 'bar' => $this->bar], $options);
    }
}
