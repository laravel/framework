/**
 * Generate a "psuedo-random" string of given length.
 *
 * @param  Integer  length
 * @return String
 */
export function generateRandomId(length = 40)
{
    // Calculate half length and create an array of 8-bit unsigned integers.
    let halfLength = length / 2,
        randomValues = new Uint8Array(halfLength);
    // Get cryptographically strong random values into given empty array.
    window.crypto.getRandomValues(randomValues);
    // Create a new, shallow-copied "Array" instance and join with an empty string.
    let randomString = Array.from(randomValues, (value) => {
        // Get a hex string representing the specified number of given radix and
        // get last 2 characters of the hex representation of the random number.
        return (`0${value.toString(16)}`).substr(-2);
    }).join("");

    // Make a pseudo-ramdom string by combining unix timestamp in milliseconds and random string.
    return `${randomString.slice(0, halfLength)}${Date.now()}${randomString.slice(halfLength, length)}`;
}
