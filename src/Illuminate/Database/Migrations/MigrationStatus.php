<?php

namespace Illuminate\Database\Migrations;

enum MigrationStatus: string
{
    case Ran = 'Ran';
    case Pending = 'Pending';
    case Skipped = 'Skipped';
}
