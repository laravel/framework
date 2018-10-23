import Rule from 'foundation/Rule';

/**
 * A custom mimetypes validation rule.
 *
 * Defines rule name, message or message format and a passes
 * method which verifies the input value and returns a boolean.
 */
class MimeTypesRule extends Rule
{
    /**
     * Rule name for custom validation method.
     *
     * @var String
     */
    name = 'mimetypes';

    /**
     * Validate elements on keyup.
     *
     * @param  FileList  files
     * @param  Element  element
     * @param  String  mimeTypes
     * @return Boolean
     */
    passes(files, element, mimeTypes)
    {
        // Split mime on commas in case we have multiple types we can accept
        let mimeTypes = mimeTypes.replace(/\s/g, ""), regex;
        // Escape string to be used in the regex
        mimeTypes = mimeTypes.replace(/[\-\[\]\/\{\}\(\)\+\?\.\\\^\$\|]/g, "\\$&")
                             .replace(/,/g, "|")
                             .replace(/\/\*/g, "/.*");
        regex = new RegExp(".?(" + mimeTypes + ")$", "i");
        for (let file of files) {

            return regex.test(file.type);
        }
    }

    /**
     * Get error message format for the mimetypes rule.
     *
     * @return Function
     */
    format()
    {
        return $.validator.format("Only files with mimetypes {0} are accepted.");
    }
}

export default MimeTypesRule;
