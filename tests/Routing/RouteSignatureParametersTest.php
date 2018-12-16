<?php

namespace Illuminate\Tests\Routing;

use ReflectionParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;
use Illuminate\Routing\RouteSignatureParameters;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

if (! class_exists(RouteSignatureParametersTestController::class)) {
    class RouteSignatureParametersTestController
    {
        public function show($id)
        {
        }
    }
}

class RouteSignatureParametersTest extends TestCase
{
    public function actionProvider()
    {
        return [
            [
                RouteSignatureParametersTestController::class.'@show',
                ['id'],
            ],
            [
                function ($one) {
                },
                ['one'],
            ],
            [
                function () {
                },
                [],
            ],
            [
                new class {
                    public function __invoke($one)
                    {
                    }
                },
                ['one'],
            ],
        ];
    }

    /**
     * @dataProvider actionProvider
     * @param mixed $action
     * @param array $expectedNames
     */
    public function testFromActionFindsParameters($action, array $expectedNames)
    {
        /* @var ReflectionParameter[] $parameters */
        $parameters = RouteSignatureParameters::fromAction([
            'uses' => $action,
        ]);

        $this->assertEquals(count($expectedNames), count($parameters));

        foreach ($expectedNames as $idx => $name) {
            $this->assertEquals($name, $parameters[$idx]->getName());
        }
    }

    public function testFromActionWithSubclassFilter()
    {
        $parameters = RouteSignatureParameters::fromAction([
            'uses' => function (Request $response) {
            },
        ], SymfonyRequest::class);

        $this->assertCount(1, $parameters);
    }

    public function testFromActionWithFailingSubclassFilter()
    {
        $parameters = RouteSignatureParameters::fromAction([
            'uses' => function (Request $response) {
            },
        ], Response::class);

        $this->assertEmpty($parameters);
    }
}
