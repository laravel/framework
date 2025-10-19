<?php

namespace Illuminate\Tests\Foundation\Http;

use Illuminate\Foundation\Http\Controllers\KeyGenerationController;
use PHPUnit\Framework\TestCase;

class KeyGenerationControllerTest extends TestCase
{
    public function testKeyGenerationControllerExists()
    {
        $this->assertTrue(class_exists(KeyGenerationController::class));

        $controller = new KeyGenerationController();
        $this->assertTrue(method_exists($controller, 'generateKey'));
    }

    public function testKeyGenerationControllerHasCorrectMethodSignature()
    {
        $controller = new KeyGenerationController();
        $reflection = new \ReflectionMethod($controller, 'generateKey');

        $this->assertEquals('generateKey', $reflection->getName());
        $this->assertCount(1, $reflection->getParameters());

        $paramType = $reflection->getParameters()[0]->getType();
        $returnType = $reflection->getReturnType();

        $this->assertInstanceOf(\ReflectionNamedType::class, $paramType);
        $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);

        $this->assertEquals('Illuminate\Http\Request', $paramType->getName());
        $this->assertEquals('Illuminate\Http\JsonResponse', $returnType->getName());
    }

    public function testKeyGenerationControllerCanBeInstantiated()
    {
        $controller = new KeyGenerationController();
        $this->assertInstanceOf(KeyGenerationController::class, $controller);
    }

    public function testKeyGenerationControllerExtendsController()
    {
        $controller = new KeyGenerationController();
        $this->assertInstanceOf(\Illuminate\Routing\Controller::class, $controller);
    }
}
