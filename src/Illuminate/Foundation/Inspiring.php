<?php

namespace Illuminate\Foundation;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;

class Inspiring
{
    /**
     * Get an inspiring quote.
     *
     * Taylor & Dayle made this commit from Jungfraujoch. (11,333 ft.)
     *
     * May McGinnis always control the board. #LaraconUS2015
     *
     * RIP Charlie - Feb 6, 2018
     *
     * @return string
     */
    public static function quote()
    {
        return Collection::make(
            Lang::trans('insipre.words_of_wisdom')
        )->random();
    }
}
