<?php

namespace Illuminate\Encryption;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

/**
 * @deprecated since version 5.1. Use Illuminate\Encryption\Encrypter.
 */
class McryptEncrypter extends BaseEncrypter implements EncrypterContract
{
    /**
     * The algorithm used for encryption.
     *
     * @var string
     */
    protected $cipher;

    /**
     * The block size of the cipher.
     *
     * @var int
     */
    protected $block;

    /**
     * Create a new encrypter instance.
     *
     * @param  string  $key
     * @param  int     $cipher
     * @return void
     */
    public function __construct($key, $cipher = MCRYPT_RIJNDAEL_128)
    {
        $key = (string) $key;

        if (static::supported($key, $cipher)) {
            $this->key = $key;
            $this->cipher = $cipher;
            $this->block = mcrypt_get_iv_size($this->cipher, MCRYPT_MODE_CBC);
        } else {
            throw new RuntimeException('The only supported ciphers are MCRYPT_RIJNDAEL_128 and MCRYPT_RIJNDAEL_256.');
        }
    }

    /**
     * Determine if the given key and cipher combination is valid.
     *
     * @param  string  $key
     * @param  string  $cipher
     * @return bool
     */
    public static function supported($key, $cipher)
    {
        return defined('MCRYPT_RIJNDAEL_128') &&
                ($cipher === MCRYPT_RIJNDAEL_128 || $cipher === MCRYPT_RIJNDAEL_256);
    }

    /**
     * Encrypt the given value.
     *
     * @param  string  $value
     * @return string
     */
    public function encrypt($value)
    {
        $iv = mcrypt_create_iv($this->getIvSize(), $this->getRandomizer());

        $value = base64_encode($this->padAndMcrypt($value, $iv));

        // Once we have the encrypted value we will go ahead base64_encode the input
        // vector and create the MAC for the encrypted value so we can verify its
        // authenticity. Then, we'll JSON encode the data in a "payload" array.
        $mac = $this->hash($iv = base64_encode($iv), $value);

        return base64_encode(json_encode(compact('iv', 'value', 'mac')));
    }

    /**
     * Pad and use mcrypt on the given value and input vector.
     *
     * @param  string  $value
     * @param  string  $iv
     * @return string
     */
    protected function padAndMcrypt($value, $iv)
    {
        $value = $this->addPadding(serialize($value));

        return mcrypt_encrypt($this->cipher, $this->key, $value, MCRYPT_MODE_CBC, $iv);
    }

    /**
     * Decrypt the given value.
     *
     * @param  string  $payload
     * @return string
     */
    public function decrypt($payload)
    {
        $payload = $this->getJsonPayload($payload);

        // We'll go ahead and remove the PKCS7 padding from the encrypted value before
        // we decrypt it. Once we have the de-padded value, we will grab the vector
        // and decrypt the data, passing back the unserialized from of the value.
        $value = base64_decode($payload['value']);

        $iv = base64_decode($payload['iv']);

        return unserialize($this->stripPadding($this->mcryptDecrypt($value, $iv)));
    }

    /**
     * Run the mcrypt decryption routine for the value.
     *
     * @param  string  $value
     * @param  string  $iv
     * @return string
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    protected function mcryptDecrypt($value, $iv)
    {
        try {
            return mcrypt_decrypt($this->cipher, $this->key, $value, MCRYPT_MODE_CBC, $iv);
        } catch (Exception $e) {
            throw new DecryptException($e->getMessage());
        }
    }

    /**
     * Add PKCS7 padding to a given value.
     *
     * @param  string  $value
     * @return string
     */
    protected function addPadding($value)
    {
        $pad = $this->block - (strlen($value) % $this->block);

        return $value.str_repeat(chr($pad), $pad);
    }

    /**
     * Remove the padding from the given value.
     *
     * @param  string  $value
     * @return string
     */
    protected function stripPadding($value)
    {
        $pad = ord($value[($len = strlen($value)) - 1]);

        return $this->paddingIsValid($pad, $value) ? substr($value, 0, $len - $pad) : $value;
    }

    /**
     * Determine if the given padding for a value is valid.
     *
     * @param  string  $pad
     * @param  string  $value
     * @return bool
     */
    protected function paddingIsValid($pad, $value)
    {
        $beforePad = strlen($value) - $pad;

        return substr($value, $beforePad) == str_repeat(substr($value, -1), $pad);
    }

    /**
     * Get the IV size for the cipher.
     *
     * @return int
     */
    protected function getIvSize()
    {
        return mcrypt_get_iv_size($this->cipher, MCRYPT_MODE_CBC);
    }

    /**
     * Get the random data source available for the OS.
     *
     * @return int
     */
    protected function getRandomizer()
    {
        if (defined('MCRYPT_DEV_URANDOM')) {
            return MCRYPT_DEV_URANDOM;
        }

        if (defined('MCRYPT_DEV_RANDOM')) {
            return MCRYPT_DEV_RANDOM;
        }

        mt_srand();

        return MCRYPT_RAND;
    }
}
