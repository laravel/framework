<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\StrictMimeTypeGuesser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Magika\MagikaCliGuesser;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

class ValidationMimeStrictRuleTest extends TestCase
{
    protected function setUp(): void
    {
        $container = Container::getInstance();

        $container->bind('translator', fn () => new Translator(new ArrayLoader, 'en'));

        Facade::setFacadeApplication($container);

        (new ValidationServiceProvider($container))->register();
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        parent::tearDown();
    }

    public function testPassesWhenDetectedExtensionMatchesSingleParam()
    {
        $this->bindGuesser('png');

        $this->passes(
            'mime_strict:png',
            UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png'))
        );
    }

    public function testFailsWhenDetectedExtensionDoesNotMatchSingleParam()
    {
        $this->bindGuesser('png');

        $this->fails(
            'mime_strict:pdf',
            UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png')),
            ['validation.mime_strict']
        );
    }

    public function testPassesWhenDetectedExtensionIsInMultipleParams()
    {
        $this->bindGuesser('jpg');

        $this->passes(
            'mime_strict:png,jpg,pdf',
            UploadedFile::fake()->createWithContent('foo.jpg', file_get_contents(__DIR__.'/fixtures/image.png'))
        );
    }

    public function testFailsWhenDetectedExtensionIsNotInMultipleParams()
    {
        $this->bindGuesser('txt');

        $this->fails(
            'mime_strict:png,jpg,pdf',
            UploadedFile::fake()->createWithContent('foo.txt', 'Hello World!'),
            ['validation.mime_strict']
        );
    }

    public function testFailsWhenGuesserReturnsNull()
    {
        $this->bindGuesser(null);

        $this->fails(
            'mime_strict:png',
            UploadedFile::fake()->createWithContent('foo.bin', 'binary'),
            ['validation.mime_strict']
        );
    }

    public function testFailsWhenValueIsNotAFile()
    {
        $this->bindGuesser('png');

        $trans = new Translator(new ArrayLoader, 'en');
        $v = new Validator($trans, ['x' => 'not-a-file'], ['x' => 'mime_strict:png']);
        $v->setContainer(Container::getInstance());

        $this->assertFalse($v->passes());
    }

    public function testThrowsWhenNoGuesserIsBound()
    {
        $trans = new Translator(new ArrayLoader, 'en');
        $file = UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png'));
        $v = new Validator($trans, ['x' => $file], ['x' => 'mime_strict:png']);
        $v->setContainer(Container::getInstance());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/StrictMimeTypeGuesser/');

        $v->passes();
    }

    public function testBlocksPhpUpload()
    {
        $this->bindGuesser('php');

        $trans = new Translator(new ArrayLoader, 'en');
        $uploadedFile = [__FILE__, 'shell.php', null, null, true];

        $file = $this->createStub(UploadedFile::class);
        $file->__construct(...$uploadedFile);
        $file->method('getClientOriginalExtension')->willReturn('php');

        $v = new Validator($trans, ['x' => $file], ['x' => 'mime_strict:png,jpg']);
        $v->setContainer(Container::getInstance());

        $this->assertFalse($v->passes());
    }

    public function testAllowsPhpUploadWhenPhpIsExplicitlyInParams()
    {
        $this->bindGuesser('php');

        $trans = new Translator(new ArrayLoader, 'en');

        $file = new UploadedFile(__FILE__, 'script.php', null, null, true);

        $v = new Validator($trans, ['x' => $file], ['x' => 'mime_strict:php']);
        $v->setContainer(Container::getInstance());

        $this->assertTrue($v->passes());
    }

