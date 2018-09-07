<?php

namespace Illuminate\Tests\Integration\Database\SchemaTest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class SchemaBuilderTest extends DatabaseTestCase
{
    public function test_drop_all_tables()
    {
        Schema::create('table', function ($table) {
            $table->increments('id');
        });

        Schema::dropAllTables();

        Schema::create('table', function ($table) {
            $table->increments('id');
        });

        $this->assertTrue(true);
    }

    public function test_drop_all_views()
    {
        DB::statement('create view "view"("id") as select 1');

        Schema::dropAllViews();

        DB::statement('create view "view"("id") as select 1');

        $this->assertTrue(true);
    }
}
