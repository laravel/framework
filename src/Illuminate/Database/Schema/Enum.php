<?php

namespace Illuminate\Database\Schema;

class Enum
{
    public function __construct(
        private string $type,
    )
    {
    }

    public function getAcceptedValues(): array
    {
        $cases = $this->type::cases();

        $arrayOfCasesValues = [];
        foreach ($cases as $case) {
            $arrayOfCasesValues[] = $case->value;
        }

        return $arrayOfCasesValues;
    }
}
