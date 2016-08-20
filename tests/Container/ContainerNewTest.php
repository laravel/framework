<?php

use Illuminate\Container\Container;

class ContainerContainerNewTest extends PHPUnit_Framework_TestCase
{

    public function testAbstractConversion()
    {
        $container = new Container();
        $ret = $container->bind(\Datetime::class);

        $this->assertEquals($ret, "Datetime");
    }

}
