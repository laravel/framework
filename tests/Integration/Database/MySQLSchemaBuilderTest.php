<?php

namespace Illuminate\Tests\Integration\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;

class MySQLSchemaBuilderTest extends TestCase
{
    /**
     * The "MySQL" connection.
     *
     * @var DB
     */
    private $db;

    /**
     * Start a "MySQL" connection and create the "test" table.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->startConnection();
        $this->createTestTable();
    }

    /**
     * Delete the "test" table.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->deleteTestTable();
    }

    public function test_if_changing_column_to_char_works()
    {
        $b = new Blueprint('table');
        $b->char('test_column', 121)->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column char(121) NOT NULL COLLATE utf8mb4_unicode_ci',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_double_works()
    {
        $b = new Blueprint('table');
        $b->double('test_column', 3, 8)->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column double(3, 8) NOT NULL COLLATE utf8mb4_unicode_ci',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_enum_works()
    {
        $b = new Blueprint('table');
        $b->enum('test_column', ['yes', 'no'])->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column enum(\'yes\', \'no\') NOT NULL COLLATE utf8mb4_unicode_ci',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_geometry_works()
    {
        $b = new Blueprint('table');
        $b->geometry('test_column')->collation('')->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column geometry NOT NULL',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_geometrycollection_works()
    {
        $b = new Blueprint('table');
        $b->geometryCollection('test_column')->collation('')->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column geometrycollection NOT NULL',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_ipaddress_works()
    {
        $b = new Blueprint('table');
        $b->ipAddress('test_column')->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column varchar(45) NOT NULL COLLATE utf8mb4_unicode_ci',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_jsonb_works()
    {
        $b = new Blueprint('table');
        $b->jsonb('test_column')->collation('')->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column json NOT NULL',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_linestring_works()
    {
        $b = new Blueprint('table');
        $b->lineString('test_column')->collation('')->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column linestring NOT NULL',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_macaddress_works()
    {
        $b = new Blueprint('table');
        $b->macAddress('test_column')->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column varchar(17) NOT NULL COLLATE utf8mb4_unicode_ci',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_mediuminteger_works()
    {
        $b = new Blueprint('table');
        $b->mediumInteger('test_column')->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column mediumint NOT NULL COLLATE utf8mb4_unicode_ci',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_unsigned_mediuminteger_works()
    {
        $b = new Blueprint('table');
        $b->unsignedMediumInteger('test_column')->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column mediumint UNSIGNED NOT NULL COLLATE utf8mb4_unicode_ci',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_unsigned_biginteger_works()
    {
        $b = new Blueprint('table');
        $b->unsignedBigInteger('test_column')->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column BIGINT UNSIGNED NOT NULL COLLATE utf8mb4_unicode_ci',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_unsigned_smallinteger_works()
    {
        $b = new Blueprint('table');
        $b->unsignedSmallInteger('test_column')->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column SMALLINT UNSIGNED NOT NULL COLLATE utf8mb4_unicode_ci',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_multilinestring_works()
    {
        $b = new Blueprint('table');
        $b->multiLineString('test_column')->collation('')->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column multilinestring NOT NULL',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_multipoint_works()
    {
        $b = new Blueprint('table');
        $b->multiPoint('test_column')->collation('')->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column multipoint NOT NULL',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_multipolygon_works()
    {
        $b = new Blueprint('table');
        $b->multiPolygon('test_column')->collation('')->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column multipolygon NOT NULL',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_point_works()
    {
        $b = new Blueprint('table');
        $b->point('test_column')->collation('')->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column point NOT NULL',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_polygon_works()
    {
        $b = new Blueprint('table');
        $b->polygon('test_column')->collation('')->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column polygon NOT NULL',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_tinyinteger_works()
    {
        $b = new Blueprint('table');
        $b->tinyInteger('test_column')->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column tinyint NOT NULL COLLATE utf8mb4_unicode_ci',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_unsigned_tinyinteger_works()
    {
        $b = new Blueprint('table');
        $b->unsignedTinyInteger('test_column')->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column tinyint UNSIGNED NOT NULL COLLATE utf8mb4_unicode_ci',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_uuid_works()
    {
        $b = new Blueprint('table');
        $b->uuid('test_column')->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column char(36) NOT NULL COLLATE utf8mb4_unicode_ci',
            $statements[0]
        );
    }

    public function test_if_changing_column_to_year_works()
    {
        $b = new Blueprint('table');
        $b->year('test_column')->change();

        $statements = $b->toSql($this->db->connection(), new MySqlGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals(
            'ALTER TABLE `table` CHANGE test_column test_column year NOT NULL COLLATE utf8mb4_unicode_ci',
            $statements[0]
        );
    }

    /**
     * Start a "MySQL" connection.
     *
     * @return void
     */
    private function startConnection()
    {
        $this->db = $db = new DB;

        $db->addConnection([
            'driver' => 'mysql',
            'database' => 'laravel',
            'prefix' => '',
            'host' => 'localhost',
            'username' => 'homestead',
            'password' => 'secret'
        ]);

        $db->setAsGlobal();
    }

    /**
     * Create the "test" table.
     *
     * @return void
     */
    private function createTestTable()
    {
        $this->db->connection()->getSchemaBuilder()->create('table', function (Blueprint $table) {
            $table->increments('id');
            $table->string('test_column');
        });
    }

    /**
     * Delete the "test" table.
     *
     * @return void
     */
    private function deleteTestTable()
    {
        $this->db->connection()->getSchemaBuilder()->drop('table');
    }
}