<?php

namespace Illuminate\Tests\Integration\Foundation;

use Exception;
use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\HelperFunctionsBlacklist;

/**
 * @group integration
 */
class HelperFunctionsBlacklistTest extends TestCase
{
    /** @test */
    public function no_configuration_file_will_throw_exception()
    {
        $this->expectException(Exception::class);
        HelperFunctionsBlacklist::loadConfiguration(__DIR__ . '/Fixtures/HelperFunctionsBlacklist/non-existant.composer.json');
    }

    /** @test */
    public function an_empty_configuration_will_allow_all_helpers()
    {
        HelperFunctionsBlacklist::loadConfiguration(__DIR__ . '/Fixtures/HelperFunctionsBlacklist/empty.composer.json');
        $this->assertTrue(HelperFunctionsBlacklist::isEnabled('app'));
    }

    /** @test */
    public function an_empty_configuration_with_laravel_key_will_allow_all_helpers()
    {
        HelperFunctionsBlacklist::loadConfiguration(__DIR__ . '/Fixtures/HelperFunctionsBlacklist/empty-with-laravel-key.composer.json');
        $this->assertTrue(HelperFunctionsBlacklist::isEnabled('app'));
    }

    /** @test */
    public function false_will_allow_all_helpers()
    {
        HelperFunctionsBlacklist::loadConfiguration(__DIR__ . '/Fixtures/HelperFunctionsBlacklist/false.composer.json');
        $this->assertTrue(HelperFunctionsBlacklist::isEnabled('app'));
        $this->assertTrue(HelperFunctionsBlacklist::isEnabled('resolve'));
    }

    /** @test */
    public function true_will_blacklist_all_helpers()
    {
        HelperFunctionsBlacklist::loadConfiguration(__DIR__ . '/Fixtures/HelperFunctionsBlacklist/true.composer.json');
        $this->assertFalse(HelperFunctionsBlacklist::isEnabled('app'));
        $this->assertFalse(HelperFunctionsBlacklist::isEnabled('resolve'));
    }

    /** @test */
    public function an_empty_array_will_allow_all_helpers()
    {
        HelperFunctionsBlacklist::loadConfiguration(__DIR__ . '/Fixtures/HelperFunctionsBlacklist/empty-array.composer.json');
        $this->assertTrue(HelperFunctionsBlacklist::isEnabled('app'));
        $this->assertTrue(HelperFunctionsBlacklist::isEnabled('resolve'));
    }

    /** @test */
    public function an_array_of_strings_will_block_those_helpers_but_allow_others()
    {
        HelperFunctionsBlacklist::loadConfiguration(__DIR__ . '/Fixtures/HelperFunctionsBlacklist/array-of-strings.composer.json');
        $this->assertFalse(HelperFunctionsBlacklist::isEnabled('app'));
        $this->assertTrue(HelperFunctionsBlacklist::isEnabled('resolve'));
    }

    /** @test */
    public function non_string_values_in_array_will_throw_exception()
    {
        $this->expectException(Exception::class);
        HelperFunctionsBlacklist::loadConfiguration(__DIR__ . '/Fixtures/HelperFunctionsBlacklist/non-string-values-in-array.composer.json');
    }

    /** @test */
    public function values_that_are_not_a_string_or_boolean_will_throw_an_exception()
    {
        $this->expectException(Exception::class);
        HelperFunctionsBlacklist::loadConfiguration(__DIR__ . '/Fixtures/HelperFunctionsBlacklist/value-is-not-string-or-boolean.json');
    }
}
