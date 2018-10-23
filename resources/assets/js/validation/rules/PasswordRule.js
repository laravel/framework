import Rule from 'foundation/Rule';

/**
 * A custom password validation rule.
 *
 * Defines rule name, message or message format and a passes
 * method which verifies the input value and returns a boolean.
 */
class PasswordRule extends Rule
{
    /**
     * Rule name for custom validation method.
     *
     * @var String
     */
    name = 'password';

    /**
     * Get validity of the input value.
     *
     * @param  String  value
     * @return Boolean
     */
    passes(value)
    {
        return /(?=.*[a-z])(?=.*\d)(?=.*[-+_!@#$%^&*.,?])/ig.test(value);
    }

    /**
     * Get error message for the password rule.
     *
     * @return String
     */
    message()
    {
        return "Password should contain atleast a letter, a number and a special character.";
    }
}

export default PasswordRule;
