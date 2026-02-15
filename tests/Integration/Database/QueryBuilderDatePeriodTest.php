<?php

namespace Illuminate\Tests\Integration\Database;

use Carbon\CarbonPeriod;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueryBuilderDatePeriodTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->dateTime('event_date');
        });

        DB::table('events')->insert([
            ['name' => 'Event 1', 'event_date' => '2024-01-05 10:00:00'],
            ['name' => 'Event 2', 'event_date' => '2024-01-10 10:00:00'],
            ['name' => 'Event 3', 'event_date' => '2024-01-15 10:00:00'],
            ['name' => 'Event 4', 'event_date' => '2024-01-20 10:00:00'],
            ['name' => 'Event 5', 'event_date' => '2024-01-25 10:00:00'],
        ]);
    }

    public function testWhereBetweenWithDatePeriod()
    {
        $period = new DatePeriod(
            new DateTime('2024-01-10'),
            new DateInterval('P1D'),
            new DateTime('2024-01-20')
        );

        $results = DB::table('events')
            ->whereBetween('event_date', $period)
            ->pluck('name')
            ->all();

        $this->assertEquals(['Event 2', 'Event 3'], $results);
    }

    public function testWhereBetweenWithCarbonPeriod()
    {
        $period = CarbonPeriod::create('2024-01-10', '2024-01-20');

        $results = DB::table('events')
            ->whereBetween('event_date', $period)
            ->pluck('name')
            ->all();

        $this->assertEquals(['Event 2', 'Event 3'], $results);
    }

    public function testWhereBetweenWithDatePeriodUsingRecurrences()
    {
        // Start date + 10 days (10 recurrences)
        $period = new DatePeriod(
            new DateTime('2024-01-10'),
            new DateInterval('P1D'),
            10
        );

        $results = DB::table('events')
            ->whereBetween('event_date', $period)
            ->pluck('name')
            ->all();

        $this->assertEquals(['Event 2', 'Event 3'], $results);
    }

    public function testWhereBetweenWithDatePeriodExcludeStartDate()
    {
        $period = new DatePeriod(
            new DateTime('2024-01-10'),
            new DateInterval('P1D'),
            new DateTime('2024-01-20'),
            DatePeriod::EXCLUDE_START_DATE
        );

        $results = DB::table('events')
            ->whereBetween('event_date', $period)
            ->pluck('name')
            ->all();

        // Event 2 (2024-01-10) should be excluded
        $this->assertEquals(['Event 3'], $results);
    }

    public function testWhereBetweenWithDatePeriodIncludeEndDate()
    {
        $period = new DatePeriod(
            new DateTime('2024-01-10'),
            new DateInterval('P1D'),
            new DateTime('2024-01-20'),
            DatePeriod::INCLUDE_END_DATE
        );

        $results = DB::table('events')
            ->whereBetween('event_date', $period)
            ->pluck('name')
            ->all();

        // Event 4 (2024-01-20) should be included
        $this->assertEquals(['Event 2', 'Event 3', 'Event 4'], $results);
    }

    public function testWhereBetweenWithDatePeriodExcludeStartAndIncludeEnd()
    {
        $period = new DatePeriod(
            new DateTime('2024-01-10'),
            new DateInterval('P1D'),
            new DateTime('2024-01-20'),
            DatePeriod::EXCLUDE_START_DATE | DatePeriod::INCLUDE_END_DATE
        );

        $results = DB::table('events')
            ->whereBetween('event_date', $period)
            ->pluck('name')
            ->all();

        // Event 2 excluded, Event 4 included
        $this->assertEquals(['Event 3', 'Event 4'], $results);
    }

    public function testWhereNotBetweenWithDatePeriod()
    {
        $period = new DatePeriod(
            new DateTime('2024-01-10'),
            new DateInterval('P1D'),
            new DateTime('2024-01-20')
        );

        $results = DB::table('events')
            ->whereNotBetween('event_date', $period)
            ->pluck('name')
            ->all();

        // Should return events outside the period
        $this->assertEquals(['Event 1', 'Event 4', 'Event 5'], $results);
    }

    public function testWhereNotBetweenWithDatePeriodExcludeStartDate()
    {
        $period = new DatePeriod(
            new DateTime('2024-01-10'),
            new DateInterval('P1D'),
            new DateTime('2024-01-20'),
            DatePeriod::EXCLUDE_START_DATE
        );

        $results = DB::table('events')
            ->whereNotBetween('event_date', $period)
            ->pluck('name')
            ->all();

        // Event 2 (2024-01-10) should be included since it's excluded from the period
        $this->assertEquals(['Event 1', 'Event 2', 'Event 4', 'Event 5'], $results);
    }

    public function testHavingBetweenWithDatePeriod()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->increments('id');
            $table->string('product');
            $table->integer('quantity');
            $table->date('sale_date');
        });

        DB::table('sales')->insert([
            ['product' => 'A', 'quantity' => 10, 'sale_date' => '2024-01-05'],
            ['product' => 'A', 'quantity' => 20, 'sale_date' => '2024-01-15'],
            ['product' => 'B', 'quantity' => 15, 'sale_date' => '2024-01-10'],
            ['product' => 'B', 'quantity' => 25, 'sale_date' => '2024-01-20'],
        ]);

        $period = new DatePeriod(
            new DateTime('2024-01-10'),
            new DateInterval('P1D'),
            new DateTime('2024-01-20')
        );

        $results = DB::table('sales')
            ->select('product')
            ->selectRaw('MIN(sale_date) as first_sale')
            ->groupBy('product')
            ->havingBetween('first_sale', $period)
            ->pluck('product')
            ->all();

        // Product B has first sale on 2024-01-10 (within period)
        $this->assertEquals(['B'], $results);
    }

    public function testHavingNotBetweenWithDatePeriod()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('customer');
            $table->integer('amount');
            $table->date('order_date');
        });

        DB::table('orders')->insert([
            ['customer' => 'John', 'amount' => 100, 'order_date' => '2024-01-05'],
            ['customer' => 'John', 'amount' => 200, 'order_date' => '2024-01-25'],
            ['customer' => 'Jane', 'amount' => 150, 'order_date' => '2024-01-15'],
        ]);

        $period = new DatePeriod(
            new DateTime('2024-01-10'),
            new DateInterval('P1D'),
            new DateTime('2024-01-20')
        );

        $results = DB::table('orders')
            ->select('customer')
            ->selectRaw('MIN(order_date) as first_order')
            ->groupBy('customer')
            ->havingNotBetween('first_order', $period)
            ->pluck('customer')
            ->all();

        // John's first order is 2024-01-05 (outside period)
        $this->assertEquals(['John'], $results);
    }

    public function testOrWhereBetweenWithDatePeriod()
    {
        $period = new DatePeriod(
            new DateTime('2024-01-10'),
            new DateInterval('P1D'),
            new DateTime('2024-01-16')
        );

        $results = DB::table('events')
            ->where('name', 'Event 1')
            ->orWhereBetween('event_date', $period)
            ->pluck('name')
            ->all();

        $this->assertEquals(['Event 1', 'Event 2', 'Event 3'], $results);
    }

    public function testOrWhereNotBetweenWithDatePeriod()
    {
        $period = new DatePeriod(
            new DateTime('2024-01-10'),
            new DateInterval('P1D'),
            new DateTime('2024-01-16')
        );

        $results = DB::table('events')
            ->where('name', 'Event 2')
            ->orWhereNotBetween('event_date', $period)
            ->pluck('name')
            ->all();

        $this->assertEquals(['Event 1', 'Event 2', 'Event 4', 'Event 5'], $results);
    }
}

