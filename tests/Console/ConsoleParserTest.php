<?php

use Illuminate\Console\Parser;

class ConsoleParserTest extends PHPUnit_Framework_TestCase
{
    public function testBasicParameterParsing()
    {
        $results = Parser::parse('command:name');

        $this->assertEquals('command:name', $results[0]);

        $results = Parser::parse('command:name {argument} {--option}');

        $this->assertEquals('command:name', $results[0]);
        $this->assertEquals('argument', $results[1][0]->getName());
        $this->assertEquals('option', $results[2][0]->getName());
        $this->assertFalse($results[2][0]->acceptValue());

        $results = Parser::parse('command:name {argument*} {--option=}');

        $this->assertEquals('command:name', $results[0]);
        $this->assertEquals('argument', $results[1][0]->getName());
        $this->assertTrue($results[1][0]->isArray());
        $this->assertTrue($results[1][0]->isRequired());
        $this->assertEquals('option', $results[2][0]->getName());
        $this->assertTrue($results[2][0]->acceptValue());

        $results = Parser::parse('command:name {argument?*} {--option=*}');

        $this->assertEquals('command:name', $results[0]);
        $this->assertEquals('argument', $results[1][0]->getName());
        $this->assertTrue($results[1][0]->isArray());
        $this->assertFalse($results[1][0]->isRequired());
        $this->assertEquals('option', $results[2][0]->getName());
        $this->assertTrue($results[2][0]->acceptValue());
        $this->assertTrue($results[2][0]->isArray());

        $results = Parser::parse('command:name {argument?* : The argument description.}    {--option=* : The option description.}');

        $this->assertEquals('command:name', $results[0]);
        $this->assertEquals('argument', $results[1][0]->getName());
        $this->assertEquals('The argument description.', $results[1][0]->getDescription());
        $this->assertTrue($results[1][0]->isArray());
        $this->assertFalse($results[1][0]->isRequired());
        $this->assertEquals('option', $results[2][0]->getName());
        $this->assertEquals('The option description.', $results[2][0]->getDescription());
        $this->assertTrue($results[2][0]->acceptValue());
        $this->assertTrue($results[2][0]->isArray());

        $results = Parser::parse('command:name
            {argument?* : The argument description.}
            {--option=* : The option description.}');

        $this->assertEquals('command:name', $results[0]);
        $this->assertEquals('argument', $results[1][0]->getName());
        $this->assertEquals('The argument description.', $results[1][0]->getDescription());
        $this->assertTrue($results[1][0]->isArray());
        $this->assertFalse($results[1][0]->isRequired());
        $this->assertEquals('option', $results[2][0]->getName());
        $this->assertEquals('The option description.', $results[2][0]->getDescription());
        $this->assertTrue($results[2][0]->acceptValue());
        $this->assertTrue($results[2][0]->isArray());
    }

    public function testShortcutNameParsing()
    {
        $results = Parser::parse('command:name {--o|option}');

        $this->assertEquals('o', $results[2][0]->getShortcut());
        $this->assertEquals('option', $results[2][0]->getName());
        $this->assertFalse($results[2][0]->acceptValue());

        $results = Parser::parse('command:name {--o|option=}');

        $this->assertEquals('o', $results[2][0]->getShortcut());
        $this->assertEquals('option', $results[2][0]->getName());
        $this->assertTrue($results[2][0]->acceptValue());

        $results = Parser::parse('command:name {--o|option=*}');

        $this->assertEquals('command:name', $results[0]);
        $this->assertEquals('o', $results[2][0]->getShortcut());
        $this->assertEquals('option', $results[2][0]->getName());
        $this->assertTrue($results[2][0]->acceptValue());
        $this->assertTrue($results[2][0]->isArray());

        $results = Parser::parse('command:name {--o|option=* : The option description.}');

        $this->assertEquals('command:name', $results[0]);
        $this->assertEquals('o', $results[2][0]->getShortcut());
        $this->assertEquals('option', $results[2][0]->getName());
        $this->assertEquals('The option description.', $results[2][0]->getDescription());
        $this->assertTrue($results[2][0]->acceptValue());
        $this->assertTrue($results[2][0]->isArray());

        $results = Parser::parse('command:name
            {--o|option=* : The option description.}');

        $this->assertEquals('command:name', $results[0]);
        $this->assertEquals('o', $results[2][0]->getShortcut());
        $this->assertEquals('option', $results[2][0]->getName());
        $this->assertEquals('The option description.', $results[2][0]->getDescription());
        $this->assertTrue($results[2][0]->acceptValue());
        $this->assertTrue($results[2][0]->isArray());
    }

    public function testDefaultParsing()
    {
        $results = Parser::parse('command:name {arg1=default}');
        $defaults = $this->getArgDefaults($results);

        $this->assertEquals('default', $defaults[0]);

        $results = Parser::parse('command:name {arg1=} {arg2=null}');
        $defaults = $this->getArgDefaults($results);

        $this->assertNull($defaults[0]);
        $this->assertNull($defaults[1]);

        $results = Parser::parse('command:name {arg1=true} {arg2=false}');
        $defaults = $this->getArgDefaults($results);

        $this->assertTrue($defaults[0]);
        $this->assertInternalType('bool', $defaults[0]);
        $this->assertFalse($defaults[1]);
        $this->assertInternalType('bool', $defaults[1]);

        $results = Parser::parse('command:name {arg1=2}');
        $defaults = $this->getArgDefaults($results);

        $this->assertEquals(2, $defaults[0]);
        $this->assertInternalType('int', $defaults[0]);

        $results = Parser::parse('command:name {arg1=2.3} {arg2=.3} {arg3=0.9}');
        $defaults = $this->getArgDefaults($results);

        $this->assertEquals(2.3, $defaults[0]);
        $this->assertInternalType('float', $defaults[0]);
        $this->assertEquals(0.3, $defaults[1]);
        $this->assertInternalType('float', $defaults[0]);
        $this->assertEquals(0.9, $defaults[2]);
        $this->assertInternalType('float', $defaults[0]);

        $results = Parser::parse('command:name {arg1=[]} {arg2=array(1,2)} {arg3=["red" => 0, "green" => 0, "blue" => 0]}');
        $defaults = $this->getArgDefaults($results);

        $this->assertEquals([], $defaults[0]);
        $this->assertEquals([1, 2], $defaults[1]);
        $this->assertEquals(['red' => 0, 'green' => 0, 'blue' => 0], $defaults[2]);

        $results = Parser::parse('command:name {arg1={"red": 0, "green": 0, "blue": 0}} {arg2={"prop": {"nested": [2, 4]}, "not": 5}}');
        $defaults = $this->getArgDefaults($results);

        $this->assertEquals((object) ['red' => 0, 'green' => 0, 'blue' => 0], $defaults[0]);
        $this->assertEquals((object) ['prop' => (object) ['nested' => [2, 4]], 'not' => 5], $defaults[1]);
    }

    protected function getArgDefaults($results)
    {
        $args = $results[1];

        return array_map(function ($arg) {
            return $arg->getDefault();
        }, $args);
    }
}
