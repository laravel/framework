<?php

namespace Tests\Validation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rules\DistinctWithin;
use PHPUnit\Framework\TestCase;

class ValidationDistinctWithinTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_prevents_duplicate_submission_within_specified_time()
    {
        $requestData = ['email' => 'test@example.com'];

        // Simulate a request
        request()->replace($requestData);

        $rule = new DistinctWithin(60); // 60 seconds

        // First submission should pass
        $this->assertTrue($rule->passes('email', $requestData['email']), 'First submission should pass');

        // Second submission within 60 seconds should fail
        $this->assertFalse($rule->passes('email', $requestData['email']), 'Second submission should fail within 60 seconds');
    }
}
