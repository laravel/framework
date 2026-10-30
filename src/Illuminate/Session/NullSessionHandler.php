<?php

namespace Illuminate\Session;

use RuntimeException;
use SessionHandlerInterface;

class NullSessionHandler implements SessionHandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function open($savePath, $sessionName): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function create_sid(): string
    {
        return session_create_id() ?: throw new RuntimeException('Unable to create a session ID.');
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function validateId($id): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function read($sessionId): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function write($sessionId, $data): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function destroy($sessionId): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function gc($lifetime): int
    {
        return 0;
    }
}
