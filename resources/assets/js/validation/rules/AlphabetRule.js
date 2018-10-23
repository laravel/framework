import Rule from 'foundation/Rule';

/**
 * A custom alphabet validation rule.
 *
 * Defines rule name, message or message format and a passes
 * method which verifies the input value and returns a boolean.
 */
class AlphabetRule extends Rule
{
    /**
     * Rule name for custom validation method.
     *
     * @var String
     */
    name = 'alphabet';

    /**
     * Get validity of the input value.
     *
     * @param  String  value
     * @return Boolean
     */
    passes(value)
    {
        return ! /[^a-zA-Z\s]/ig.test(value);
    }

    /**
     * Get error message for the alphabet rule.
     *
     * @return String
     */
    message()
    {
        return "Only alphabets are accepted.";
    }
}

export default AlphabetRule;
