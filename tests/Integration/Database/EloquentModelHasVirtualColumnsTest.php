<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Contracts\Database\Eloquent\HasVirtualColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentModelHasVirtualColumnsTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase(): void
    {
        Schema::create('virtual', static function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
        });

        Schema::table('virtual', static fn (Blueprint $table) => $table
            ->string('full_name')
            ->after('id')
            ->virtualAs("first_name || ' ' || last_name"),
        );
    }

    public function testVirtualColumnIsRetrievedWhenInterfaceIsApplied(): void
    {
        $record = EloquentModelWithVirtualColumns::query()->create([
            'first_name' => 'John',
            'last_name'  => 'Smith',
        ]);

        $this->assertSame('John', $record->first_name);
        $this->assertSame('Smith', $record->last_name);
        $this->assertSame('John Smith', $record->full_name);
    }

    public function testVirtualColumnIsMissingWhenInterfaceIsMissing(): void
    {
        $record = EloquentModelMissedVirtualColumns::query()->create([
            'first_name' => 'John',
            'last_name'  => 'Smith',
        ]);

        $this->assertSame('John', $record->first_name);
        $this->assertSame('Smith', $record->last_name);
        $this->assertNull($record->full_name);
    }
}

abstract class VirtualColumnsEloquentModel extends Model
{
    protected $table = 'virtual';

    public $timestamps = false;

    protected $fillable = ['id', 'first_name', 'last_name'];
}

class EloquentModelWithVirtualColumns extends VirtualColumnsEloquentModel implements HasVirtualColumns
{
    //
}

class EloquentModelMissedVirtualColumns extends VirtualColumnsEloquentModel
{
    //
}
