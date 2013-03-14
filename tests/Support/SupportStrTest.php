<?php

use Illuminate\Support\Str;

class SupportStrTest extends PHPUnit_Framework_TestCase
{
  /**
   * Test the Str::limitWords method.
   *
   * @group laravel
   */
  public function testStringCanBeLimitedByWords()
  {
    $this->assertEquals('Taylor...', Str::limitWords('Taylor Otwell', 1));
    $this->assertEquals('Taylor___', Str::limitWords('Taylor Otwell', 1, '___'));
    $this->assertEquals('Taylor Otwell', Str::limitWords('Taylor Otwell', 3));
  }
}