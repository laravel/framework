<?php

namespace Illuminate\Tests\Integration\Database\SchemaTest;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Grammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class ConfigureCustomDoctrineTypeTest extends DatabaseTestCase
{
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']['database.dbal.types'] = ['xml' => XmlType::class];
    }

    public function test_rename_column_with_custom_doctrine_type_listed_in_config()
    {
        if ($this->driver !== 'pgsql') {
            $this->markTestSkipped('Test requires a Postgres connection.');
        }

        Grammar::macro('typeXml', function () {
            return 'xml';
        });

        Schema::create('test', function (Blueprint $table) {
            $table->addColumn('xml', 'test_column');
        });

        Schema::table('test', function (Blueprint $table) {
            $table->renameColumn('test_column', 'renamed_column');
        });

        $this->assertFalse(Schema::hasColumn('test', 'test_column'));
        $this->assertTrue(Schema::hasColumn('test', 'renamed_column'));
    }
}

class XmlType extends Type
{
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'xml';
    }

    public function getName()
    {
        return 'xml';
    }
}
