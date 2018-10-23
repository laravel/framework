import Rule from 'foundation/Rule';

/**
 * A custom alphabet validation rule.
 *
 * Defines rule name, message or message format and a passes
 * method which verifies the input value and returns a boolean.
 */
class AlphaNumericRule extends Rule
{
    /**
     * Rule name for custom validation method.
     *
     * @var String
     */
    name = 'alphanumeric';

    /**
     * Get validity of the input value.
     *
     * @param  String  value
     * @return Boolean
     */
    passes(value)
    {
        return ! /[^a-zA-Z0-9]$/ig.test(value);
    }

    /**
     * Get error message for the alphabet rule.
     *
     * @return String
     */
    message()
    {
        return "Only alphabets and numerics are accepted.";
    }
}

export default AlphaNumericRule;
