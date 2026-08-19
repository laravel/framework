<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as Query;

class DynamicRelationModel2 extends Model
{
    public function getResults()
    {
        //
    }

    public function newQuery()
    {
        $query = new class extends Query
        {
            public function __construct()
            {
                //
            }
        };

        return new Builder($query);
    }
}
