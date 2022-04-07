<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

include_once 'Enums.php';

class SchemaBlueprintEnumTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('enums_table', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('string_status', StringStatus::class)->nullable();
            $table->enum('integer_status', IntegerStatus::class)->nullable();
        });
    }

    public function testEnumClassAllowed()
    {
        $this->assertTrue(Schema::hasColumns('enums_table', ['string_status', 'integer_status']));
    }

}
