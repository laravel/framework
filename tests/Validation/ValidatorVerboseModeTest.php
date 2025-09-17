<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorVerboseModeTest extends TestCase
{
    /**
     * Helper to create a new validator instance.
     *
     * @param  array  $data
     * @param  array  $rules
     * @return \Illuminate\Validation\Validator
     */
    protected function makeValidator(array $data, array $rules): Validator
    {
        $loader = new ArrayLoader();

        $translator = new Translator($loader, 'en');

        return new Validator($translator, $data, $rules);
    }

    public function testReportIsEmptyWhenVerboseModeIsNotEnabled()
    {
        $validator = $this->makeValidator(['name' => 'Taylor'], ['name' => 'required']);

        $validator->passes();

        $this->assertEmpty($validator->getReport());
    }

    public function testReportCapturesSuccessfulValidationSteps()
    {
        $validator = $this->makeValidator(
            ['name' => 'Taylor', 'email' => 'taylor@laravel.com'],
            ['name' => 'required|string', 'email' => 'required|email']
        )->verbose();

        $this->assertTrue($validator->passes());

        $report = $validator->getReport();

        $this->assertArrayHasKey('name', $report);
        $this->assertArrayHasKey('email', $report);

        $this->assertEquals('Required', $report['name'][0]['rule']);
        $this->assertTrue($report['name'][0]['result']);
        $this->assertEquals('String', $report['name'][1]['rule']);
        $this->assertTrue($report['name'][1]['result']);

        $this->assertEquals('Required', $report['email'][0]['rule']);
        $this->assertTrue($report['email'][0]['result']);
        $this->assertEquals('Email', $report['email'][1]['rule']);
        $this->assertTrue($report['email'][1]['result']);
    }

    public function testReportCapturesFailedValidationSteps()
    {
        $validator = $this->makeValidator(
            ['password' => '123'],
            ['password' => 'required|min:8']
        )->verbose();

        $this->assertTrue($validator->fails());

        $report = $validator->getReport();

        $this->assertArrayHasKey('password', $report);

        $this->assertEquals('Required', $report['password'][0]['rule']);
        $this->assertEquals('123', $report['password'][0]['value']);
        $this->assertTrue($report['password'][0]['result']);

        $this->assertEquals('Min', $report['password'][1]['rule']);
        $this->assertEquals(['8'], $report['password'][1]['parameters']);
        $this->assertFalse($report['password'][1]['result']);
    }

    public function testReportWorksWithCustomRuleObjects()
    {
        $customRule = new class implements Rule
        {
            public function passes($attribute, $value)
            {
                return $value === 'laravel';
            }

            public function message()
            {
                return 'The value must be "laravel".';
            }
        };

        $validator = $this->makeValidator(
            ['framework' => 'laravel'],
            ['framework' => ['required', $customRule]]
        )->verbose();

        $validator->passes();
        $report = $validator->getReport();

        $this->assertArrayHasKey('framework', $report);

        $this->assertEquals(get_class($customRule), $report['framework'][1]['rule']);
        $this->assertEquals('laravel', $report['framework'][1]['value']);
        $this->assertTrue($report['framework'][1]['result']);
    }

    public function testReportIsClearedOnEachValidationRun()
    {
        $validator = $this->makeValidator(['name' => 'A'], ['name' => 'min:2'])->verbose();

        $validator->fails();
        $report1 = $validator->getReport();
        $this->assertCount(1, $report1['name']);
        $this->assertFalse($report1['name'][0]['result']);

        $validator->setData(['name' => 'Correct']);
        $validator->passes();
        $report2 = $validator->getReport();

        $this->assertCount(1, $report2['name']);
        $this->assertTrue($report2['name'][0]['result']);
    }
}
