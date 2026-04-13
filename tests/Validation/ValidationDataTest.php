<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationData;
use PHPUnit\Framework\TestCase;

class ValidationDataTest extends TestCase
{
    public function testItDoesNotModifyOriginalEloquentModelsDuringInitialization()
    {
        $person = new class extends Model {
            protected $attributes = ['email' => 'test@example.com'];
        };

        $ticket = new class extends Model {
            protected $attributes = ['departure' => 'Colombo'];
        };

        $ticket->setRelation('people', collect([$person]));

        $data = [
            'tickets' => collect([$ticket])
        ];

        $attribute = 'tickets.*.people.*.email';

        $results = ValidationData::initializeAndGatherData($attribute, $data);

        $this->assertIsArray($results);
        $this->ArrayHasKey('tickets.0.people.0.email', $results);

        $this->assertEquals('test@example.com', $person->email);
        $this->assertEquals('Colombo', $ticket->departure);
    }

    public function testInitializeAndArrayifyConvertsNestedCollectionsToArrays()
    {
        $nestedData = collect([
            'user' => collect(['name' => 'Nilukshana']),
            'tags' => ['php', 'laravel']
        ]);

        $result = ValidationData::initializeAndArrayify($nestedData);

        $this->assertIsArray($result);
        $this->assertIsArray($result['user']);
        $this->assertEquals('Nilukshana', $result['user']['name']);
    }
}
