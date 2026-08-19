<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class DynamicRelationModel extends Model
{
    public function hardCodedRelation()
    {
        return new FakeHasManyRel();
    }
}
