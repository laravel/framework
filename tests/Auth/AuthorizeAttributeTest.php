<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\Attributes\Authorize;
use PHPUnit\Framework\TestCase;

class AuthorizeAttributeTest extends TestCase
{
    public function testAttributeCanBeInstantiatedWithAbilityOnly()
    {
        $attribute = new Authorize('update-post');

        $this->assertEquals('update-post', $attribute->ability);
        $this->assertEquals([], $attribute->models);
    }

    public function testAttributeCanBeInstantiatedWithAbilityAndModels()
    {
        $attribute = new Authorize('update-post', 'post', 'user');

        $this->assertEquals('update-post', $attribute->ability);
        $this->assertEquals(['post', 'user'], $attribute->models);
    }

    public function testAttributeCanBeInstantiatedWithAbilityAndModelArray()
    {
        $attribute = new Authorize('update-post', ['post', 'user']);

        $this->assertEquals('update-post', $attribute->ability);
        $this->assertEquals(['post', 'user'], $attribute->models);
    }

    public function testAttributeCanBeInstantiatedWithAbilityAndSingleModel()
    {
        $attribute = new Authorize('update-post', 'post');

        $this->assertEquals('update-post', $attribute->ability);
        $this->assertEquals(['post'], $attribute->models);
    }
}
