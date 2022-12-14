<?php

namespace Illuminate\Tests\Database\Fixtures\Models\Money;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Database\Fixtures\Factories\Money\PriceFactory;

class Price extends Model
{
    protected $table = 'prices';

    public static function newFactory()
    {
        return PriceFactory::new();
    }
}
