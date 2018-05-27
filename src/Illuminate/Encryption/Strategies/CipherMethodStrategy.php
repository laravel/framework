<?php

namespace Illuminate\Encryption\Strategies;

interface CipherMethodStrategy
{
    public function getCipher() :string;

    public function getKey() :string;

    public function generateKey();

    public function supported();

}