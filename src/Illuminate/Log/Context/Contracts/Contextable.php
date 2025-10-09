<?php

namespace Illuminate\Log\Context\Contracts;

interface Contextable
{
    /**
     * The data to append to your log output.
     *
     * @return array<string, mixed>
     */
    public function contextData();
}
