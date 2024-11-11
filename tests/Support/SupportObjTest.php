<?php

namespace Illuminate\Tests\Support;

use Illuminate\Collections\Obj;
use PHPUnit\Framework\TestCase;
use stdClass;

class SupportObjTest extends TestCase
{
    public function testDeepArrayify(): void
    {
        // Main object representing a company or organization
        $organization = new stdClass();
        $organization->name = 'Acme Corp';

        // First level nested object representing a department
        $department = new stdClass();
        $department->budget = 12000;

        // Second level nested object within $department, representing a team or project
        $team = new stdClass();
        $team->description = 'Research and Development';

        // Assign nested structure
        $department->team = $team;  // $department contains $team as a nested object
        $organization->departments = [$department]; // $organization contains $department as a nested object

        $array = Obj::deepArrayify($organization);

        $this->assertEquals(
            [
                  'name' => 'Acme Corp',
                  'departments' =>  [
                        0 =>  [
                          'budget' => 12000,
                          'team' =>  [
                            'description' => 'Research and Development'
                          ]
                        ]
                  ]
            ], $array
        );
    }
}
