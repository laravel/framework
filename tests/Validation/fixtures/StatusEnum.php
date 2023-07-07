<?php

namespace Illuminate\Tests\Validation\fixtures;

enum StatusEnum: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
