<?php

namespace Illuminate\Tests\Console\fixtures;

use Illuminate\Console\Attributes\CommandAttribute;
use Illuminate\Console\Command;

#[CommandAttribute(
    name: "test:basic",
    description: "Basic Command description!",
    help: "Some Help.",
    hidden: true
)]
class AttributeCommand extends Command
{
    public function handle(){

    }
}
