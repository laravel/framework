<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\Console\ForgetCommand;
use PHPUnit\Framework\TestCase;

class ForgetCommandTest extends TestCase
{
    public function testCanGetHelpWithoutInstantiatingDependencies()
    {
        $help = (new ForgetCommand())->getHelp();
        $this->stringContains('php artisan cache:forget', $help);
    }
}
