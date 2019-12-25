<?php

namespace Illuminate\Tests\Integration\Database;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentModelTimeCastingTest extends DatabaseTestCase
{
    public function testTimesAreCastable()
    {
        $item = TestTimeModel::create([
            'time_field' => '08:11:27',
        ]);

        $this->assertEquals(date_create('08:11:27'), $item->time_field);
        $this->assertEquals(date_create('08:11:27'), $item->toArray()['time_field']);
        $this->assertSame('08:11:27', $item->time_field->format('H:i:s'));
        $this->assertSame('08:11', $item->time_field->format('H:i'));
        $this->assertInstanceOf(Carbon::class, $item->time_field);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->time('time_field')->nullable();
        });
    }
}

class TestTimeModel extends Model
{
    public $timestamps = false;

    protected $table = 'test_model1';

    protected $guarded = ['id'];

    protected $casts = [
        'time_field' => 'time',
    ];
}
