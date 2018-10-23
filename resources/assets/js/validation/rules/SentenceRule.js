import Rule from 'foundation/Rule';

/**
 * A custom sentence validation rule.
 *
 * Defines rule name, message or message format and a passes
 * method which verifies the input value and returns a boolean.
 */
class SentenceRule extends Rule
{
    /**
     * Rule name for custom validation method.
     *
     * @var String
     */
    name = 'sentence';

    /**
     * Get validity of the input value.
     *
     * @param  String  value
     * @return Boolean
     */
    passes(value)
    {
        return ! /[^a-zA-Z\s\.]$/ig.test(value);
    }

    /**
     * Get error message for the password rule.
     *
     * @return String
     */
    message()
    {
        return "Only alphabets, spaces and dots are accepted.";
    }
}

export default SentenceRule;
