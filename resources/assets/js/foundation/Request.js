/**
 * An abstract form request class.
 *
 * Adds common properties and methods required for form validations.
 * Developers can override these default properties and methods for
 * any special cases by extending this class.
 */
class Request
{
    /**
     * CSS class to create error labels.
     *
     * @var String
     */
    errorClass = 'help-block';

    /**
     * Element type to create error messages.
     *
     * @var String
     */
    errorElement = 'span';

    /**
     * Validate elements on keyup.
     *
     * @param  Element  element
     * @return Boolean
     */
    onkeyup(element)
    {
        return this.invalid.hasOwnProperty(element.name) ? $(element).valid() : false;
    }

    /**
     * Handler to highlight invalid fields.
     *
     * @param  Element  element
     * @return jQuery
     */
    highlight(element)
    {
        let node = $(element);
        node.siblings("span.help-block").show();
        return node.parent().addClass('has-error');
    }

    /**
     * Handler to revert changes made by highlight.
     *
     * @param  Element  element
     * @return jQuery
     */
    unhighlight(element)
    {
        let node = $(element);
        node.siblings("span.help-block").hide();
        return node.parent().removeClass('has-error');
    }

    /**
     * Customize placement of created error labels.
     *
     * @param  jQuery  error
     * @param  jQuery  element
     * @return jQuery
     */
    errorPlacement(error, element)
    {
        return element.siblings("span.help-block").length === 0 ? error.appendTo(element.parent()) : error;
    }

    /**
     * Get options for initializing form validation.
     *
     * @return Object
     */
    getOptions()
    {
        let that = this,
            options = {
                errorClass: this.errorClass,
                errorElement: this.errorElement,
                onkeyup: this.onkeyup,
                highlight: this.highlight,
                unhighlight: this.unhighlight,
                errorPlacement: this.errorPlacement,
                rules: this.rules(),
                messages: this.messages(),
                submitHandler: function (form, event) {
                    that.handler(form, event, that);
                }
            };
        if (typeof this.ignore !== 'undefined') {
            options['ignore'] = this.ignore;
        }

        return options;
    }
}

export default Request;
