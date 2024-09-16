<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationGeolocationTest extends TestCase
{
    // Longitude tests
    public function testLongitudeValidationPasses()
    {
        $validator = $this->getValidator(['longitude' => 100], ['longitude' => 'longitude']);
        $this->assertFalse($validator->fails());
    }

    public function testLongitudeValidationFails()
    {
        $validator = $this->getValidator(['longitude' => 200], ['longitude' => 'longitude']);
        $this->assertTrue($validator->fails());
    }

    public function testLongitudeBoundaryValuesPass()
    {
        $validator = $this->getValidator(['longitude' => -180], ['longitude' => 'longitude']);
        $this->assertFalse($validator->fails());

        $validator = $this->getValidator(['longitude' => 180], ['longitude' => 'longitude']);
        $this->assertFalse($validator->fails());
    }

    public function testLongitudeInvalidStringFails()
    {
        $validator = $this->getValidator(['longitude' => 'invalid'], ['longitude' => 'longitude']);
        $this->assertTrue($validator->fails());
    }

    // Latitude tests
    public function testLatitudeValidationPasses()
    {
        $validator = $this->getValidator(['latitude' => 50], ['latitude' => 'latitude']);
        $this->assertFalse($validator->fails());
    }

    public function testLatitudeValidationFails()
    {
        $validator = $this->getValidator(['latitude' => 100], ['latitude' => 'latitude']);
        $this->assertTrue($validator->fails());
    }

    public function testLatitudeBoundaryValuesPass()
    {
        $validator = $this->getValidator(['latitude' => -90], ['latitude' => 'latitude']);
        $this->assertFalse($validator->fails());

        $validator = $this->getValidator(['latitude' => 90], ['latitude' => 'latitude']);
        $this->assertFalse($validator->fails());
    }

    public function testLatitudeInvalidStringFails()
    {
        $validator = $this->getValidator(['latitude' => 'invalid'], ['latitude' => 'latitude']);
        $this->assertTrue($validator->fails());
    }

    // Camera Angle tests
    public function testCameraAngleValidationPasses()
    {
        $validator = $this->getValidator(['camera_angle' => 120], ['camera_angle' => 'camera_angle']);
        $this->assertFalse($validator->fails());
    }

    public function testCameraAngleValidationFails()
    {
        $validator = $this->getValidator(['camera_angle' => 400], ['camera_angle' => 'camera_angle']);
        $this->assertTrue($validator->fails());
    }

    public function testCameraAngleBoundaryValuesPass()
    {
        $validator = $this->getValidator(['camera_angle' => 0], ['camera_angle' => 'camera_angle']);
        $this->assertFalse($validator->fails());

        $validator = $this->getValidator(['camera_angle' => 360], ['camera_angle' => 'camera_angle']);
        $this->assertFalse($validator->fails());
    }

    public function testCameraAngleInvalidStringFails()
    {
        $validator = $this->getValidator(['camera_angle' => 'invalid'], ['camera_angle' => 'camera_angle']);
        $this->assertTrue($validator->fails());
    }

    public function testEmptyValuesFail()
    {
        $validator = $this->getValidator(['longitude' => null], ['longitude' => 'longitude']);
        $this->assertTrue($validator->fails());

        $validator = $this->getValidator(['latitude' => null], ['latitude' => 'latitude']);
        $this->assertTrue($validator->fails());

        $validator = $this->getValidator(['camera_angle' => null], ['camera_angle' => 'camera_angle']);
        $this->assertTrue($validator->fails());
    }

    // Helper function to create a validator
    protected function getValidator($data, $rules)
    {
        return Validator::make($data, $rules);
    }
}

