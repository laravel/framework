/**
 * Store form state and restore it.
 *
 * Store form elements with keys, restores them into 
 * particular position and checks elements existence.
 */
class FormState
{
    /**
     * Form selector.
     *
     * @var Selector|undefined
     */
    formSelector = undefined;

    /**
     * Form elements state repository.
     *
     * @var Object
     */
    repository = {};

    /**
     * Create a new instance of FormState.
     *
     * @param  Selector  selector
     * @return void
     */
    constructor(selector)
    {
        this.formSelector = selector;
    }

    /**
     * Stores given cloned element into repository.
     *
     * @param  Selector  selector
     * @return void
     */
    store(selector)
    {
        // Store select, if given selector node is a "select" element.
        let node = $(selector);
        return node.is("select") ? this.storeSelect(node) : false;
    }

    /**
     * Store "select" element into the repository.
     *
     * @param  jQuery  node
     * @return void
     */
    storeSelect(node)
    {
        let optionsCollection = node.children("option"),
            optionsToStore = [];

        // Prepare select options to store.
        for (let option of optionsCollection) {
            optionsToStore.push({
                "text": option.text,
                "value": option.value,
            }); 
        }

        // Store options and value into the repository.
        this.repository[`#${node.attr("id")}`] = {
            "options": optionsToStore,
            "value": node.val(),
        };
    }

    /**
     * Restores element with given key from repository.
     *
     * @param  String  key
     * @return void
     */
    restore(key = "")
    {
        if (key.length > 0 && $(key).is("select")) {
            return this.restoreSelect($(key), this.repository[key].options, this.repository[key].value);
        }

        for (let key in this.repository) {
            let node = $(key);
            if (node.is("select")) {
                this.restoreSelect(node, this.repository[key].options, this.repository[key].value);
            }
        }
    }

    /**
     * Build select element to its original state.
     *
     * @param  jQuery  node
     * @param  Array  options
     * @param  String  value
     * @return void
     */
    restoreSelect(node, options, value)
    {
        node.html("");
        for (let option of options) {
            let optionElement = new Option(option.text, option.value);
            node.append($(optionElement));
        }
        node.val(value).trigger("change");
    }

    /**
     * Get list of selectors of saved elements.
     *
     * @return Array
     */
    selectors()
    {
        return Object.keys(this.repository);
    }

    /**
     * Clear all element states in the repository.
     *
     * @return Array
     */
    clear()
    {
        this.repository = {};
    }

    /**
     * Update the latest form fields state.
     *
     * @return void
     */
    override()
    {
        let elements = $(this.formSelector).get(0).elements;

        for (let element of elements) {
            let node = $(element);

            // Override select value, if it is overridable.
            if (this.isOverridable(node) && node.is("select")) {
                this.updateSelectValue(node);
                continue;
            }

            // Override input text value, if it is overridable.
            if (this.isOverridable(node) && node.is("input[type=text]")) {
                this.updateTextValue(node);
                continue;
            }

            // Override input radio state, if it is overridable.
            if (this.isOverridable(node) && node.is("input[type=radio]")) {
                this.updateRadioState(node.attr("name"));
                continue;
            }
        }
    }

    /**
     * Check whether given node is overridable.
     *
     * @param  jQuery  node
     * @return Boolean
     */
    isOverridable(node)
    {
        return $.type(node.attr("id")) === "string" && node.is(":visible");
    }

    /**
     * Update given select node default values.
     *
     * @param  jQuery  node
     * @return void
     */
    updateSelectValue(node)
    {
        let value = node.val();
        node.children('option[selected]').removeAttr('selected');
        node.children(`option[value=${value}]`).attr('selected', 'selected');
    }

    /**
     * Update given text node default values.
     *
     * @param  jQuery  node
     * @return void
     */
    updateTextValue(node)
    {
        node.attr('value', node.val());
    }

    /**
     * Update given radio node default values.
     *
     * @param  String  name
     * @return void
     */
    updateRadioState(name)
    {
        let nodes = $(`input[name=${name}]`),
            currentNode = $(`input[name=${name}]:checked`);
        nodes.removeAttr('checked')
        currentNode.attr('checked', 'checked');
    }
}

export default FormState;
