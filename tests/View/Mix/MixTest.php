<?php

namespace Illuminate\Tests\View\Mix;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\HtmlString;

class MixTest extends TestCase
{
    protected $mix;

    public function setUp()
    {
        app()->singleton('path.public', function () {
            return __DIR__;
        });
        $this->mix = new \Illuminate\View\Mix\Mix;
    }

    /**
     * @expectedException \Illuminate\View\Mix\MixException
     * @expectedExceptionMessage The Mix manifest does not exist.
     */
    public function testMixMethodThrowAnExceptionIfMixManifestDoesNotExist()
    {
        $this->mix->mix('foo.css');
    }

    /**
     * @expectedException \Illuminate\View\Mix\MixException
     * @expectedExceptionMessage Unable to locate Mix file: /baz.css. Please check your webpack.mix.js output paths and try again.
     */
    public function testMixMethodThrowAnExceptionIfPathDoesNotExistInManifest()
    {
        $this->mix->mix('baz.css', 'fixtures');
    }

    /**
     * @expectedException \Illuminate\View\Mix\MixException
     * @expectedExceptionMessage The Mix manifest isn't a proper json file.
     */
    public function testMixMethodThrowAnExceptionIfManifestIsNotAProperJson()
    {
        $this->mix->setManifestFilename('mix-manifest-wrong')->mix('foo.css', 'fixtures');
    }

    public function testMixMethodWhenDisabled()
    {
        $this->assertEquals(new HTMLString('Mix is disabled!'), $this->mix->disable()->mix('foo.css'));
    }

    /**
     * @expectedException \Illuminate\View\Mix\MixException
     * @expectedExceptionMessage The Mix manifest does not exist.
     */
    public function testMixMethodWhenReEnabled()
    {
        $this->mix->disable()->enable()->mix('foo.css');
    }

    public function testMixMethodWhenCompiled()
    {
        $this->assertEquals(new HTMLString('/fixtures/bar.css'), $this->mix->mix('foo.css', 'fixtures'));
    }

    public function testMixMethodWhenHMR()
    {
        touch(public_path('hot'));

        $this->assertEquals(new HTMLString('//localhost:8080/foo.css'), $this->mix->mix('foo.css'));

        unlink(public_path('hot'));
    }

    public function testMixMethodWhenCustomHMR()
    {
        touch(public_path('hot-custom'));

        $this->assertEquals(
            new HTMLString('//custom:uri/foo.css'),
            $this->mix->setHmrFilename('hot-custom')->setHmrURI('//custom:uri')->mix('foo.css')
        );

        unlink(public_path('hot-custom'));
    }
}
