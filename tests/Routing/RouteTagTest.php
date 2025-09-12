<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;

class RouteTagTest extends TestCase
{
    public function testCanAddSingleTag()
    {
        $route = new Route(['GET'], '/test', []);
        $route->tag('api');
        
        $this->assertTrue($route->hasTag('api'));
        $this->assertEquals(['api'], $route->getTags());
    }
    
    public function testCanAddMultipleTags()
    {
        $route = new Route(['GET'], '/test', []);
        $route->tags(['api', 'public']);
        
        $this->assertTrue($route->hasTag('api'));
        $this->assertTrue($route->hasTag('public'));
        $this->assertEquals(['api', 'public'], $route->getTags());
    }
    
    public function testCanChainTagMethods()
    {
        $route = new Route(['GET'], '/test', []);
        $result = $route->tag('api')->tags(['public', 'v1']);
        
        $this->assertSame($route, $result);
        $this->assertEquals(['api', 'public', 'v1'], $route->getTags());
    }
    
    public function testTagsAreUnique()
    {
        $route = new Route(['GET'], '/test', []);
        $route->tag('api')->tag('api');
        
        $this->assertEquals(['api'], $route->getTags());
    }
    
    public function testHasAnyTag()
    {
        $route = new Route(['GET'], '/test', []);
        $route->tags(['api', 'public']);
        
        $this->assertTrue($route->hasAnyTag(['api', 'admin']));
        $this->assertFalse($route->hasAnyTag(['admin', 'private']));
    }
    
    public function testHasAllTags()
    {
        $route = new Route(['GET'], '/test', []);
        $route->tags(['api', 'public', 'v1']);
        
        $this->assertTrue($route->hasAllTags(['api', 'public']));
        $this->assertFalse($route->hasAllTags(['api', 'admin']));
    }
    
    public function testCanRemoveTag()
    {
        $route = new Route(['GET'], '/test', []);
        $route->tags(['api', 'public'])->withoutTag('api');
        
        $this->assertFalse($route->hasTag('api'));
        $this->assertTrue($route->hasTag('public'));
        $this->assertEquals(['public'], $route->getTags());
    }
}