    public function testFluentWithoutStrictDoesNotInvokeGuesser()
    {
        $calls = 0;
        $spy = new class($calls) implements StrictMimeTypeGuesser {
            public function __construct(private int &$calls) {}
            public function guess(string $path): ?string { $this->calls++; return 'png'; }
        };

        Container::getInstance()->instance(StrictMimeTypeGuesser::class, $spy);

        $trans = new Translator(new ArrayLoader, 'en');
        $file = UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png'));
        $v = new Validator($trans, ['x' => $file], ['x' => File::types(['png'])]);
        $v->setContainer(Container::getInstance());
        $v->passes();

        $this->assertSame(0, $calls, 'Guesser must not be called when ->strict() is not set.');
    }

    public function testDetectedValueDoesNotLeakBetweenAttributes()
    {
        $this->bindGuesser('txt');

        $trans = new Translator(new ArrayLoader, 'en');
        $trans->addLines(['validation.mime_strict' => 'detected: :detected'], 'en');

        $fileA = UploadedFile::fake()->createWithContent('a.txt', 'text content');
        $fileB = UploadedFile::fake()->createWithContent('b.bin', '');

        $this->bindGuesser('txt');
        $vA = new Validator($trans, ['x' => $fileA], ['x' => 'mime_strict:png']);
        $vA->setContainer(Container::getInstance());
        $vA->passes();
        $this->assertStringContainsString('detected: txt', $vA->messages()->first('x'));

        $this->bindGuesser(null);
        $vB = new Validator($trans, ['x' => $fileB], ['x' => 'mime_strict:png']);
        $vB->setContainer(Container::getInstance());
        $vB->passes();
        $this->assertStringContainsString('detected: unknown', $vB->messages()->first('x'));
    }

    public function testFluentStrictToggleEmitsMimeStrictRule()
    {
        $this->bindGuesser('png');

        $this->passes(
            File::types(['png'])->strict(),
            UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png'))
        );
    }

    public function testFluentStrictToggleFailsOnWrongType()
    {
        $this->bindGuesser('txt');

        $this->fails(
            File::types(['png', 'jpg'])->strict(),
            UploadedFile::fake()->createWithContent('foo.txt', 'Hello World!'),
            ['validation.mime_strict']
        );
    }

    public function testFluentWithoutStrictStillUsesMimes()
    {
        $this->passes(
            File::types(['png']),
            UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png'))
        );
    }

    public function testErrorMessageContainsAllowedTypesAndDetected()
    {
        $this->bindGuesser('txt');

        $trans = new Translator(new ArrayLoader, 'en');
        $trans->addLines(['validation.mime_strict' => 'The :attribute field must be a file of type: :values (detected: :detected).'], 'en');

        $file = UploadedFile::fake()->createWithContent('foo.txt', 'Hello World!');
        $v = new Validator($trans, ['x' => $file], ['x' => 'mime_strict:png,jpg']);
        $v->setContainer(Container::getInstance());

        $this->assertFalse($v->passes());
        $this->assertStringContainsString('png, jpg', $v->messages()->first('x'));
        $this->assertStringContainsString('detected: txt', $v->messages()->first('x'));
    }

    public function testErrorMessageShowsUnknownWhenGuesserReturnsNull()
    {
        $this->bindGuesser(null);

        $trans = new Translator(new ArrayLoader, 'en');
        $trans->addLines(['validation.mime_strict' => 'The :attribute field must be a file of type: :values (detected: :detected).'], 'en');

        $file = UploadedFile::fake()->createWithContent('foo.bin', 'binary');
        $v = new Validator($trans, ['x' => $file], ['x' => 'mime_strict:png']);
        $v->setContainer(Container::getInstance());

        $this->assertFalse($v->passes());
        $this->assertStringContainsString('detected: unknown', $v->messages()->first('x'));
    }

