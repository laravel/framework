<?php

namespace Illuminate\Tests\Validation;

use BadMethodCallException;
use Illuminate\Validation\RulesPreset;
use PHPUnit\Framework\TestCase;

class ValidationRulesPresetTest extends TestCase
{
    public function testMakeMethodCreatesARulesPreset()
    {
        $this->assertInstanceOf(UserRulesPreset::class, UserRulesPreset::make());
    }

    public function testInvokingPresetNameWillInstanciateAnObjectAndSelectsThePreset()
    {
        $this->assertEquals(['foo' => ['required']], DummyPreset::foo()->rules());
        $this->assertEquals(['bar' => ['required']], DummyPreset::bar()->rules());

        try {
            DummyPreset::baz()->rules();
        } catch (BadMethodCallException $e) {
            $this->assertEquals("Method Illuminate\Tests\Validation\DummyPreset::presetBaz does not exist.", $e->getMessage());

            return;
        }

        $this->fail('Expected BadMethodCallException to be thrown, because there is no baz preset !');
    }

    public function testSelectingPresets()
    {
        $instance = DummyPreset::foo();
        $this->assertEquals(['foo' => ['required']], DummyPreset::foo()->rules());
        $this->assertEquals(['bar' => ['required']], $instance->preset('bar')->rules());
        $this->assertEquals(['foo' => ['required']], $instance->preset('foo')->rules());

        $this->assertSame($instance, $instance->preset('foo'));
    }

    public function testRulesMethod()
    {
        $this->assertEquals(
            [
                'name' => ['required', 'max:191'],
                'username' => ['required', 'unique:users,username'],
                'password' => ['required', 'confirmed', 'min:8'],
                'email' => ['email', 'confirmed'],
            ],
            UserRulesPreset::create()->rules([
                'name' => 'max:191',
                'password' => ['confirmed', 'min:8'],
                'email' => ['email', 'confirmed'],
            ])
        );

        $this->assertEquals(
            [
                'name' => ['required'],
                'username' => ['required', 'unique:users,username'],
                'password' => 'required',
            ],
            UserRulesPreset::create()->rules()
        );
    }
}

class DummyPreset extends RulesPreset
{
    public function presetFoo()
    {
        return [
            'foo' => ['required'],
        ];
    }

    public function presetBar()
    {
        return [
            'bar' => ['required'],
        ];
    }
}

class UserRulesPreset extends RulesPreset
{
    public function presetCreate()
    {
        return [
            'name' => ['required'],
            'username' => ['required', 'unique:users,username'],
            'password' => 'required',
        ];
    }
}
