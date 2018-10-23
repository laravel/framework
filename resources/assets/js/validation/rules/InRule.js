import Rule from 'foundation/Rule';

/**
 * A custom in validation rule.
 *
 * Defines rule name, message or message format and a passes
 * method which verifies the input value and returns a boolean.
 */
class InRule extends Rule
{
    /**
     * Rule name for custom validation method.
     *
     * @var String
     */
    name = 'in';

    /**
     * Get validity of the input value.
     *
     * @param  String  value
     * @param  Element  element
     * @param  Array  param
     * @param  Validator  validator
     * @return Boolean
     */
    passes(value, element, param, validator)
    {
        let isOptional = validator.optional(element);
        if (isOptional) {

            return isOptional;
        }

        return ! (param.indexOf(value) === -1);
    }

    /**
     * Get error message for the in rule.
     *
     * @return String
     */
    message()
    {
        return "Select one of these options.";
    }
}

export default InRule;
