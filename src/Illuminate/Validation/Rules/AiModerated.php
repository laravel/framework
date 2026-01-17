<?php

namespace Illuminate\Validation\Rules;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\AiModerationVerifier;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Traits\Conditionable;
use InvalidArgumentException;

class AiModerated implements DataAwareRule, ValidationRule, ValidatorAwareRule
{
    use Conditionable;

    /**
     * The validator performing the validation.
     *
     * @var \Illuminate\Contracts\Validation\Validator
     */
    protected $validator;

    /**
     * The data under validation.
     *
     * @var array
     */
    protected $data;

    /**
     * The categories to check for moderation.
     *
     * @var array<string>
     */
    protected array $categories = [];

    /**
     * The threshold for flagging content (0.0 to 1.0).
     *
     * @var float
     */
    protected float $threshold = 0.5;

    /**
     * The AI provider to use for moderation.
     *
     * @var string|null
     */
    protected ?string $provider = null;

    /**
     * The number of seconds to cache moderation results.
     *
     * @var int|null
     */
    protected ?int $cacheFor = null;

    /**
     * Custom verifier callback.
     *
     * @var callable|null
     */
    protected $customVerifier = null;

    /**
     * The callback that will generate the "default" version of the rule.
     *
     * @var string|array|callable|null
     */
    public static $defaultCallback;

    /**
     * Set the default callback to be used for determining the default configuration.
     *
     * If no arguments are passed, the default configuration will be returned.
     *
     * @param  static|callable|null  $callback
     * @return static|void
     */
    public static function defaults($callback = null)
    {
        if (is_null($callback)) {
            return static::default();
        }

        if (! is_callable($callback) && ! $callback instanceof static) {
            throw new InvalidArgumentException('The given callback should be callable or an instance of '.static::class);
        }

        static::$defaultCallback = $callback;
    }

    /**
     * Get the default configuration of the rule.
     *
     * @return static
     */
    public static function default()
    {
        $rule = is_callable(static::$defaultCallback)
            ? call_user_func(static::$defaultCallback)
            : static::$defaultCallback;

        return $rule instanceof static ? $rule : new static;
    }

    /**
     * Set the performing validator.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Specify the categories to check during moderation.
     *
     * @param  array<string>|string  $categories
     * @return $this
     */
    public function categories(array|string $categories)
    {
        $this->categories = is_array($categories) ? $categories : func_get_args();

        return $this;
    }

    /**
     * Check for hate speech content.
     *
     * @return $this
     */
    public function noHate()
    {
        $this->categories[] = 'hate';

        return $this;
    }

    /**
     * Check for threatening hate content.
     *
     * @return $this
     */
    public function noHateThreatening()
    {
        $this->categories[] = 'hate/threatening';

        return $this;
    }

    /**
     * Check for harassment content.
     *
     * @return $this
     */
    public function noHarassment()
    {
        $this->categories[] = 'harassment';

        return $this;
    }

    /**
     * Check for threatening harassment content.
     *
     * @return $this
     */
    public function noHarassmentThreatening()
    {
        $this->categories[] = 'harassment/threatening';

        return $this;
    }

    /**
     * Check for self-harm content.
     *
     * @return $this
     */
    public function noSelfHarm()
    {
        $this->categories[] = 'self-harm';

        return $this;
    }

    /**
     * Check for self-harm intent content.
     *
     * @return $this
     */
    public function noSelfHarmIntent()
    {
        $this->categories[] = 'self-harm/intent';

        return $this;
    }

    /**
     * Check for self-harm instructions content.
     *
     * @return $this
     */
    public function noSelfHarmInstructions()
    {
        $this->categories[] = 'self-harm/instructions';

        return $this;
    }

    /**
     * Check for sexual content.
     *
     * @return $this
     */
    public function noSexual()
    {
        $this->categories[] = 'sexual';

        return $this;
    }

    /**
     * Check for sexual content involving minors.
     *
     * @return $this
     */
    public function noSexualMinors()
    {
        $this->categories[] = 'sexual/minors';

        return $this;
    }

    /**
     * Check for violent content.
     *
     * @return $this
     */
    public function noViolence()
    {
        $this->categories[] = 'violence';

        return $this;
    }

    /**
     * Check for graphic violence content.
     *
     * @return $this
     */
    public function noViolenceGraphic()
    {
        $this->categories[] = 'violence/graphic';

        return $this;
    }

    /**
     * Apply strict moderation by checking all known categories.
     *
     * @return $this
     */
    public function strict()
    {
        $this->categories = [
            'hate',
            'hate/threatening',
            'harassment',
            'harassment/threatening',
            'self-harm',
            'self-harm/intent',
            'self-harm/instructions',
            'sexual',
            'sexual/minors',
            'violence',
            'violence/graphic',
        ];

        return $this;
    }

    /**
     * Set the threshold for flagging content.
     *
     * @param  float  $threshold
     * @return $this
     */
    public function threshold(float $threshold)
    {
        $this->threshold = max(0.0, min(1.0, $threshold));

        return $this;
    }

    /**
     * Set a low threshold for stricter moderation.
     *
     * @return $this
     */
    public function strictThreshold()
    {
        $this->threshold = 0.2;

        return $this;
    }

    /**
     * Set a high threshold for lenient moderation.
     *
     * @return $this
     */
    public function lenientThreshold()
    {
        $this->threshold = 0.8;

        return $this;
    }

    /**
     * Specify the AI provider to use for moderation.
     *
     * @param  string  $provider
     * @return $this
     */
    public function using(string $provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Cache the moderation results for the given number of seconds.
     *
     * @param  int  $seconds
     * @return $this
     */
    public function remember(int $seconds)
    {
        $this->cacheFor = $seconds;

        return $this;
    }

    /**
     * Use a custom verifier callback for moderation.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function verifyUsing(callable $callback)
    {
        $this->customVerifier = $callback;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $result = $this->moderate($value);

        if ($result->flagged()) {
            $flaggedCategories = $result->flaggedCategories();

            if (empty($flaggedCategories)) {
                $fail('validation.ai_moderated.flagged')->translate();
            } else {
                $fail('validation.ai_moderated.categories')->translate([
                    'categories' => implode(', ', $flaggedCategories),
                ]);
            }
        }
    }

    /**
     * Perform the AI moderation check.
     *
     * @param  string  $value
     * @return \Illuminate\Contracts\Validation\AiModerationResult
     */
    protected function moderate(string $value)
    {
        if ($this->customVerifier !== null) {
            return call_user_func($this->customVerifier, [
                'value' => $value,
                'categories' => $this->categories,
                'threshold' => $this->threshold,
                'provider' => $this->provider,
                'cacheFor' => $this->cacheFor,
            ]);
        }

        return Container::getInstance()->make(AiModerationVerifier::class)->verify([
            'value' => $value,
            'categories' => $this->categories,
            'threshold' => $this->threshold,
            'provider' => $this->provider,
            'cacheFor' => $this->cacheFor,
        ]);
    }

    /**
     * Get information about the current state of the moderation rules.
     *
     * @return array
     */
    public function appliedRules()
    {
        return [
            'categories' => $this->categories,
            'threshold' => $this->threshold,
            'provider' => $this->provider,
            'cacheFor' => $this->cacheFor,
        ];
    }
}
