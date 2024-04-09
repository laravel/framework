<?php

namespace Illuminate\Translation;

use Illuminate\Validation\StopOnFailureException;

trait CreatesPotentiallyTranslatedStrings
{
    /**
     * Create a pending potentially translated string.
     *
     * @param  string  $attribute
     * @param  string|null  $message
     * @return \Illuminate\Translation\PotentiallyTranslatedString
     */
    protected function pendingPotentiallyTranslatedString($attribute, $message)
    {
        $destructor = $message === null
            ? fn ($message) => $this->messages[] = $message
            : fn ($message) => $this->messages[$attribute] = $message;

        $onFailure = function ($potentiallyTranslatedString) {
            $this->failed = true;

            // Unsetting this object ensures the exception thrown in the destructor is caught...
            unset($potentiallyTranslatedString);
        };

        return new class($message ?? $attribute, $this->validator->getTranslator(), $destructor, $onFailure) extends PotentiallyTranslatedString
        {
            /**
             * Indicates if the validation callback failed.
             *
             * @var bool
             */
            public $failed = true;

            /**
             * The callback to call when the object destructs.
             *
             * @var \Closure
             */
            protected $destructor;

            /**
             * The callback to call when the rule fails.
             *
             * @var \Closure
             */
            protected $onFailure;

            /**
             * Indicates if the validation should stop if this check fails.
             *
             * @var bool
             */
            protected $stopOnFailure = false;

            /**
             * Create a new pending potentially translated string.
             *
             * @param  string  $message
             * @param  \Illuminate\Contracts\Translation\Translator  $translator
             * @param  \Closure  $destructor
             * @return void
             */
            public function __construct($message, $translator, $destructor, $onFailure)
            {
                parent::__construct($message, $translator);

                $this->destructor = $destructor;
                $this->onFailure = $onFailure;
            }

            /**
             * Raise the error message if the given condition is true.
             *
             * @param  mixed  $failed
             * @return $this
             */
            public function when($failed)
            {
                $this->failed = value($failed);

                return $this;
            }

            /**
             * Raise the error message unless the given condition is true.
             *
             * @param  mixed  $failed
             * @return $this
             */
            public function unless($failed)
            {
                $this->failed = ! value($failed);

                return $this;
            }

            /**
             * Stop if a failure happened.
             *
             * @param  bool  $stopOnFailure
             * @return $this
             */
            public function stopOnFailure($stopOnFailure = true)
            {
                $this->stopOnFailure = $stopOnFailure;

                return $this;
            }

            /**
             * Handle the object's destruction.
             *
             * @return void
             */
            public function __destruct()
            {
                if (! $this->failed) {
                    return;
                }

                ($this->onFailure)($this);

                ($this->destructor)($this->toString());

                if ($this->stopOnFailure) {
                    throw new StopOnFailureException();
                }
            }
        };
    }
}
