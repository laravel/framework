/**
 * Translates generic messages to real messages.
 *
 * Loads languages files and translates given generic
 * message key to real message.
 *
 * validation keys regex - (?<=\.trans\([\'|\"]).*(?=[\'|\"]\,)
 */
class Translator
{
    /**
     * Get translations for the translator.
     *
     * @var Object
     */
    translations = {
        system: require("lang/en/system.json"),
        validation: require("lang/en/validation.json")
    };

    /**
     * Translates generic message to a real message.
     *
     * @param  String  id
     * @param  Array  options
     * @return String
     */
    trans(id, options = [])
    {
        let message = this.translations,
            keys = id.split(".");

        for (let key of keys) {
            message = message[key];
        }

        for (let option of options) {
            let optionRegex = new RegExp(":" + option.replace, "g"),
                modifiedMessage = message.replace(optionRegex, option.with);
            message = modifiedMessage;
        }

        return message;
    }
}

export default Translator;
