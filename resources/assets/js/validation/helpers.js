/**
 * Check given email is valid or not
 *
 * @param  String  email
 * @return Boolean
 */
export function isEmail(email)
{
    return /\s/g.test(email) ? false : /[a-zA-Z0-9]+@[a-zA-Z0-9]+\.[a-zA-Z0-9]{2}/g.test(email);
}
