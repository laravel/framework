<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Concerns\AppendsToJson;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class AppendsToJsonTest extends TestCase
{
    public function test_can_append_to_array()
    {
        $user = new UserStub;

        $user->appendToArray('first', 'sport', 'soccer');
        $this->assertEquals($user->first, ['sport' => 'soccer']);

        $user->appendToArray('first', 'subject', 'math');
        $this->assertEquals($user->first, ['sport' => 'soccer', 'subject' => 'math']);

        $user->appendToArray('first', 'sport', 'football');
        $this->assertEquals($user->first, ['sport' => 'football', 'subject' => 'math']);
    }

    public function test_can_append_to_collection()
    {
        $user = new UserStub;

        $user->appendToCollection('second', 'sport', 'soccer');
        $this->assertEquals($user->second->toArray(), ['sport' => 'soccer']);

        $user->appendToCollection('second', 'subject', 'math');
        $this->assertEquals($user->second->toArray(), ['sport' => 'soccer', 'subject' => 'math']);

        $user->appendToCollection('second', 'sport', 'football');
        $this->assertEquals($user->second->toArray(), ['sport' => 'football', 'subject' => 'math']);
    }

    public function test_can_append_to_object()
    {
        $user = new UserStub;

        $user->appendToObject('third', 'sport', 'soccer');
        $this->assertEquals($user->third->sport, 'soccer');

        $user->appendToObject('third', 'subject', 'math');
        $this->assertEquals($user->third->sport, 'soccer');
        $this->assertEquals($user->third->subject, 'math');

        $user->appendToObject('third', 'sport', 'football');
        $this->assertEquals($user->third->sport, 'football');
        $this->assertEquals($user->third->subject, 'math');
    }
}

class UserStub extends Model
{
    use AppendsToJson;

    protected $casts = [
        'first'  => 'array',
        'second' => 'collection',
        'third'  => 'object',
    ];
}
