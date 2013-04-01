<?php

use Illuminate\Support\Str;

class SupportStrTest extends PHPUnit_Framework_TestCase
{
  /**
   * Test the Str::words method.
   *
   * @group laravel
   */
  public function testStringCanBeLimitedByWords()
  {
    $this->assertEquals('Taylor...', Str::words('Taylor Otwell', 1));
    $this->assertEquals('Taylor___', Str::words('Taylor Otwell', 1, '___'));
    $this->assertEquals('Taylor Otwell', Str::words('Taylor Otwell', 3));
  }

  /**
   * Test the Str::title method.
   *
   * @group laravel
   */
  public function testStringCanBeCapitalizedUsingTitle()
  {
    $this->assertEquals('Ένα Πλαίσιο Για Το Web Artisans', Str::title('Ένα πλαίσιο για το web Artisans'));
    $this->assertEquals('A Framework For Web Artisans', Str::title('A framework For web Artisans'));
  }

  /**
   * Test the Str::asciiTitle method.
   *
   * @group laravel
   */
  public function testStringCanBeCapitalizedUsingAsciiTitle()
  {
    $this->assertEquals('??? ??????? ??? ?? Web Artisans', Str::asciiTitle('Ένα πλαίσιο για το web Artisans'));
    $this->assertEquals('A Framework For Web Artisans', Str::asciiTitle('A framework For web Artisans'));
  }
}
