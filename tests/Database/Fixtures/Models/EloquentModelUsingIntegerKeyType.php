<?php

namespace Illuminate\Tests\Database\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentModelUsingIntegerKeyType extends Model
{
    protected $keyType = 'integer';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'model';

    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return 'model_using_integer_key_type_id';
    }
}
