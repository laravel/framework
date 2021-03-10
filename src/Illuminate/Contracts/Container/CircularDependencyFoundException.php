<?php

namespace Illuminate\Contracts\Container;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class CircularDependencyFoundException extends Exception implements ContainerExceptionInterface
{
    //
}
