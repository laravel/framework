<?php

namespace Illuminate\Tests\Database\Fixtures\Models\Money;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Database\Fixtures\Factories\Money\PriceFactory;

class Price extends Model
{
    use HasFactory;

    protected $table = 'prices';

    public static function factory()
    {
        return PriceFactory::new();
    }
}
