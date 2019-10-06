<?php

namespace Illuminate\Contracts\Foundation\Testing;

interface Fakeable
{
    /**
     * Setup up the Faker instance.
     *
     * @return void
     */
    public function doSetUpFaker();
}
