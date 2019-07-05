<?php

namespace Illuminate\Session;

use Illuminate\Encryption\EncryptionManager;
use SessionHandlerInterface;
use Illuminate\Contracts\Encryption\DecryptException;

class EncryptedStore extends Store
{
    /**
     * The encrypter instance.
     *
     * @var \Illuminate\Encryption\EncryptionManager
     */
    protected $encrypter;

    /**
     * Create a new session instance.
     *
     * @param  string $name
     * @param  \SessionHandlerInterface $handler
     * @param  \Illuminate\Encryption\EncryptionManager $encrypter
     * @param  string|null $id
     * @return void
     */
    public function __construct($name, SessionHandlerInterface $handler, EncryptionManager $encrypter, $id = null)
    {
        $this->encrypter = $encrypter;

        parent::__construct($name, $handler, $id);
    }

    /**
     * Prepare the raw string data from the session for unserialization.
     *
     * @param  string  $data
     * @return string
     */
    protected function prepareForUnserialize($data)
    {
        try {
            return $this->encrypter->decrypt($data);
        } catch (DecryptException $e) {
            return serialize([]);
        }
    }

    /**
     * Prepare the serialized session data for storage.
     *
     * @param  string  $data
     * @return string
     */
    protected function prepareForStorage($data)
    {
        return $this->encrypter->encrypt($data);
    }

    /**
     * Get the encrypter instance.
     *
     * @return \Illuminate\Encryption\EncryptionManager
     */
    public function getEncrypter()
    {
        return $this->encrypter;
    }
}
