<?php

declare(strict_types=1);

namespace Illuminate\Tests\Database\Fixtures\Models;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar;

final class OverriddenSchemaBlueprint extends Blueprint
{
    public static bool $invokedSpy = false;

    protected $commands = [];

    public function timestamps($precision = 0): void
    {
        parent::timestamps($precision);
        $this->applyTimestampDefaults();
    }

    private function applyTimestampDefaults(): void
    {
        // other DB statements...
        self::$invokedSpy = true;
    }

    public function toSql(Connection $connection, Grammar $grammar)
    {
        return [];
    }
}
