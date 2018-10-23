/**
 * Custom FileChooser component.
 *
 * Makes a custom file chooser for the given input file element.
 */
class FileChooser
{
    /**
     * Raw HTML template for filechooser.
     *
     * @var String template
     */
    template = `
        <div class="input-group">
            <label class="input-group-addon" for="{selector}">
                <i class="fa fa-picture-o"></i>
            </label>
            <label class="form-control" for="{selector}">
                <span class="text-normal text-placeholder" id="{selector}Alias">{placeholder}</span>
            </label>
        </div>
    `;

    /**
     * Make a custom filechooser for the given selector.
     *
     * @param  Selector  selector
     * @param  String  placeholder
     * @return void
     */
    make(selector, placeholder = "gravatar.png")
    {
        this.hideFileElement(selector);
        this.fixFirefoxBug(selector);
        $(selector).after(this.replacePlaceholders(selector, placeholder));
        this.applyFileChooserBehaviour(selector, placeholder);
    }

    /**
     * Fix firefox bug for input[type=file]:focus.
     *
     * @param  Selector  selector
     * @return void
     */
    fixFirefoxBug(selector)
    {
        // To check what events are registered on an element,
        // use $._data( $("#ProfilePicture")[0], "events" );
        // We get only events which are registered only by the "jquery"
        if (navigator.userAgent.indexOf("Firefox") !== -1) {
            $(selector).on({
                focus: function (event) {
                    $(event.currentTarget).addClass('has-focus');
                },
                blur: function (event) {
                    $(event.currentTarget).removeClass('has-focus');
                }
            });
        }
    }

    /**
     * Replace the placeholders in raw template.
     *
     * @param  Selector  selector
     * @param  String  placeholder
     * @return String
     */
    replacePlaceholders(selector, placeholder)
    {
        return this.template.replace(/\{selector\}/g, selector.replace('#', ''))
                            .replace(/\{placeholder\}/g, placeholder);
    }

    /**
     * Hide the actual HTML file element.
     *
     * @param  Selector  selector
     * @return void
     */
    hideFileElement(selector)
    {
        $(selector).css({
            "width": '0.1px',
            "height": '0.1px',
            "opacity": 0,
            "overflow": 'hidden',
            "position": 'absolute',
            "z-index": -1
        });
    }

    /**
     * Apply filechooser behaviour on the custom template.
     *
     * @param  Selector  selector
     * @param  String  placeholder
     * @return void
     */
    applyFileChooserBehaviour(selector, placeholder)
    {
        $(selector).on('change', function () {
            let fileChooserAliasNode = $(this).siblings('div.input-group').find(selector + 'Alias');
            if (this.files.length > 0) {
                let selectedFilename = this.files[0].name,
                    displayFilename = undefined;

                if (selectedFilename.length > 26) {
                    displayFilename = selectedFilename.slice(0, 18) + "..." + selectedFilename.slice(-8);
                } else {
                    displayFilename = selectedFilename;
                }
                fileChooserAliasNode.html(displayFilename).removeClass('text-placeholder');
            } else {
                fileChooserAliasNode.html(placeholder).addClass('text-placeholder');
            }
        });
    }
}

export default FileChooser;
