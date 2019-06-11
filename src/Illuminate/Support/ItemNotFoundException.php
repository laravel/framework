<?php


namespace Illuminate\Support;


use RuntimeException;

class ItemNotFoundException extends RuntimeException
{
    protected $message = 'No query results for item';
}
