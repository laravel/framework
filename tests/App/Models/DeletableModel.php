<?php

namespace Illuminate\Tests\App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletableModel extends Model
{
    protected $table = 'delete_model_test_models';

    public $timestamps = false;

    protected $guarded = [];
}
