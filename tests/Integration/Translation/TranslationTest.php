<?php

namespace Illuminate\Tests\Integration\Translation;

use Orchestra\Testbench\TestCase;

class TranslationTest extends TestCase
{
    public function testTheUnderscoreHelperOnlyReturnsStrings()
    {
        $this->assertIsString(__('validation'));
    }

    public function testTheUnderscoreHelperReturnsTheGivenKeyIfTheTranslationCannotBeFound()
    {
        $this->assertEquals('nonexistent_key', __('nonexistent_key'));
    }
}
