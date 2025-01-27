<?php

namespace Illuminate\Tests\Translation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase;

class TranslationPotentiallyTranslatedStringTest extends TestCase
{
    public function testPotentitallyTranslatedStringWithCurriedReplacements()
    {
        $translator = new Translator(tap(new ArrayLoader)->addMessages('en', 'custom', [
            'test' => 'Message: :first :second :third!',
        ]), 'en');
        $translation = new PotentiallyTranslatedString('custom.test', $translator, [
            'first' => 'hello',
            'second' => 'world',
            'third' => 'Otwell',
        ]);
        $translation->addReplace([
            'first' => 'Hi',
            'second' => 'Acme',
        ]);
        $translation->translate([
            'second' => 'Taylor',
        ]);
        $this->assertSame('Message: Hi Taylor Otwell!', (string) $translation);
    }
}