<?php

namespace Illuminate\Tests\App\ValueObjects;

final class Decimal
{
    private $value;
    private $scale;

    public function __construct($value)
    {
        $parts = explode('.', (string) $value);

        $this->scale = strlen($parts[1]);
        $this->value = (int) str_replace('.', '', $value);
    }

    public function getValue()
    {
        return $this->value;
    }

    public function __toString()
    {
        return substr_replace($this->value, '.', -$this->scale, 0);
    }
}
