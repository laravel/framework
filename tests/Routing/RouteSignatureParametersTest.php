<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\RouteSignatureParameters;
use Laravel\SerializableClosure\SerializableClosure;
use Opis\Closure\SerializableClosure as OpisSerializableClosure;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;

class RouteSignatureParametersTest extends TestCase
{
    public function testItCanExtractTheRouteActionSignatureParameters()
    {
        $callable = function (SignatureParametersUser $user) {
            return $user;
        };

        $action = ['uses' => serialize(\PHP_VERSION_ID < 70400
            ? new OpisSerializableClosure($callable)
            : new SerializableClosure($callable)
        )];

        $parameters = RouteSignatureParameters::fromAction($action);

        $this->assertContainsOnlyInstancesOf(ReflectionParameter::class, $parameters);
        $this->assertSame('user', $parameters[0]->getName());
    }
}

class SignatureParametersUser extends Model
{
    //
}
