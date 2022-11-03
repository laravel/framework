<?php

namespace Illuminate\Auth\Access;

interface DefinesAbilities
{
    /**
     * @return array<string, callable>
     */
    public function abilities();
}
