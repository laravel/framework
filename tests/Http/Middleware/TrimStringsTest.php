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
    public function testNoZeroWidthSpaceCharacterReturnsTheSameString()
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
    public function testLeadingZeroWidthSpaceCharacterIsTrimmed()
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

    public function testTrimStringsCanGloballyIgnoreCertainInputs()
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
    public function testTrailingZeroWidthSpaceCharacterIsTrimmed()
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
    public function testLeadingZeroWidthNonBreakableSpaceCharacterIsTrimmed()
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
    public function testLeadingMultipleZeroWidthNonBreakableSpaceCharactersAreTrimmed()
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
    public function testCombinationOfLeadingAndTrailingZeroWidthNonBreakableSpaceAndZeroWidthSpaceCharactersAreTrimmed()
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

    /**
     * Test leading invisible character are trimmed [U+200E].
     */
    public function testLeadingInvisibleCharactersAreTrimmed()
    {
        $request = new Request;

        $request->merge([
            'title' => '‎This title contains a invisible character at the beginning',
        ]);

        $middleware = new TrimStrings;

        $middleware->handle($request, function ($req) {
            $this->assertEquals('This title contains a invisible character at the beginning', $req->title);
        });
    }

    /**
     * Test trailing invisible character are trimmed [U+200E].
     */
    public function testTrailingInvisibleCharactersAreTrimmed()
    {
        $request = new Request;

        $request->merge([
            'title' => 'This title contains a invisible character at the end‎',
        ]);

        $middleware = new TrimStrings;

        $middleware->handle($request, function ($req) {
            $this->assertEquals('This title contains a invisible character at the end', $req->title);
        });
    }

    /**
     * Test leading multiple invisible character are trimmed [U+200E].
     */
    public function testLeadingMultipleInvisibleCharactersAreTrimmed()
    {
        $request = new Request;

        $request->merge([
            'title' => '‎‎This title contains a invisible character at the beginning',
        ]);

        $middleware = new TrimStrings;

        $middleware->handle($request, function ($req) {
            $this->assertEquals('This title contains a invisible character at the beginning', $req->title);
        });
    }

    /**
     * Test trailing multiple invisible character are trimmed [U+200E].
     */
    public function testTrailingMultipleInvisibleCharactersAreTrimmed()
    {
        $request = new Request;

        $request->merge([
            'title' => 'This title contains a invisible character at the end‎‎',
        ]);

        $middleware = new TrimStrings;

        $middleware->handle($request, function ($req) {
            $this->assertEquals('This title contains a invisible character at the end', $req->title);
        });
    }

    /**
     * Test combination of leading and trailing multiple invisible characters are trimmed [U+200E].
     */
    public function testCombinationOfLeadingAndTrailingMultipleInvisibleCharactersAreTrimmed()
    {
        $request = new Request;

        $request->merge([
            'title' => '‎‎This title contains a combination of a invisible character at beginning and the end‎‎',
        ]);

        $middleware = new TrimStrings;

        $middleware->handle($request, function ($req) {
            $this->assertEquals('This title contains a combination of a invisible character at beginning and the end', $req->title);
        });
    }

    public function testTrimStringsCanIgnoreNestedAttributesUsingWildcards()
    {
        $request = new Request;

        $request->merge([
            'users' => [
                ['name' => '  foo  ', 'role' => '  admin  '],
                ['name' => '  bar  ', 'role' => '  editor  '],
            ],
            'teams' => [
                ['name' => '  team  '],
            ],
            'orders' => [
                [
                    'items' => [
                        ['meta' => ['title' => '  foo  ', 'sku' => '  SKU-1  ', 'tags' => ['  alpha  ']]],
                    ],
                ],
                [
                    'items' => [
                        ['meta' => ['title' => '  bar  ', 'sku' => '  SKU-2  ', 'tags' => ['  beta  ']]],
                    ],
                ],
            ],
        ]);

        $middleware = new class extends TrimStrings
        {
            protected $except = [
                'users.*.name',
                'orders.*.items.*.meta.title',
                'orders.*.items.*.meta.tags.*',
            ];
        };

        $middleware->handle($request, function ($req) {
            $this->assertSame('  foo  ', $req->input('users.0.name'));
            $this->assertSame('  bar  ', $req->input('users.1.name'));
            $this->assertSame('admin', $req->input('users.0.role'));
            $this->assertSame('editor', $req->input('users.1.role'));
            $this->assertSame('team', $req->input('teams.0.name'));
            $this->assertSame('  foo  ', $req->input('orders.0.items.0.meta.title'));
            $this->assertSame('SKU-1', $req->input('orders.0.items.0.meta.sku'));
            $this->assertSame('  alpha  ', $req->input('orders.0.items.0.meta.tags.0'));

            $this->assertSame('  bar  ', $req->input('orders.1.items.0.meta.title'));
            $this->assertSame('SKU-2', $req->input('orders.1.items.0.meta.sku'));
            $this->assertSame('  beta  ', $req->input('orders.1.items.0.meta.tags.0'));
        });
    }
}
