<?php

namespace Illuminate\Testing\Concerns;

use Illuminate\Testing\Assert as PHPUnit;

trait AssertsStatusCodes
{
    /**
     * Assert that the response has a 200 status code.
     *
     * @return $this
     */
    public function assertOk()
    {
        return $this->assertStatus(200);
    }

    /**
     * Assert that the response has a 201 status code.
     *
     * @return $this
     */
    public function assertCreated()
    {
        return $this->assertStatus(201);
    }

    /**
     * Assert that the response has the given status code and no content.
     *
     * @param  int  $status
     * @return $this
     */
    public function assertNoContent($status = 204)
    {
        $this->assertStatus($status);

        PHPUnit::assertEmpty($this->getContent(), 'Response content is not empty.');

        return $this;
    }

    /**
     * Assert that the response has an unauthorized status code.
     *
     * @return $this
     */
    public function assertUnauthorized()
    {
        return $this->assertStatus(401);
    }

    /**
     * Assert that the response has a forbidden status code.
     *
     * @return $this
     */
    public function assertForbidden()
    {
        return $this->assertStatus(403);
    }

    /**
     * Assert that the response has a not found status code.
     *
     * @return $this
     */
    public function assertNotFound()
    {
        return $this->assertStatus(404);
    }

    /**
     * Assert that the response has a 422 status code.
     *
     * @return $this
     */
    public function assertUnprocessable()
    {
        return $this->assertStatus(422);
    }
}
