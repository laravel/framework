<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelLoadCountTest;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentModelInitialAttributesTest extends DatabaseTestCase
{
    public function testInitialAttributesAreSet()
    {
        $model = new ModelWithInitialAttributes;

        $this->assertSame(
            'Task',
            $model->getAttribute('title')
        );

        $this->assertInstanceOf(
            Carbon::class,
            $model->getAttribute('deadline_at')
        );
    }
}

class ModelWithInitialAttributes extends Model
{
    protected $fillable = [
        'title',
        'deadline_at',
    ];

    protected function initialAttributes()
    {
        return [
            'title' => 'Task',
            'deadline_at' => now()->addWeek(),
        ];
    }
}
