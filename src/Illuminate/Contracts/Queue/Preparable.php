<?php

namespace Illuminate\Contracts\Queue;

interface Preparable
{
    /**
     * Run preparation logic before dispatch. Return false to abort.
     *
     * @return bool|void
     */
    public function prepare();
}
