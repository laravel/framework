<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Tests\App\Models\DeletableModel;

class DeleteMissingModelJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Dispatchable;
    use SerializesModels;

    public static bool $handled = false;

    public $deleteWhenMissingModels = true;

    public function __construct(public DeletableModel $model)
    {
    }

    public function displayName(): string
    {
        return 'sorry-ma-forgot-to-take-out-the-trash';
    }

    public function handle()
    {
        self::$handled = true;
    }
}
