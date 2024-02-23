<?php

namespace Illuminate\Tests\Console;

use Exception;
use PHPUnit\Framework\TestCase;

use function Illuminate\Console\checked_var_export;

class CheckedVarExportHelperTest extends TestCase
{
    public function testItCanExportVariable()
    {
        $this->assertSame(<<<'TEXT'
        array (
          0 => 'Laravel',
        )
        TEXT, checked_var_export(['Laravel']));
    }

    public function testExportThrowsOnClosure()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to export file: Candidate file content cannot be parsed.');
        $this->expectExceptionCode(0);

        checked_var_export([fn () => null]);
    }
}
