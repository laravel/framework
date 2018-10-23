/**
 * An abstract form request class.
 *
 * Adds common properties and methods required for form validations.
 * Developers can override these default properties and methods for
 * any special cases by extending this class.
 */
class ReplyRequest
{
    /**
     * CSS class to create error labels.
     *
     * @var String
     */
    errorClass = 'help-block text-danger';

    /**
     * Element type to create error messages.
     *
     * @var String
     */
    errorElement = 'span';

     ignore = [];
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
        if(element.id === "Attachments"){
            return $(element).closest('.row').find('button.btn-attachmentupload').addClass('text-danger');
        }else{
            return $(element).closest('.form-group').addClass("has-error");
        }
    }

    /**
     * Handler to revert changes made by highlight.
     *
     * @param  Element  element
     * @return jQuery
     */
    unhighlight(element)
    {
        if(element.id === "Attachments"){
            return $(element).closest('.row').find('button.btn-attachmentupload').removeClass('text-danger');
        }else{
            return $(element).closest('.form-group').removeClass("has-error");
        }
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
        if($(element).attr("id") === "Attachments"){
            $(element).closest('.row').find('i.fa-paperclip').removeClass("text-blue");
            return $(element).closest('.row').find('button.btn-attachmentupload').attr('data-original-title', error[0].innerText).trigger("mouseover");
        }else{
            return error.appendTo($(element).parent());
        }
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

export default ReplyRequest;
