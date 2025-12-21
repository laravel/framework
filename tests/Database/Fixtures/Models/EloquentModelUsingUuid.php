<?php

namespace Illuminate\Tests\Database\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentModelUsingUuid extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'model';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return 'model_using_uuid_id';
    }
}
