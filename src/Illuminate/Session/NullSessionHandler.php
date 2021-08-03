<?php

namespace Illuminate\Session;

use SessionHandlerInterface;

class NullSessionHandler implements SessionHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function read($sessionId)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function write($sessionId, $data)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function destroy($sessionId)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function gc($lifetime)
    {
        return true;
    }
}
