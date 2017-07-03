<?php

namespace Illuminate\Tests\View\Mix;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\HtmlString;

class MixTest extends TestCase
{
    /**
     * The Mix instance to test.
     *
     * @var Mix
     */
    protected $mix;

    public function setUp()
    {
        app()->singleton('path.public', function () {
            return __DIR__;
        });

        $this->mix = new Mix;
    }

    /**
     * @expectedException \Illuminate\View\Mix\MixException
     * @expectedExceptionMessageRegExp #The Mix manifest .+/mix-manifest.json does not exist.#
     */
    public function testMixMethodThrowAnExceptionIfMixManifestDoesNotExist()
    {
        $this->mix->resolve('foo.css');
    }

    /**
     * @expectedException \Illuminate\View\Mix\MixException
     * @expectedExceptionMessage Unable to locate the file: /baz.css. Please check your webpack.mix.js output paths and try again.
     */
    public function testMixMethodThrowAnExceptionIfPathDoesNotExistInManifest()
    {
        $this->mix->resolve('baz.css', 'fixtures');
    }

    /**
     * @expectedException \Illuminate\View\Mix\MixException
     * @expectedExceptionMessageRegExp #The Mix manifest .+/mix-manifest-wrong isn't a proper json file.#
     */
    public function testMixMethodThrowAnExceptionIfManifestIsNotAProperJson()
    {
        $this->mix->setManifestFilename('mix-manifest-wrong')->resolve('foo.css', 'fixtures');
    }

    public function testMixMethodWhenDisabled()
    {
        $this->assertEquals(new HtmlString('Mix is disabled!'), $this->mix->disable()->resolve('foo.css'));
    }

    /**
     * @expectedException \Illuminate\View\Mix\MixException
     * @expectedExceptionMessageRegExp #The Mix manifest .+/mix-manifest.json does not exist.#
     */
    public function testMixMethodWhenReEnabled()
    {
        $this->mix->disable()->enable()->resolve('foo.css');
    }

    public function testMixMethodWhenCompiled()
    {
        $this->assertEquals(new HtmlString('/fixtures/bar.css'), $this->mix->resolve('foo.css', 'fixtures'));
    }

    public function testMixMethodWhenHMR()
    {
        touch(public_path('hot'));

        $this->assertEquals(new HtmlString('//localhost:8080/foo.css'), $this->mix->resolve('foo.css'));

        unlink(public_path('hot'));
    }

    public function testMixMethodWhenCustomHMR()
    {
        touch(public_path('hot-custom'));

        $this->assertEquals(
            new HtmlString('//custom:uri/foo.css'),
            $this->mix->setHmrFilename('hot-custom')->setHmrURI('//custom:uri')->resolve('foo.css')
        );

        unlink(public_path('hot-custom'));
    }

    public function testManifestsAreCached()
    {
        $this->assertSame([], $this->mix->getCachedManifests());

        $this->mix->resolve('foo.css', 'fixtures');
        $this->assertSame([
            public_path('fixtures/mix-manifest.json') => ['/foo.css' => '/bar.css'],
        ], $this->mix->getCachedManifests());

        $this->mix->setManifestFilename('mix-manifest-bis.json')->resolve('foo-bis.css', 'fixtures');
        $this->assertSame([
            public_path('fixtures/mix-manifest.json') => ['/foo.css' => '/bar.css'],
            public_path('fixtures/mix-manifest-bis.json') => ['/foo-bis.css' => '/bar-bis.css'],
        ], $this->mix->getCachedManifests());
    }
}

class Mix extends \Illuminate\View\Mix\Mix {
    /**
     * Expose publicly the protected var cachedManifests
     *
     * @return array
     */
    public function getCachedManifests()
    {
        return $this->cachedManifests;
    }
}
