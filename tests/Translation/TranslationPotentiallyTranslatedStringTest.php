<?php

namespace Illuminate\Tests\Translation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\TestWith;

class TranslationPotentiallyTranslatedStringTest extends TestCase
{
    public function testPotentiallyTranslatedStringTranslation()
    {
        $translator = new Translator(tap(new ArrayLoader)->addMessages('en', 'custom', [
            'hello' => 'world',
            'lorem' => 'ipsum',
        ]), 'en');
        $translation = new PotentiallyTranslatedString('custom.hello', $translator);
        $translation->translate();
        $this->assertSame('world', (string) $translation);
    }
}
