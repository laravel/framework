<?php

namespace Illuminate\Tests\Validation;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Validation\Validator;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Validation\PresenceVerifierInterface;

class ValidationCustomPresenceVerifierTest extends TestCase
{
    protected function tearDown()
    {
        m::close();
    }

    public function testSetConnectionShouldNotBeCalledOnCustomPresenceVerifierWithUniqueRule()
    {
        $translator = m::mock(Translator::class);
        $verifier = $this->getPresenceVerifier();

        $validator = new Validator($translator, ['username' => 'foo'], ['username' => 'unique:users']);
        $validator->setPresenceVerifier($verifier);

        $validator->fails();
    }

    public function testSetConnectionShouldNotBeCalledOnCustomPresenceVerifierWithExistsRule()
    {
        $translator = m::mock(Translator::class);
        $translator->shouldReceive('trans')->twice();
        $verifier = $this->getPresenceVerifier();

        $validator = new Validator($translator, ['username' => 'foo'], ['username' => 'exists:users']);
        $validator->setPresenceVerifier($verifier);

        $validator->fails();
    }

    protected function getPresenceVerifier()
    {
        $verifier = m::mock(PresenceVerifierInterface::class);
        $verifier
            ->shouldReceive('getCount')
            ->once();
        $verifier
            ->shouldReceive('setConnection')
            ->never();
        return $verifier;
    }
}
