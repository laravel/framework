<?php

namespace Illuminate\Encryption;

use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use Symfony\Component\Security\Core\Util\StringUtils;
use Symfony\Component\Security\Core\Util\SecureRandom;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class Encrypter implements EncrypterContract
{
    /**
     * The encryption key.
     *
     * @var string
     */
    protected $key;

    /**
     * The algorithm used for encryption.
     *
     * @var string
     */
    protected $cipher = 'AES-128-CBC';

    /**
     * The mode used for encryption.
     *
     * @var string
     */
    protected $mode;

    /**
     * The block size of the cipher.
     *
     * @var int
     */
    protected $block = 16;

    /**
     * Create a new encrypter instance.
     *
     * @param  string  $key
     * @return void
     */
    public function __construct($key)
    {
        $this->key = (string) $key;
    }

    /**
     * Encrypt the given value.
     *
     * @param  string  $value
     * @return string
     */
    public function encrypt($value)
    {
        $iv = openssl_random_pseudo_bytes($this->getIvSize());

        $value = base64_encode($this->padAndEncrypt($value, $iv));

        // Once we have the encrypted value we will go ahead base64_encode the input
        // vector and create the MAC for the encrypted value so we can verify its
        // authenticity. Then, we'll JSON encode the data in a "payload" array.
        $mac = $this->hash($iv = base64_encode($iv), $value);

        return base64_encode(json_encode(compact('iv', 'value', 'mac')));
    }

    /**
     * Pad and encrypt on the given value and input vector.
     *
     * @param  string  $value
     * @param  string  $iv
     * @return string
     */
    protected function padAndEncrypt($value, $iv)
    {
        $value = $this->addPadding(serialize($value));

        return openssl_encrypt($value, $this->cipher, $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
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

        return unserialize($this->stripPadding($this->opensslDecrypt($value, $iv)));
    }

    /**
     * Run the openssl decryption routine for the value.
     *
     * @param  string  $value
     * @param  string  $iv
     * @return string
     *
     * @throws \Exception
     */
    protected function opensslDecrypt($value, $iv)
    {
        try {
            return openssl_decrypt($value, $this->cipher, $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        } catch (Exception $e) {
            throw new DecryptException($e->getMessage());
        }
    }

    /**
     * Get the JSON array from the given payload.
     *
     * @param  string  $payload
     * @return array
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    protected function getJsonPayload($payload)
    {
        $payload = json_decode(base64_decode($payload), true);

        // If the payload is not valid JSON or does not have the proper keys set we will
        // assume it is invalid and bail out of the routine since we will not be able
        // to decrypt the given value. We'll also check the MAC for this encryption.
        if (!$payload || $this->invalidPayload($payload)) {
            throw new DecryptException('Invalid data.');
        }

        if (!$this->validMac($payload)) {
            throw new DecryptException('MAC is invalid.');
        }

        return $payload;
    }

    /**
     * Determine if the MAC for the given payload is valid.
     *
     * @param  array  $payload
     * @return bool
     *
     * @throws \RuntimeException
     */
    protected function validMac(array $payload)
    {
        $bytes = (new SecureRandom)->nextBytes(16);

        $calcMac = hash_hmac('sha256', $this->hash($payload['iv'], $payload['value']), $bytes, true);

        return StringUtils::equals(hash_hmac('sha256', $payload['mac'], $bytes, true), $calcMac);
    }

    /**
     * Create a MAC for the given value.
     *
     * @param  string  $iv
     * @param  string  $value
     * @return string
     */
    protected function hash($iv, $value)
    {
        return hash_hmac('sha256', $iv.$value, $this->key);
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
     * Verify that the encryption payload is valid.
     *
     * @param  array|mixed  $data
     * @return bool
     */
    protected function invalidPayload($data)
    {
        return !is_array($data) || !isset($data['iv']) || !isset($data['value']) || !isset($data['mac']);
    }

    /**
     * Get the IV size for the cipher.
     *
     * @return int
     */
    protected function getIvSize()
    {
        return openssl_cipher_iv_length($this->cipher);
    }

    /**
     * Set the encryption key.
     *
     * @param  string  $key
     * @return void
     */
    public function setKey($key)
    {
        $this->key = (string) $key;
    }

    /**
     * Set the encryption cipher.
     *
     * @param  string  $cipher
     * @return void
     */
    public function setCipher($cipher)
    {
        $this->cipher = $cipher;

        $this->updateBlockSize();
    }

    /**
     * Set the encryption mode.
     *
     * @param  string  $mode
     * @return void
     */
    public function setMode($mode)
    {
        $this->cipher = $mode;

        $this->updateBlockSize();
    }

    /**
     * Update the block size for the current cipher and mode.
     *
     * @return void
     */
    protected function updateBlockSize()
    {
        $this->block = openssl_cipher_iv_length($this->cipher);
    }
}
