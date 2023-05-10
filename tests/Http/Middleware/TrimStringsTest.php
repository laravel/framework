<?php

namespace Illuminate\Tests\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class TrimStringsTest extends TestCase
{
    /**
     * Test no zero-width space character returns the same string.
     */
    public function test_no_zero_width_space_character_returns_the_same_string()
    {
        $request = new Request;

        $request->merge([
            'title' => 'This title does not contain any zero-width space',
        ]);

        $middleware = new TrimStrings;

        $middleware->handle($request, function ($req) {
            $this->assertEquals('This title does not contain any zero-width space', $req->title);
        });
    }

    /**
     * Test leading zero-width space character is trimmed [ZWSP].
     */
    public function test_leading_zero_width_space_character_is_trimmed()
    {
        $request = new Request;

        $request->merge([
            'title' => '​This title contains a zero-width space at the beginning',
        ]);

        $middleware = new TrimStrings;

        $middleware->handle($request, function ($req) {
            $this->assertEquals('This title contains a zero-width space at the beginning', $req->title);
        });
    }

    public function test_trim_strings_can_globally_ignore_certain_inputs()
    {
        $request = new Request;

        $request->merge([
            'globally_ignored_title' => ' test title ',
        ]);

        TrimStrings::except(['globally_ignored_title']);

        $middleware = new TrimStrings;

        $middleware->handle($request, function ($req) {
            $this->assertEquals(' test title ', $req->globally_ignored_title);
        });
    }

    /**
     * Test trailing zero-width space character is trimmed [ZWSP].
     */
    public function test_trailing_zero_width_space_character_is_trimmed()
    {
        $request = new Request;

        $request->merge([
            'title' => 'This title contains a zero-width space at the end​',
        ]);

        $middleware = new TrimStrings;

        $middleware->handle($request, function ($req) {
            $this->assertEquals('This title contains a zero-width space at the end', $req->title);
        });
    }

    /**
     * Test leading zero-width non-breakable space character is trimmed [ZWNBSP].
     */
    public function test_leading_zero_width_non_breakable_space_character_is_trimmed()
    {
        $request = new Request;

        $request->merge([
            'title' => '﻿This title contains a zero-width non-breakable space at the beginning',
        ]);

        $middleware = new TrimStrings;

        $middleware->handle($request, function ($req) {
            $this->assertEquals('This title contains a zero-width non-breakable space at the beginning', $req->title);
        });
    }

    /**
     * Test leading multiple zero-width non-breakable space characters are trimmed [ZWNBSP].
     */
    public function test_leading_multiple_zero_width_non_breakable_space_characters_are_trimmed()
    {
        $request = new Request;

        $request->merge([
            'title' => '﻿﻿This title contains a zero-width non-breakable space at the beginning',
        ]);

        $middleware = new TrimStrings;

        $middleware->handle($request, function ($req) {
            $this->assertEquals('This title contains a zero-width non-breakable space at the beginning', $req->title);
        });
    }

    /**
     * Test a combination of leading and trailing zero-width non-breakable space and zero-width space characters are trimmed [ZWNBSP], [ZWSP].
     */
    public function test_combination_of_leading_and_trailing_zero_width_non_breakable_space_and_zero_width_space_characters_are_trimmed()
    {
        $request = new Request;

        $request->merge([
            'title' => '﻿​﻿This title contains a combination of zero-width non-breakable space and zero-width spaces characters at the beginning and the end​',
        ]);

        $middleware = new TrimStrings;

        $middleware->handle($request, function ($req) {
            $this->assertEquals('This title contains a combination of zero-width non-breakable space and zero-width spaces characters at the beginning and the end', $req->title);
        });
    }
}
