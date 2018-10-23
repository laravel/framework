/**
 * An abstract validation rule class.
 *
 * Adds custom validation rules, their methods and
 * default validation messages to the application scope.
 */
class Rule
{
    /**
     * Add a custom validation method.
     *
     * @return void
     */
    add()
    {
        let that = this;
        $.validator.addMethod(this.name, function (value, element, params) {
            if (element.type === "file") {

                return that.passes(element.files, element, params, this);
            } else {

                return that.passes(value, element, params, this);
            }
        }, this.messageMethod());
    }

    /**
     * Get message or format method for the validation rule.
     *
     * @return String|Function
     */
    messageMethod()
    {
        if (typeof this.format === 'undefined') {

            return this.message();
        }

        return this.format();
    }
}

export default Rule;
