<?php

namespace Illuminate\Testing\Concerns;

use Illuminate\Testing\Assert as PHPUnit;

trait AssertsStatusCodes
{
    /**
     * Assert that the response has a 200 "OK" status code.
     *
     * @return $this
     */
    public function assertOk()
    {
        return $this->assertStatus(200);
    }

    /**
     * Assert that the response has a 201 "Created" status code.
     *
     * @return $this
     */
    public function assertCreated()
    {
        return $this->assertStatus(201);
    }

    /**
     * Assert that the response has a 202 "Accepted" status code.
     *
     * @return $this
     */
    public function assertAccepted()
    {
        return $this->assertStatus(202);
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
     * Assert that the response has a 301 "Moved Permanently" status code.
     *
     * @return $this
     */
    public function assertMovedPermanently()
    {
        return $this->assertStatus(301);
    }

    /**
     * Assert that the response has a 302 "Found" status code.
     *
     * @return $this
     */
    public function assertFound()
    {
        return $this->assertStatus(302);
    }

    /**
     * Assert that the response has a 400 "Bad Request" status code.
     *
     * @return $this
     */
    public function assertBadRequest()
    {
        return $this->assertStatus(400);
    }

    /**
     * Assert that the response has a 401 "Unauthorized" status code.
     *
     * @return $this
     */
    public function assertUnauthorized()
    {
        return $this->assertStatus(401);
    }

    /**
     * Assert that the response has a 402 "Payment Required" status code.
     *
     * @return $this
     */
    public function assertPaymentRequired()
    {
        return $this->assertStatus(402);
    }

    /**
     * Assert that the response has a 403 "Forbidden" status code.
     *
     * @return $this
     */
    public function assertForbidden()
    {
        return $this->assertStatus(403);
    }

    /**
     * Assert that the response has a 404 "Not Found" status code.
     *
     * @return $this
     */
    public function assertNotFound()
    {
        return $this->assertStatus(404);
    }

    /**
     * Assert that the response has a 408 "Request Timeout" status code.
     *
     * @return $this
     */
    public function assertRequestTimeout()
    {
        return $this->assertStatus(408);
    }

    /**
     * Assert that the response has a 409 "Conflict" status code.
     *
     * @return $this
     */
    public function assertConflict()
    {
        return $this->assertStatus(409);
    }

    /**
     * Assert that the response has a 415 "Unsupported Media Type" status code.
     *
     * @return $this
     */
    public function assertUnsupportedMediaType()
    {
        return $this->assertStatus(415);
    }

    /**
     * Assert that the response has a 422 "Unprocessable Entity" status code.
     *
     * @return $this
     */
    public function assertUnprocessable()
    {
        return $this->assertStatus(422);
    }

    /**
     * Assert that the response has a 429 "Too Many Requests" status code.
     *
     * @return $this
     */
    public function assertTooManyRequests()
    {
        return $this->assertStatus(429);
    }
}
