<?php

namespace Illuminate\Testing\Concerns;

use Illuminate\Http\Response;
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
        return $this->assertStatus(Response::HTTP_OK);
    }

    /**
     * Assert that the response has a 201 "Created" status code.
     *
     * @return $this
     */
    public function assertCreated()
    {
        return $this->assertStatus(Response::HTTP_CREATED);
    }

    /**
     * Assert that the response has a 202 "Accepted" status code.
     *
     * @return $this
     */
    public function assertAccepted()
    {
        return $this->assertStatus(Response::HTTP_ACCEPTED);
    }

    /**
     * Assert that the response has the given status code and no content.
     *
     * @param  int  $status
     * @return $this
     */
    public function assertNoContent($status = Response::HTTP_NO_CONTENT)
    {
        $this->assertStatus($status);

        PHPUnit::assertEmpty($this->getContent(), 'Response content is not empty.');

        return $this;
    }

    /**
     * Assert that the response has a 301 "Moved Permanently" status code.
     *
     * @param  int  $status
     * @return $this
     */
    public function assertMovedPermanently()
    {
        return $this->assertStatus(Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * Assert that the response has a 302 "Found" status code.
     *
     * @param  int  $status
     * @return $this
     */
    public function assertFound()
    {
        return $this->assertStatus(Response::HTTP_FOUND);
    }

    /**
     * Assert that the response has a 400 "Bad Request" status code.
     *
     * @return $this
     */
    public function assertBadRequest()
    {
        return $this->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Assert that the response has a 401 "Unauthorized" status code.
     *
     * @return $this
     */
    public function assertUnauthorized()
    {
        return $this->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Assert that the response has a 402 "Payment Required" status code.
     *
     * @return $this
     */
    public function assertPaymentRequired()
    {
        return $this->assertStatus(Response::HTTP_PAYMENT_REQUIRED);
    }

    /**
     * Assert that the response has a 403 "Forbidden" status code.
     *
     * @return $this
     */
    public function assertForbidden()
    {
        return $this->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * Assert that the response has a 404 "Not Found" status code.
     *
     * @return $this
     */
    public function assertNotFound()
    {
        return $this->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * Assert that the response has a 408 "Request Timeout" status code.
     *
     * @return $this
     */
    public function assertRequestTimeout()
    {
        return $this->assertStatus(Response::HTTP_REQUEST_TIMEOUT);
    }

    /**
     * Assert that the response has a 409 "Conflict" status code.
     *
     * @return $this
     */
    public function assertConflict()
    {
        return $this->assertStatus(Response::HTTP_CONFLICT);
    }

    /**
     * Assert that the response has a 422 "Unprocessable Entity" status code.
     *
     * @return $this
     */
    public function assertUnprocessable()
    {
        return $this->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Assert that the response has a 429 "Too Many Requests" status code.
     *
     * @return $this
     */
    public function assertTooManyRequests()
    {
        return $this->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    }
}
