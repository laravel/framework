import Rule from 'foundation/Rule';

/**
 * A custom image validation rule.
 *
 * Defines rule name, message or message format and a passes
 * method which verifies the input value and returns a boolean.
 */
class ImageRule extends Rule
{
    /**
     * Rule name for custom validation method.
     *
     * @var String
     */
    name = 'image';

    /**
     * Get validity of the input value.
     *
     * @param  FileList  files
     * @param  Element  element
     * @param  Boolean  param
     * @param  Validator  validator
     * @return Boolean
     */
    passes(files, element, param, validator)
    {
        // Split mime on commas in case we have multiple types we can accept
        let typeParam = "image/*", regex,
            isOptional = validator.optional(element);
        if (isOptional) {

            return isOptional;
        }
        // Escape string to be used in the regex
        typeParam = typeParam.replace(/[\-\[\]\/\{\}\(\)\+\?\.\\\^\$\|]/g, "\\$&")
                             .replace(/,/g, "|")
                             .replace(/\/\*/g, "/.*");
        regex = new RegExp(".?(" + typeParam + ")$", "i");
        for (let file of files) {

            return regex.test(file.type);
        }
    }

    /**
     * Get error message for the image rule.
     *
     * @return String
     */
    message()
    {
        return "Only image files are accepted.";
    }
}

export default ImageRule;
