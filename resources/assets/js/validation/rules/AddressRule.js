import Rule from 'foundation/Rule';

/**
 * A custom alphabet validation rule.
 *
 * Defines rule name, message or message format and a passes
 * method which verifies the input value and returns a boolean.
 */
class AddressRule extends Rule
{
    /**
     * Rule name for custom validation method.
     *
     * @var String
     */
    name = 'address';

    /**
     * Get validity of the input value.
     *
     * @param  String  value
     * @return Boolean
     */
    passes(value)
    {
        return ! /[^A-Za-z0-9\/\,\-\s]/ig.test(value);
    }

    /**
     * Get error message for the alphabet rule.
     *
     * @return String
     */
    message()
    {
        return "Only alphabets, numerics, slash (/), comma (,) and hyphen (-) is allowed.";
    }
}

export default AddressRule;
