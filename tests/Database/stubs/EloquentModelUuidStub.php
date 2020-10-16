<?php

use Illuminate\Database\Eloquent\Model;

class EloquentModelUuidStub extends Model
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
}
