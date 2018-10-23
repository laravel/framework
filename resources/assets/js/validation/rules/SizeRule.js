import Rule from 'foundation/Rule';

/**
 * A custom size validation rule.
 *
 * Defines rule name, message or message format and a passes
 * method which verifies the input value and returns a boolean.
 */
class SizeRule extends Rule
{
    /**
     * Rule name for custom validation method.
     *
     * @var String
     */
    name = 'size';

    /**
     * Get validity of the input value.
     *
     * @param  FileList  files
     * @param  Element  element
     * @param  Integer  size
     * @param  Validator  validator
     * @return Boolean
     */
    passes(files, element, size, validator)
    {
        let isOptional = validator.optional(element);
        if (isOptional) {

            return isOptional;
        }
        if (element.multiple) {
            let totalSize = 0;
            for (let file of files) {
                totalSize += this.getFileSize(file);
            }

            return Math.round(totalSize) < size;
        }

        return Math.round(this.getFileSize(files[0])) < size;
    }

    /**
     * Get filesize converted to KB.
     *
     * @param  File  file
     * @return Interger
     */
    getFileSize(file)
    {
        return file.size/1024;
    }

    /**
     * Get error message format for the size rule.
     *
     * @return Function
     */
    format()
    {
        return $.validator.format("File size should not exceed {0} KB.");
    }
}

export default SizeRule;
