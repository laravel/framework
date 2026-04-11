<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationIssue59650Test extends TestCase
{
    public function testValidationDoesNotModifyOriginalEloquentModelsInNestedCollections(): void
    {
        $person1 = new Issue59650Person(['email' => 'user1@example.com']);
        $person2 = new Issue59650Person(['email' => 'user2@example.com']);

        $ticket1 = new Issue59650Ticket(['departure' => 'NYC']);
        $ticket1->setAttribute('people', collect([$person1]));

        $ticket2 = new Issue59650Ticket(['departure' => 'LDN']);
        $ticket2->setAttribute('people', collect([$person2]));

        $data = [
            'tickets' => collect([$ticket1, $ticket2]),
        ];

        $rules = [
            'tickets.*.departure' => 'required|string',
            'tickets.*.people.*.email' => 'required|email',
        ];

        $validator = new Validator(
            new Translator(new ArrayLoader(), 'en'),
            $data,
            $rules
        );

        // Before validation, check data
        $this->assertEquals('NYC', $ticket1->departure);
        $this->assertEquals('user1@example.com', $person1->email);

        $validator->passes();

        // After validation, data MUST be unchanged
        $this->assertEquals('NYC', $ticket1->departure);
        $this->assertEquals('user1@example.com', $person1->email);

        // Specifically check that attributes list didn't get 'null' merged in
        $this->assertEquals('NYC', $ticket1->departure);
        $this->assertEquals('user1@example.com', $person1->email);
    }

    public function testIndirectModificationOfOverloadedPropertyErrorIsFixed(): void
    {
        $person = new Issue59650Person(['email' => 'user@example.com']);
        $ticket = new Issue59650Ticket(['departure' => 'NYC']);
        $ticket->setAttribute('people', collect([$person]));

        $data = [
            'tickets' => collect([$ticket]),
        ];

        $rules = [
            'tickets.*.people.*.email' => 'required|email',
        ];

        $validator = new Validator(
            new Translator(new ArrayLoader(), 'en'),
            $data,
            $rules
        );

        $this->assertTrue($validator->passes());
    }
}

/**
 * @property string $departure
 */
class Issue59650Ticket extends Model
{
    protected $guarded = [];
}

/**
 * @property string $email
 */
class Issue59650Person extends Model
{
    protected $guarded = [];
}
