<?php

namespace Alkhachatryan\Encryptable;

use Illuminate\Support\Facades\Crypt;

trait Encryptable
{
    /**
     * Decrypt data from DB.
     *
     * @param mixed $key
     * @return mixed $value
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (in_array($key, $this->encryptable)) {
            $value = Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * Encrypt data to DB.
     *
     * @param mixed $key
     * @param mixed $value
     * @return string
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->encryptable)) {
            $value = Crypt::encrypt($value);
        }

        return parent::setAttribute($key, $value);
    }
}
