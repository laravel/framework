<?php

namespace Illuminate\Log\Context\Contracts;

interface Contextable
{
    /**
     * The data to append to your log output.
     *
     * @param  \Illuminate\Log\Context\Repository  $repository
     * @return array<string, mixed>|null
     */
    public function context($repository);
}
