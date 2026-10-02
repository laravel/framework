<?php

namespace Illuminate\Tests\Integration\Database\MariaDb;

use Illuminate\Database\Eloquent\Casts\AsVector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Attributes\RequiresDatabase;

#[RequiresDatabase('mariadb', '>=11.7.0')]
class EloquentVectorTest extends MariaDbTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->increments('id');
            $table->vector('embedding', 3);
            $table->vectorIndex('embedding');
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::dropIfExists('documents');
    }

    public function testVectorsCanBeStoredAndRetrieved()
    {
        $document = VectorDocument::create(['embedding' => [0.5, -1.25, 3]]);

        $this->assertSame([0.5, -1.25, 3.0], $document->embedding);
        $this->assertSame([0.5, -1.25, 3.0], $document->fresh()->embedding);
    }

    public function testVectorsCanBeUpdated()
    {
        $document = VectorDocument::create(['embedding' => [0.5, -1.25, 3]]);

        $document->update(['embedding' => [1, 2, 3]]);

        $this->assertSame([1.0, 2.0, 3.0], $document->fresh()->embedding);
    }

    public function testVectorsCanBeQueriedByDistance()
    {
        $exact = VectorDocument::create(['embedding' => [1, 0, 0]]);
        $close = VectorDocument::create(['embedding' => [0.9, 0.1, 0]]);
        VectorDocument::create(['embedding' => [0, 1, 0]]);

        $results = VectorDocument::query()
            ->select('id')
            ->selectVectorDistance('embedding', [1, 0, 0])
            ->whereVectorSimilarTo('embedding', [1, 0, 0], minSimilarity: 0.5)
            ->get();

        $this->assertSame([$exact->id, $close->id], $results->pluck('id')->all());
        $this->assertEqualsWithDelta(0.0, (float) $results[0]->embedding_distance, 0.0001);
        $this->assertGreaterThan(0.0, (float) $results[1]->embedding_distance);
    }
}

class VectorDocument extends Model
{
    protected $table = 'documents';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'embedding' => AsVector::class,
    ];
}
