<?php declare(strict_types=1);

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\ResourceResponse;

class PostResourceResponse extends ResourceResponse
{
    public function calculateStatus()
    {
        return 200;
    }
}
