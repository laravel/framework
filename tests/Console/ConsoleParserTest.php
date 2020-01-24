<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Parser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ConsoleParserTest extends TestCase
{
    public function testBasicParameterParsing()
    {
        $results = Parser::parse('command:name');

        $this->assertSame('command:name', $results[0]);

        $results = Parser::parse('command:name {argument} {--option}');

        $this->assertSame('command:name', $results[0]);
        $this->assertSame('argument', $results[1][0]->getName());
        $this->assertSame('option', $results[2][0]->getName());
        $this->assertFalse($results[2][0]->acceptValue());

        $results = Parser::parse('command:name {argument*} {--option=}');

        $this->assertSame('command:name', $results[0]);
        $this->assertSame('argument', $results[1][0]->getName());
        $this->assertTrue($results[1][0]->isArray());
        $this->assertTrue($results[1][0]->isRequired());
        $this->assertSame('option', $results[2][0]->getName());
        $this->assertTrue($results[2][0]->acceptValue());

        $results = Parser::parse('command:name {argument?*} {--option=*}');

        $this->assertSame('command:name', $results[0]);
        $this->assertSame('argument', $results[1][0]->getName());
        $this->assertTrue($results[1][0]->isArray());
        $this->assertFalse($results[1][0]->isRequired());
        $this->assertSame('option', $results[2][0]->getName());
        $this->assertTrue($results[2][0]->acceptValue());
        $this->assertTrue($results[2][0]->isArray());

        $results = Parser::parse('command:name {argument?* : The argument description.}    {--option=* : The option description.}');

        $this->assertSame('command:name', $results[0]);
        $this->assertSame('argument', $results[1][0]->getName());
        $this->assertSame('The argument description.', $results[1][0]->getDescription());
        $this->assertTrue($results[1][0]->isArray());
        $this->assertFalse($results[1][0]->isRequired());
        $this->assertSame('option', $results[2][0]->getName());
        $this->assertSame('The option description.', $results[2][0]->getDescription());
        $this->assertTrue($results[2][0]->acceptValue());
        $this->assertTrue($results[2][0]->isArray());

        $results = Parser::parse('command:name
            {argument?* : The argument description.}
            {--option=* : The option description.}');

        $this->assertSame('command:name', $results[0]);
        $this->assertSame('argument', $results[1][0]->getName());
        $this->assertSame('The argument description.', $results[1][0]->getDescription());
        $this->assertTrue($results[1][0]->isArray());
        $this->assertFalse($results[1][0]->isRequired());
        $this->assertSame('option', $results[2][0]->getName());
        $this->assertSame('The option description.', $results[2][0]->getDescription());
        $this->assertTrue($results[2][0]->acceptValue());
        $this->assertTrue($results[2][0]->isArray());
    }

    public function testShortcutNameParsing()
    {
        $results = Parser::parse('command:name {--o|option}');

        $this->assertSame('o', $results[2][0]->getShortcut());
        $this->assertSame('option', $results[2][0]->getName());
        $this->assertFalse($results[2][0]->acceptValue());

        $results = Parser::parse('command:name {--o|option=}');

        $this->assertSame('o', $results[2][0]->getShortcut());
        $this->assertSame('option', $results[2][0]->getName());
        $this->assertTrue($results[2][0]->acceptValue());

        $results = Parser::parse('command:name {--o|option=*}');

        $this->assertSame('command:name', $results[0]);
        $this->assertSame('o', $results[2][0]->getShortcut());
        $this->assertSame('option', $results[2][0]->getName());
        $this->assertTrue($results[2][0]->acceptValue());
        $this->assertTrue($results[2][0]->isArray());

        $results = Parser::parse('command:name {--o|option=* : The option description.}');

        $this->assertSame('command:name', $results[0]);
        $this->assertSame('o', $results[2][0]->getShortcut());
        $this->assertSame('option', $results[2][0]->getName());
        $this->assertSame('The option description.', $results[2][0]->getDescription());
        $this->assertTrue($results[2][0]->acceptValue());
        $this->assertTrue($results[2][0]->isArray());

        $results = Parser::parse('command:name
            {--o|option=* : The option description.}');

        $this->assertSame('command:name', $results[0]);
        $this->assertSame('o', $results[2][0]->getShortcut());
        $this->assertSame('option', $results[2][0]->getName());
        $this->assertSame('The option description.', $results[2][0]->getDescription());
        $this->assertTrue($results[2][0]->acceptValue());
        $this->assertTrue($results[2][0]->isArray());
    }

    public function testDefaultValueParsing()
    {
        $results = Parser::parse('command:name {argument=defaultArgumentValue} {--option=defaultOptionValue}');

        $this->assertFalse($results[1][0]->isRequired());
        $this->assertSame('defaultArgumentValue', $results[1][0]->getDefault());
        $this->assertTrue($results[2][0]->acceptValue());
        $this->assertSame('defaultOptionValue', $results[2][0]->getDefault());

        $results = Parser::parse('command:name {argument=*defaultArgumentValue1,defaultArgumentValue2} {--option=*defaultOptionValue1,defaultOptionValue2}');

        $this->assertTrue($results[1][0]->isArray());
        $this->assertFalse($results[1][0]->isRequired());
        $this->assertEquals(['defaultArgumentValue1', 'defaultArgumentValue2'], $results[1][0]->getDefault());
        $this->assertTrue($results[2][0]->acceptValue());
        $this->assertTrue($results[2][0]->isArray());
        $this->assertEquals(['defaultOptionValue1', 'defaultOptionValue2'], $results[2][0]->getDefault());
    }

    public function testArgumentDefaultValue()
    {
        $results = Parser::parse('command:name {argument= : The argument description.}');
        $this->assertNull($results[1][0]->getDefault());

        $results = Parser::parse('command:name {argument=default : The argument description.}');
        $this->assertSame('default', $results[1][0]->getDefault());
    }

    public function testOptionDefaultValue()
    {
        $results = Parser::parse('command:name {--option= : The option description.}');
        $this->assertNull($results[2][0]->getDefault());

        $results = Parser::parse('command:name {--option=default : The option description.}');
        $this->assertSame('default', $results[2][0]->getDefault());
    }

    public function testNameIsSpacesException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to determine command name from signature.');

        Parser::parse(" \t\n\r\x0B\f");
    }

    public function testNameInEmptyException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to determine command name from signature.');

        Parser::parse('');
    }
}
