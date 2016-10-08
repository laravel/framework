<?php

namespace Illuminate\Foundation\Testing\Constraints;

use Closure;
use Swift_Message;
use PHPUnit_Framework_Assert as Assert;
use PHPUnit_Framework_ExpectationFailedException as ExpectationException;

class Mail
{
    protected $constraints = [];
    protected $lastErrorMessage;

    public function __construct(Closure $assertions = null)
    {
        if ($assertions) {
            $assertions($this);
        }
    }

    public function to($users)
    {
        return $this->addConstraint('To', $users);
    }

    public function subject($subject)
    {
        return $this->addConstraint('Subject', $subject);
    }

    public function htmlLink($text, $url = null)
    {
        return $this->addConstraint('HtmlLink', compact('text', 'url'));
    }

    public function matches(Swift_Message $message)
    {
        try {
            $this->runAssertions($message);

            return true;
        } catch (ExpectationException $e) {
            return dd($e->getMessage());
        }
    }

    /**
     * @param \Swift_Message $message
     */
    protected function runAssertions(Swift_Message $message)
    {
        foreach ($this->constraints as $constraint => $expected) {
            $this->{'assert'.$constraint}($message, $expected);
        }
    }

    protected function addConstraint($constraint, $expected)
    {
        $this->constraints[$constraint] = $expected;

        return $this;
    }

    protected function assertTo(Swift_Message $message, $expected)
    {
        Assert::assertSame((array) $expected, array_keys($message->getTo()));
    }

    protected function assertSubject(Swift_Message $message, $expected)
    {
        Assert::assertSame($expected, $message->getSubject());
    }

    protected function assertHtmlLink(Swift_Message $message, $expected)
    {
        Assert::assertThat($message->getBody(), new HasLink($expected['text'], $expected['url']));
    }

    public function __toString()
    {
        return json_encode($this->constraints);
    }
}
