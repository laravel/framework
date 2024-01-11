<?php

declare(strict_types=1);

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DatabaseEloquentBuilderTest extends DatabaseTestCase
{
    public function testCreateAndRefresh()
    {
        Schema::create('sample', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        $model = new class extends Model
        {
            protected $table = 'sample';

            protected $fillable = [
                'name',
            ];
        };

        $record = $model->newModelQuery()->createAndRefresh([
            'name' => 'Taylor Otwell',
        ]);

        $this->assertDatabaseHas('sample', [
            'name' => 'Taylor Otwell',
            'status' => '1',
        ]);

        $this->assertNotNull($record->id);
        $this->assertSame('Taylor Otwell', $record->name);
        $this->assertSame(1, $record->status);
        $this->assertNotNull($record->created_at);
        $this->assertNotNull($record->updated_at);
    }
}
