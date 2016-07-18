<?php

namespace Illuminate\Redis;

use Predis\Client as BaseClient;

class Client extends BaseClient
{
    /**
     * {@inheritdoc}
     */
    protected function createConnection($parameters)
    {
        return new Connection(parent::createConnection($parameters));
    }
}
