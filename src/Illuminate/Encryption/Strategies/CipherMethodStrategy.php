<?php
/**
 * Created by PhpStorm.
 * User: serabalint
 * Date: 2018. 05. 26.
 * Time: 19:14
 */

namespace Illuminate\Encryption\Strategies;


interface CipherMethodStrategy
{
    public function getCipher() :string;
    public function getKey() :string;
    public function generateKey();
    public function supported();
}