<?php

namespace Illuminate\Queue\Connectors;

use IronMQ\IronMQ;
use Illuminate\Http\Request;
use Illuminate\Queue\IronQueue;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class IronConnector implements ConnectorInterface
{
    /**
     * The encrypter instance.
     *
     * @var \Illuminate\Encryption\Encrypter
     */
    protected $crypt;

    /**
     * The current request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Create a new Iron connector instance.
     *
     * @param  \Illuminate\Contracts\Encryption\Encrypter  $crypt
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(EncrypterContract $crypt, Request $request)
    {
        $this->crypt = $crypt;
        $this->request = $request;
    }

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $ironConfig = ['token' => $config['token'], 'project_id' => $config['project']];

        if (isset($config['host'])) {
            $ironConfig['host'] = $config['host'];
        }

        $iron = new IronMQ($ironConfig);

        if (isset($config['ssl_verifypeer'])) {
            $iron->ssl_verifypeer = $config['ssl_verifypeer'];
        }

        return new IronQueue($iron, $this->request, $config['queue'], $config['encrypt']);
    }
}
