<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ModelMakeCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_model_with_hasfactory_trait_when_using_all_option()
    {
        Artisan::call('make:model Post --all');

        $this->assertFileExists(app_path('Models/Post.php'));

        $content = file_get_contents(app_path('Models/Post.php'));
        $this->assertStringContainsString('use HasFactory;', $content);
    }
}
