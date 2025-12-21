<?php

namespace Illuminate\Tests\Database\stubs;

class TestValueObject
{
    private string $myPropertyA;
    private string $myPropertyB;

    public static function make(?array $test): self
    {
        $self = new self;
        if (! empty($test['myPropertyA'])) {
            $self->myPropertyA = $test['myPropertyA'];
        }
        if (! empty($test['myPropertyB'])) {
            $self->myPropertyB = $test['myPropertyB'];
        }

        return $self;
    }

    public function toArray(): array
    {
        if (isset($this->myPropertyA)) {
            $result['myPropertyA'] = $this->myPropertyA;
        }
        if (isset($this->myPropertyB)) {
            $result['myPropertyB'] = $this->myPropertyB;
        }

        return $result ?? [];
    }
}
