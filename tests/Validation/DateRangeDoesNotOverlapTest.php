<?php

namespace Illuminate\Validation\Rules;

use PHPUnit\Framework\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Mockery as m;

class DateRangeDoesNotOverlapTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testItPassesWhenNoOverlapsExist()
    {
        $request = $this->mockRequest([
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-05',
        ]);

        $dbMock = m::mock('alias:Illuminate\Support\Facades\DB');
        $queryBuilder = m::mock();
        $dbMock->shouldReceive('table')->once()->with('reservations')->andReturn($queryBuilder);

        $queryBuilder->shouldReceive('where')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('exists')->once()->andReturn(false);

        $rule = new DateRangeDoesNotOverlap('reservations');
        $this->validateRule($rule, 'start_date', '2025-06-01');
    }

    public function testItFailsWhenOverlapsExist()
    {
        $request = $this->mockRequest([
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-05',
        ]);

        $dbMock = m::mock('alias:Illuminate\Support\Facades\DB');
        $queryBuilder = m::mock();
        $dbMock->shouldReceive('table')->once()->with('reservations')->andReturn($queryBuilder);

        $queryBuilder->shouldReceive('where')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('exists')->once()->andReturn(true);

        $rule = new DateRangeDoesNotOverlap('reservations');

        $failed = false;
        $failCallback = function () use (&$failed) {
            $failed = true;
            return m::mock('Illuminate\Contracts\Validation\ValidationRule\Failed')
                ->shouldReceive('translate')
                ->once()
                ->getMock();
        };

        $rule->validate('start_date', '2025-06-01', $failCallback);
        $this->assertTrue($failed);
    }

    public function testItExcludesCurrentRecordForUpdates()
    {
        $request = $this->mockRequest([
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-05',
        ]);

        $dbMock = m::mock('alias:Illuminate\Support\Facades\DB');
        $queryBuilder = m::mock();
        $subQueryBuilder = m::mock();

        $dbMock->shouldReceive('table')->once()->with('reservations')->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('where')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('where')->once()->with('id', '!=', 5)->andReturnSelf();
        $queryBuilder->shouldReceive('exists')->once()->andReturn(false);

        $rule = new DateRangeDoesNotOverlap('reservations', 'start_date', 'end_date', 5);
        $this->validateRule($rule, 'start_date', '2025-06-01');
    }

    public function testFluentApiForExclusion()
    {
        $rule = new DateRangeDoesNotOverlap('reservations');
        $rule->exclude(5);

        $this->assertSame(5, $this->getProtectedProperty($rule, 'excludeId'));
    }

    protected function mockRequest(array $data)
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('all')->andReturn($data);

        foreach ($data as $key => $value) {
            $request->shouldReceive('input')->with($key)->andReturn($value);
        }

        app()->instance('request', $request);

        return $request;
    }

    protected function validateRule($rule, $attribute, $value)
    {
        $rule->validate($attribute, $value, function () {
            return m::mock('Illuminate\Contracts\Validation\Failed')
                ->shouldReceive('translate')
                ->getMock();
        });
    }

    protected function getProtectedProperty($object, $property)
    {
        $reflection = new \ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }
}
