<?php

namespace Illuminate\Tests\App\Http\Resources;

class PostResourceWithoutWrap extends PostResource
{
    public static $wrap = null;
}
