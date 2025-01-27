<?php

namespace Illuminate\Tests\Translation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\TestWith;

class TranslationPotentiallyTranslatedStringTest extends TestCase
{
    #[TestWith(['custom.hello', 'en', true])]
    #[TestWith(['basic.hello', 'en', false])]
    #[TestWith(['custom.world', 'en', false])]
    #[TestWith(['custom.hello', 'de', false])]
    public function testPotentiallyTranslatedStringExistenceCheck(string $key, string $locale, bool $exists)
    {
        $translator = new Translator(tap(new ArrayLoader)->addMessages('en', 'custom', [
            'hello' => 'world',
            'lorem' => 'ipsum',
        ]), 'en');
        $translation = new PotentiallyTranslatedString($key, $translator);
        $this->assertSame($exists, $translation->hasForLocale($locale));
    }
}
