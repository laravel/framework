<?php

namespace Illuminate\Tests\Integration\Foundation\ListCommand\fixtures\vendor;

use Illuminate\Console\Command;

class VendorCommand extends Command
{
    protected $signature = 'vendor-command';

    public function handle() {}
}