    public function testMimeStrictDetectsFilesThatFoolFinfo()
    {
        $which = new Process(['which', 'magika']);
        $which->run();

        if (! $which->isSuccessful()) {
            $this->markTestSkipped('The magika binary is not installed.');
        }

        Container::getInstance()->bind(StrictMimeTypeGuesser::class, MagikaCliGuesser::class);

        $fixture = __DIR__.'/fixtures/spoofed_php_as_png.png';

        $trans = new Translator(new ArrayLoader, 'en');
        $file = new UploadedFile($fixture, 'upload.png', null, null, true);

        $mimesValidator = new Validator($trans, ['x' => $file], ['x' => 'mimes:png']);
        $mimesValidator->setContainer(Container::getInstance());
        $this->assertTrue($mimesValidator->passes(), 'finfo should be fooled by the PNG header');

        $strictValidator = new Validator($trans, ['x' => $file], ['x' => 'mime_strict:png']);
        $strictValidator->setContainer(Container::getInstance());
        $this->assertFalse($strictValidator->passes(), 'Strict guesser should detect the true PHP content');
    }

    public function testMagikaCliGuesserThrowsWhenBinaryMissing()
    {
        $guesser = new MagikaCliGuesser('nonexistent-magika-binary-xyz');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/nonexistent-magika-binary-xyz/');

        $guesser->guess('/tmp/some-file.png');
    }

    public function testGuesserCanBeSwappedViaContainer()
    {
        $fake = new class implements StrictMimeTypeGuesser
        {
            public function guess(string $path): ?string
            {
                return 'pdf';
            }
        };

        Container::getInstance()->instance(StrictMimeTypeGuesser::class, $fake);

        $this->passes(
            'mime_strict:pdf',
            UploadedFile::fake()->createWithContent('foo.pdf', '%PDF-1.4 fake')
        );

        $this->fails(
            'mime_strict:png',
            UploadedFile::fake()->createWithContent('foo.pdf', '%PDF-1.4 fake'),
            ['validation.mime_strict']
        );
    }

    public function testFluentStrictChainedWithSizeConstraints()
    {
        $this->bindGuesser('png');

        $this->passes(
            File::types(['png'])->strict()->max('10mb'),
            UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png'))
        );

        $this->fails(
            File::types(['png'])->strict()->max('1kb'),
            UploadedFile::fake()->create('foo.png', 2048),
            ['validation.max.file']
        );

        $this->fails(
            File::types(['png'])->strict()->min('5kb'),
            UploadedFile::fake()->create('foo.png', 1),
            ['validation.min.file']
        );
    }

    public function testMagikaCliGuesserAgainstRealBinary()
    {
        $which = new Process(['which', 'magika']);
        $which->run();

        if (! $which->isSuccessful()) {
            $this->markTestSkipped('The magika binary is not installed. Install via `pip install magika` to run this integration test.');
        }

        $guesser = new MagikaCliGuesser('magika');

        $detected = $guesser->guess(__DIR__.'/fixtures/image.png');

        $this->assertNotNull($detected, 'Magika should detect a known PNG fixture.');
        $this->assertSame('png', strtolower($detected));
    }

    protected function bindGuesser(?string $returnExtension): void
    {
        $fake = new class($returnExtension) implements StrictMimeTypeGuesser
        {
            public function __construct(private readonly ?string $ext)
            {
            }

            public function guess(string $path): ?string
            {
                return $this->ext;
            }
        };

        Container::getInstance()->instance(StrictMimeTypeGuesser::class, $fake);
    }

    protected function passes(mixed $rule, mixed $values): void
    {
        $this->assertValidationRules($rule, $values, true, []);
    }

    protected function fails(mixed $rule, mixed $values, array $messages): void
    {
        $this->assertValidationRules($rule, $values, false, $messages);
    }

    protected function assertValidationRules(mixed $rule, mixed $values, bool $result, array $messages): void
    {
        foreach (Arr::wrap($values) as $value) {
            $trans = new Translator(new ArrayLoader, 'en');
            $v = new Validator($trans, ['x' => $value], ['x' => is_object($rule) ? clone $rule : $rule]);
            $v->setContainer(Container::getInstance());

            $this->assertSame($result, $v->passes());
            $this->assertSame($result ? [] : ['x' => $messages], $v->messages()->toArray());
        }
    }
}
