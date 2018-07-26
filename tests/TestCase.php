<?php

class TestCase extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected $mockObjects;

    protected $mockObjectGenerator;

    /**
     * @param $originalClassName
     * @param array $methods
     * @param array $arguments
     * @param string $mockClassName
     * @param bool $callOriginalConstructor
     * @param bool $callOriginalClone
     * @param bool $callAutoload
     * @param bool $cloneArguments
     * @param bool $callOriginalMethods
     * @param null $proxyTarget
     * @return mixed
     * @throws ReflectionException
     */
    public function getMock($originalClassName, $methods = array(), array $arguments = array(), $mockClassName = '', $callOriginalConstructor = true, $callOriginalClone = true, $callAutoload = true, $cloneArguments = false, $callOriginalMethods = false, $proxyTarget = null)
    {
        $ref = new ReflectionClass($originalClassName);

        if ($ref->isTrait()) {
            return $this->getMockForTrait($originalClassName, $arguments, $mockClassName, $callOriginalConstructor, $callOriginalClone, $callAutoload, $methods, $cloneArguments);
        }

        if ($ref->isAbstract()) {
            return $this->getMockForAbstractClass($originalClassName, $arguments, $mockClassName, $callOriginalConstructor, $callOriginalClone, $callAutoload, $methods, $cloneArguments);
        }

        $mock = $this->getMockBuilder($originalClassName)
            ->setMethods($methods)
            ->setConstructorArgs($arguments)
            ->setMockClassName($mockClassName);

        $callOriginalConstructor ? $mock->enableOriginalConstructor() : $mock->disableOriginalConstructor();
        $callOriginalClone ? $mock->enableOriginalClone() : $mock->disableOriginalClone();
        $callAutoload ? $mock->enableAutoload() : $mock->disableAutoload();
        $cloneArguments ? $mock->enableOriginalClone() : $mock->disableOriginalClone();
        return $mock->getMock();
    }
}