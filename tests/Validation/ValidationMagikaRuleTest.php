<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\MagikaDetector;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\MagikaCliDetector;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

class ValidationMagikaRuleTest extends TestCase
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
        $this->bindDetector('png');

        $this->passes(
            'magika:png',
            UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png'))
        );
    }

    public function testFailsWhenDetectedExtensionDoesNotMatchSingleParam()
    {
        $this->bindDetector('png');

        $this->fails(
            'magika:pdf',
            UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png')),
            ['validation.magika']
        );
    }

    public function testPassesWhenDetectedExtensionIsInMultipleParams()
    {
        $this->bindDetector('jpg');

        $this->passes(
            'magika:png,jpg,pdf',
            UploadedFile::fake()->createWithContent('foo.jpg', file_get_contents(__DIR__.'/fixtures/image.png'))
        );
    }

    public function testFailsWhenDetectedExtensionIsNotInMultipleParams()
    {
        $this->bindDetector('txt');

        $this->fails(
            'magika:png,jpg,pdf',
            UploadedFile::fake()->createWithContent('foo.txt', 'Hello World!'),
            ['validation.magika']
        );
    }

    public function testFailsWhenDetectorReturnsNull()
    {
        $this->bindDetector(null);

        $this->fails(
            'magika:png',
            UploadedFile::fake()->createWithContent('foo.bin', 'binary'),
            ['validation.magika']
        );
    }

    public function testFailsWhenValueIsNotAFile()
    {
        $this->bindDetector('png');

        $trans = new Translator(new ArrayLoader, 'en');
        $v = new Validator($trans, ['x' => 'not-a-file'], ['x' => 'magika:png']);
        $v->setContainer(Container::getInstance());

        $this->assertFalse($v->passes());
    }

    public function testBlocksPhpUpload()
    {
        $this->bindDetector('php');

        $trans = new Translator(new ArrayLoader, 'en');
        $uploadedFile = [__FILE__, 'shell.php', null, null, true];

        $file = $this->createStub(UploadedFile::class);
        $file->__construct(...$uploadedFile);
        $file->method('getClientOriginalExtension')->willReturn('php');

        $v = new Validator($trans, ['x' => $file], ['x' => 'magika:png,jpg']);
        $v->setContainer(Container::getInstance());

        $this->assertFalse($v->passes());
    }

    public function testFluentMagikaToggleEmitsMagikaRule()
    {
        $this->bindDetector('png');

        $this->passes(
            File::types(['png'])->magika(),
            UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png'))
        );
    }

    public function testFluentMagikaToggleFailsOnWrongType()
    {
        $this->bindDetector('txt');

        $this->fails(
            File::types(['png', 'jpg'])->magika(),
            UploadedFile::fake()->createWithContent('foo.txt', 'Hello World!'),
            ['validation.magika']
        );
    }

    public function testFluentWithoutMagikaStillUsesMimes()
    {
        $this->passes(
            File::types(['png']),
            UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png'))
        );
    }

    public function testErrorMessageContainsAllowedTypes()
    {
        $this->bindDetector('txt');

        $trans = new Translator(new ArrayLoader, 'en');
        $trans->addLines(['validation.magika' => 'The :attribute field must be a file of type: :values.'], 'en');

        $file = UploadedFile::fake()->createWithContent('foo.txt', 'Hello World!');
        $v = new Validator($trans, ['x' => $file], ['x' => 'magika:png,jpg']);
        $v->setContainer(Container::getInstance());

        $this->assertFalse($v->passes());
        $this->assertStringContainsString('png, jpg', $v->messages()->first('x'));
    }

    public function testMagikaCliDetectorThrowsWhenBinaryMissing()
    {
        $detector = new MagikaCliDetector('nonexistent-magika-binary-xyz');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/nonexistent-magika-binary-xyz/');

        $detector->detect('/tmp/some-file.png');
    }

    public function testMagikaCliDetectorContractBinding()
    {
        $container = Container::getInstance();

        $this->assertInstanceOf(MagikaDetector::class, $container->make(MagikaDetector::class));
        $this->assertInstanceOf(MagikaCliDetector::class, $container->make(MagikaDetector::class));
    }

    public function testMagikaDetectorCanBeSwappedViaContainer()
    {
        $fake = new class implements MagikaDetector
        {
            public function detect(string $path): ?string
            {
                return 'pdf';
            }
        };

        Container::getInstance()->instance(MagikaDetector::class, $fake);

        $this->passes(
            'magika:pdf',
            UploadedFile::fake()->createWithContent('foo.pdf', '%PDF-1.4 fake')
        );

        $this->fails(
            'magika:png',
            UploadedFile::fake()->createWithContent('foo.pdf', '%PDF-1.4 fake'),
            ['validation.magika']
        );
    }

    public function testFluentMagikaChainedWithSizeConstraints()
    {
        $this->bindDetector('png');

        $this->passes(
            File::types(['png'])->magika()->max('10mb'),
            UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png'))
        );

        $this->fails(
            File::types(['png'])->magika()->max('1kb'),
            UploadedFile::fake()->create('foo.png', 2048),
            ['validation.max.file']
        );

        $this->fails(
            File::types(['png'])->magika()->min('5kb'),
            UploadedFile::fake()->create('foo.png', 1),
            ['validation.min.file']
        );
    }

    public function testMagikaCliDetectorAgainstRealBinary()
    {
        $which = new Process(['which', 'magika']);
        $which->run();

        if (! $which->isSuccessful()) {
            $this->markTestSkipped('The magika binary is not installed. Install via `pip install magika` to run this integration test.');
        }

        $detector = new MagikaCliDetector('magika');

        $detected = $detector->detect(__DIR__.'/fixtures/image.png');

        $this->assertNotNull($detected, 'Magika should detect a known PNG fixture.');
        $this->assertSame('png', strtolower($detected));
    }

    protected function bindDetector(?string $returnExtension): void
    {
        $fake = new class($returnExtension) implements MagikaDetector
        {
            public function __construct(private readonly ?string $ext)
            {
            }

            public function detect(string $path): ?string
            {
                return $this->ext;
            }
        };

        Container::getInstance()->instance(MagikaDetector::class, $fake);
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
