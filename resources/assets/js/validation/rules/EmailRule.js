import { isEmail } from 'validation/helpers';
import ExplicitRule from 'foundation/ExplicitRule';

/**
 * Override default email validation rule.
 *
 * Defines rule name, message or message format and a passes
 * method which verifies the input value and returns a boolean.
 */
class EmailRule extends ExplicitRule
{
    /**
     * Rule name for custom validation method.
     *
     * @var String
     */
    name = 'email';

    /**
     * Get validity of the input value.
     *
     * @param  String  value
     * @return Boolean
     */
    passes(value, element, param, validator)
    {
        return validator.optional(element) || isEmail(value);
    }
}

export default EmailRule;
