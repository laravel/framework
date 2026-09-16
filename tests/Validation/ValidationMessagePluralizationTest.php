<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Http\UploadedFile;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class ValidationMessagePluralizationTest extends TestCase
{
    #[DataProvider('arrayCounts')]
    public function testTranslatedMessagesUseTheSubmittedArrayCount($items, $expected)
    {
        $translator = new Translator(new ArrayLoader, 'en');
        $translator->addLines([
            'validation.min.array' => '{0} There are none|{1} There is one|[2,*] There are :count',
        ], 'en');

        $validator = new Validator($translator, ['items' => $items], ['items' => 'array|min:4']);

        $this->assertSame($expected, $validator->errors()->first('items'));
    }

    public static function arrayCounts()
    {
        return [
            [[], 'There are none'],
            [['a'], 'There is one'],
            [['a', 'b'], 'There are 2'],
            [['a', 'b', 'c'], 'There are 3'],
        ];
    }

    #[DataProvider('messageSources')]
    public function testPluralizationPreservesMessagePrecedenceAndPlaceholders($lines, $messages, $fallback, $expected)
    {
        $translator = new Translator(new ArrayLoader, 'en');
        $translator->addLines($lines + [
            'validation.min.array' => '{1} Default :attribute has one item|[2,*] Default :attribute has :count items',
        ], 'en');

        $validator = new Validator($translator, ['items' => ['a', 'b']], ['items' => 'array|min:3'], $messages, ['items' => 'selected items']);
        $validator->setFallbackMessages($fallback);

        $this->assertSame($expected, $validator->errors()->first('items'));
    }

    public static function messageSources()
    {
        $message = '{1} :attribute has :count item; at least :min required|[2,*] :attribute has :count items; at least :min required';
        $expected = 'selected items has 2 items; at least 3 required';

        return [
            'custom translation' => [['validation.custom.items.min' => $message], [], [], $expected],
            'typed custom translation' => [['validation.custom.items.min.array' => $message], [], [], $expected],
            'wildcard custom translation' => [['validation.custom.*.min' => $message], [], [], $expected],
            'inline attribute message' => [[], ['items.min' => $message], [], $expected],
            'inline rule message' => [[], ['min' => $message], [], $expected],
            'inline typed message' => [[], ['items.min' => ['array' => $message]], [], $expected],
            'inline overrides translation' => [['validation.custom.items.min' => 'Custom message'], ['items.min' => $message], [], $expected],
            'translation overrides fallback' => [[], [], ['min' => $message], 'Default selected items has 2 items'],
        ];
    }

    #[DataProvider('attributeSizes')]
    public function testPluralizationUsesTheValidationSize($value, $rules, $key, $expected)
    {
        $translator = new Translator(new ArrayLoader, 'en');
        $translator->addLines([
            $key => '{0} None for :attribute|{1} One for :attribute|[2,*] :count for :attribute',
        ], 'en');

        $validator = new Validator($translator, ['value' => $value], ['value' => $rules]);

        $this->assertSame($expected, $validator->errors()->first('value'));
    }

    public static function attributeSizes()
    {
        return [
            'string' => ['abc', 'string|min:4', 'validation.min.string', '3 for value'],
            'multibyte string' => ['é', 'string|min:4', 'validation.min.string', 'One for value'],
            'numeric string without numeric rule' => ['123', 'string|min:4', 'validation.min.string', '3 for value'],
            'numeric string' => [' 2 ', 'numeric|min:4', 'validation.min.numeric', '2 for value'],
            'integer' => [2, 'integer|min:4', 'validation.min.numeric', '2 for value'],
            'decimal' => ['2.5', 'decimal:1|min:4', 'validation.min.numeric', '2.5 for value'],
            'null' => [null, 'required', 'validation.required', 'None for value'],
            'empty array' => [[], 'required', 'validation.required', 'None for value'],
        ];
    }

    public function testFileMessagesUseKilobytes()
    {
        $translator = new Translator(new ArrayLoader, 'en');
        $translator->addLines([
            'validation.max.file' => '{1} :attribute is :count kilobyte|[2,*] :attribute is :count kilobytes',
        ], 'en');

        $validator = new Validator($translator, ['file' => UploadedFile::fake()->create('document.pdf', 2)], ['file' => 'file|max:1']);

        $this->assertSame('file is 2 kilobytes', $validator->errors()->first('file'));
    }

    public function testWildcardAttributesUseTheirOwnCounts()
    {
        $translator = new Translator(new ArrayLoader, 'en');
        $translator->addLines([
            'validation.custom.groups.*.items.min' => '{1} Group :position has one item|[2,*] Group :position has :count items',
        ], 'en');

        $validator = new Validator($translator, ['groups' => [['items' => ['a']], ['items' => ['a', 'b']]]], ['groups.*.items' => 'array|min:3']);

        $this->assertSame([
            'groups.0.items' => ['Group 1 has one item'],
            'groups.1.items' => ['Group 2 has 2 items'],
        ], $validator->errors()->toArray());
    }

    public function testEscapedDotsUseTheCorrectAttributeSize()
    {
        $translator = new Translator(new ArrayLoader, 'en');
        $translator->addLines([
            'validation.min.array' => '{1} One item|[2,*] :count items',
        ], 'en');

        $validator = new Validator($translator, ['foo.bar' => ['a', 'b'], 'foo' => ['bar' => ['a']]], ['foo\\.bar' => 'array|min:3']);

        $this->assertSame('2 items', $validator->errors()->first('foo.bar'));
    }

    public function testFallbackTranslationsArePluralized()
    {
        $translator = new Translator(new ArrayLoader, 'fr');
        $translator->setFallback('en');
        $translator->addLines(['validation.min.array' => '{1} One item|[2,*] :count items'], 'en');

        $validator = new Validator($translator, ['items' => ['a', 'b']], ['items' => 'array|min:3']);

        $this->assertSame('2 items', $validator->errors()->first('items'));
    }

    public function testExtensionFallbackMessagesArePluralized()
    {
        $validator = new Validator(new Translator(new ArrayLoader, 'en'), ['items' => ['a', 'b']], ['items' => 'custom']);
        $validator->addExtension('custom', fn () => false);
        $validator->setFallbackMessages(['custom' => '{1} One item|[2,*] :count items']);

        $this->assertSame('2 items', $validator->errors()->first('items'));
    }

    public function testCustomReplacersReceiveTheSelectedMessage()
    {
        $validator = new Validator(new Translator(new ArrayLoader, 'en'), ['items' => ['a', 'b']], ['items' => 'custom'], [
            'custom' => '{1} One :unit|[2,*] :COUNT :unit',
        ]);
        $validator->addExtension('custom', fn () => false);
        $validator->addReplacer('custom', fn ($message) => str_replace(':unit', 'items', $message));

        $this->assertSame('2 items', $validator->errors()->first('items'));
    }

    #[DataProvider('ordinaryMessages')]
    public function testMessagesWithoutExplicitIntervalsAreUnchanged($message)
    {
        $translator = new Translator(new ArrayLoader, 'en');
        $translator->addLines(['validation.min.array' => $message], 'en');

        $validator = new Validator($translator, ['items' => ['a', 'b']], ['items' => 'array|min:3']);

        $this->assertSame($message, $validator->errors()->first('items'));
    }

    public static function ordinaryMessages()
    {
        return [
            ['Choose A|B.'],
            ['An item|Several items'],
            ['[Help] Choose A|B.'],
            ['There are :count items.'],
        ];
    }

    public function testReplacementValuesAreNotInterpretedAsPluralizationSyntax()
    {
        $translator = new Translator(new ArrayLoader, 'en');
        $translator->addLines(['validation.in' => '{1} Invalid :attribute: :input|[2,*] Invalid :attribute (:count): :input'], 'en');

        $validator = new Validator($translator, ['value' => 'a|b'], ['value' => 'in:valid'], [], ['value' => 'A|B']);

        $this->assertSame('Invalid A|B (3): a|b', $validator->errors()->first('value'));
    }

    public function testPluralizationDoesNotTranslateTheResolvedMessageAgain()
    {
        $message = '{1} One item|[2,*] :count items';
        $translator = new Translator(new ArrayLoader, 'en');
        $translator->addLines(['validation.min.array' => $message], 'en');
        $translator->handleMissingKeysUsing(function ($key) use ($message) {
            $this->assertNotSame($message, $key);

            return $key;
        });

        $validator = new Validator($translator, ['items' => ['a', 'b']], ['items' => 'array|min:3']);

        $this->assertSame('2 items', $validator->errors()->first('items'));
    }

    public function testUnsupportedValuesDoNotCauseMessageFormattingToThrow()
    {
        $message = '{1} Invalid item|[2,*] Invalid items';
        $validator = new Validator(new Translator(new ArrayLoader, 'en'), ['value' => new stdClass], ['value' => 'array'], ['array' => $message]);

        $this->assertSame($message, $validator->errors()->first('value'));
    }

    public function testFailedUploadsDoNotCauseMessageFormattingToThrow()
    {
        $message = '{1} Invalid file|[2,*] Invalid files';
        $file = new UploadedFile('', 'document.pdf', null, UPLOAD_ERR_INI_SIZE, true);
        $validator = new Validator(new Translator(new ArrayLoader, 'en'), ['file' => $file], ['file' => 'file'], ['uploaded' => $message]);

        $this->assertSame($message, $validator->errors()->first('file'));
    }

    public function testOutOfRangeNumbersDoNotCauseMessageFormattingToThrow()
    {
        $message = '{1} Invalid number|[2,*] Invalid numbers';
        $validator = new Validator(new Translator(new ArrayLoader, 'en'), ['value' => '1e10000'], ['value' => 'numeric|min:4'], ['min' => $message]);

        $this->assertSame($message, $validator->errors()->first('value'));
    }
}
