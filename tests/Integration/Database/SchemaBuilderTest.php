<?php

namespace Illuminate\Tests\Integration\Database\SchemaTest;

use Doctrine\DBAL\Types\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Types\TinyInteger;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class SchemaBuilderTest extends DatabaseTestCase
{
    public function test_drop_all_tables()
    {
        Schema::create('table', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::dropAllTables();

        Schema::create('table', function (Blueprint $table) {
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

    public function test_register_custom_DBAL_type()
    {
        Schema::registerCustomDBALType(TinyInteger::class, TinyInteger::NAME, 'TINYINT');

        $this->assertArrayHasKey(TinyInteger::NAME, Type::getTypesMap());
    }
}
