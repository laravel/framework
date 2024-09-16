<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationGeolocationTest extends TestCase
{
    /**
     * Longitude Validation Tests
     */
    public function testLongitudeValidationPasses()
    {
        $validator = $this->getValidator(['longitude' => '100'], ['longitude' => 'longitude']);
        $this->assertFalse($validator->fails());
    }

    public function testLongitudeValidationFails()
    {
        $validator = $this->getValidator(['longitude' => '200'], ['longitude' => 'longitude']);
        $this->assertTrue($validator->fails());
    }

    /**
     * Latitude Validation Tests
     */
    public function testLatitudeValidationPasses()
    {
        $validator = $this->getValidator(['latitude' => '50'], ['latitude' => 'latitude']);
        $this->assertFalse($validator->fails());
    }

    public function testLatitudeValidationFails()
    {
        $validator = $this->getValidator(['latitude' => '100'], ['latitude' => 'latitude']);
        $this->assertTrue($validator->fails());
    }

    /**
     * Camera Angle Validation Tests
     */
    public function testCameraAngleValidationPasses()
    {
        $validator = $this->getValidator(['camera_angle' => '180'], ['camera_angle' => 'camera_angle']);
        $this->assertFalse($validator->fails());
    }

    public function testCameraAngleValidationFails()
    {
        $validator = $this->getValidator(['camera_angle' => '400'], ['camera_angle' => 'camera_angle']);
        $this->assertTrue($validator->fails());
    }

    /**
     * Helper function to create the Validator instance.
     *
     * @param array $data
     * @param array $rules
     * @return Validator
     */
    protected function getValidator($data, $rules)
    {
        return Validator::make($data, $rules);
    }
}
