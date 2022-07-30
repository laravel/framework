<?php

namespace Illuminate\Tests\Foundation\Console;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Illuminate\Database\Connection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\ModelMakeCommand;
use Illuminate\Support\Composer;
use PHPUnit\Framework\TestCase;

class ModelMakeCommandTest extends TestCase
{
    public function testBuildPhpDocFromColumns()
    {
        $command = new ModelMakeCommand(
            $this->createMock(Filesystem::class),
            $this->createMock(Connection::class),
            $this->createMock(Composer::class)
        );

        $phpDoc = $command->buildPhpDocForColumns([
            new Column('id', new BigIntType()),
            new Column('username', new StringType()),
            new Column('description', new TextType()),
            new Column('payload', new JsonType(), ['Notnull' => false]),
            new Column('is_admin', new BooleanType()),
            new Column('rate', new FloatType()),
            new Column('created_at', new DateTimeType()),
            new Column('updated_at', new DateTimeType()),
        ]);

        $this->assertStringContainsString('/**', $phpDoc);
        $this->assertStringContainsString('@property int $id', $phpDoc);
        $this->assertStringContainsString('@property string $username', $phpDoc);
        $this->assertStringContainsString('@property string $description', $phpDoc);
        $this->assertStringContainsString('@property array|null $payload', $phpDoc);
        $this->assertStringContainsString('@property bool $is_admin', $phpDoc);
        $this->assertStringContainsString('@property float $rate', $phpDoc);
        $this->assertStringContainsString('@property \Carbon\Carbon|string $created_at', $phpDoc);
        $this->assertStringContainsString('@property \Carbon\Carbon|string $updated_at', $phpDoc);
        $this->assertStringContainsString('*/', $phpDoc);
    }
}