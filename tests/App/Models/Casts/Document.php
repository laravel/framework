<?php

namespace Illuminate\Tests\App\Models\Casts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\App\Casts\StructuredDocumentCaster;

class Document extends Model
{
    public $timestamps = false;

    protected $casts = [
        'document' => StructuredDocumentCaster::class,
    ];
}
