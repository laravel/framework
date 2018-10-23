/**
 * Custom InputRadio component.
 *
 * Makes a custom file chooser for the given input file element.
 */
class InputRadio
{
    /**
     * Raw HTML template for filechooser.
     *
     * @var String template
     */
    template = `
        <label for="{selector}"></label>
        <label for="{selector}" class="input-radio-description padding-right-15">{placeholder}</label>
    `;

    /**
     * Make a custom input radio for the given selector.
     *
     * @param  Selector  selector
     * @param  String  placeholder
     * @return void
     */
    make(selector)
    {
        $(selector).each((index, element) => {
            let jQuery = $(element);
            this.hideRadioElement(jQuery);
            jQuery.after(this.replacePlaceholders(jQuery));
        });
    }

    /**
     * Hide the actual HTML input radio element.
     *
     * @param  jQuery  element
     * @return void
     */
    hideRadioElement(element)
    {
        element.css({
            "width": '1px',
            "height": '0px',
            "opacity": 0,
            "overflow": 'hidden',
            "position": 'absolute',
            "z-index": -1,
        });
    }

    /**
     * Replace the placeholders in raw template.
     *
     * @param  jQuery  element
     * @return String
     */
    replacePlaceholders(element)
    {
        return this.template.replace(/\{selector\}/g, element.attr('id'))
                            .replace(/\{placeholder\}/g, element.attr('placeholder'));
    }
}

export default InputRadio;
