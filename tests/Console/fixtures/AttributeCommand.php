<?php

namespace Illuminate\Tests\Console\fixtures;

use Illuminate\Console\Attributes\ArtisanCommand;
use Illuminate\Console\Command;

#[ArtisanCommand(
    name: "test:basic",
    description: "Basic Command description!",
    help: "Some Help.",
    aliases: ['alias:basic'],
    hidden: true
)]
class AttributeCommand extends Command
{
    public function handle(){

    }
}
