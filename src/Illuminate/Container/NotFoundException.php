<?php

namespace Illuminate\Container;

use Exception;
use Psr\Container\Exception\NotFoundException as NotFoundExceptionContract;

class NotFoundException extends Exception implements NotFoundExceptionContract
{
    //
}
