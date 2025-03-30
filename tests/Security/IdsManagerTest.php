<?php

namespace Illuminate\Tests\Security;

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Security\Events\ThreatDetected;
use Illuminate\Security\IdsManager;
use Illuminate\Security\IdsSensor;
use PHPUnit\Framework\TestCase;

class IdsManagerTest extends TestCase
{
    public function testCanAddSensor()
    {
        $container = new Container;
        $manager = new IdsManager($container);

        // Create a mock sensor
        $sensor = $this->createMock(IdsSensor::class);
        $sensor->method('getName')->willReturn('test-sensor');

        $manager->addSensor($sensor);

        $this->assertCount(1, $manager->getSensors());
        $this->assertArrayHasKey('test-sensor', $manager->getSensors());
    }

    public function testCanAnalyzeRequest()
    {
        $container = new Container;
        $manager = new IdsManager($container);

        // Create a sensor that always detects a threat
        $sensor = $this->createMock(IdsSensor::class);
        $sensor->method('detect')->willReturn(true);
        $sensor->method('getWeight')->willReturn(5);
        $sensor->method('getName')->willReturn('TestSensor');
        $sensor->method('getDescription')->willReturn('Test Sensor Description');

        $manager->addSensor($sensor);

        $request = Request::create('/', 'GET');

        $this->assertTrue($manager->analyze($request));
        $this->assertEquals(5, $manager->getThreatScore());
        $this->assertCount(1, $manager->getDetectedThreats());
    }

    public function testNoThreatDetected()
    {
        $container = new Container;
        $manager = new IdsManager($container);

        // Create a sensor that doesn't detect threats
        $sensor = $this->createMock(IdsSensor::class);
        $sensor->method('detect')->willReturn(false);
        $sensor->method('getName')->willReturn('test-sensor');

        $manager->addSensor($sensor);

        $request = Request::create('/', 'GET');

        $this->assertFalse($manager->analyze($request));
        $this->assertEquals(0, $manager->getThreatScore());
        $this->assertCount(0, $manager->getDetectedThreats());
    }

    public function testCanSetThreshold()
    {
        $container = new Container;
        $manager = new IdsManager($container);

        // Create a sensor with weight 1
        $sensor = $this->createMock(IdsSensor::class);
        $sensor->method('detect')->willReturn(true);
        $sensor->method('getWeight')->willReturn(1);
        $sensor->method('getName')->willReturn('test-sensor');

        $manager->addSensor($sensor);

        // Set threshold to 2
        $manager->setThreshold(2);

        $request = Request::create('/', 'GET');

        // With threshold 2 and weight 1, should not detect a threat
        $this->assertFalse($manager->analyze($request));
    }
}
