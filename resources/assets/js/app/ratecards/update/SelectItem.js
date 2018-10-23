import { applyKeyboardNavigationPatch } from "support/Select2Patches";

/**
 * Vendor class for controlling the password tab.
 *
 * Initializes essentials like form validations for password page.
 */
class SelectItem
{
    /**
     * jQuery instance of item node.
     *
     * @var jQuery
     */
    itemNode = $("#Item");

    /**
     * jQuery instance of city node.
     *
     * @var jQuery
     */
    cityNode = $("#City");

    /**
     * Create a new instance of select item.
     *
     * @return void
     */
    constructor()
    {
        this.initializeSelect2([this.itemNode, this.cityNode]);
        this.itemNode.trigger("focus");
    }

    /**
     * Initialize select2 on the given nodes.
     *
     * @param  Array  nodes
     * @return void
     */
    initializeSelect2(nodes)
    {
        for (let node of nodes) {
            node.select2().on("change", this.redirect.bind(this));
            applyKeyboardNavigationPatch(node);
        }
    }

    /**
     * Redirect handler for item and city nodes.
     *
     * @return void
     */
    redirect()
    {
        if (! this.areElementsEmpty()) {
            $("#SelectionOverlay").removeClass("hidden");
            window.location = this.getRedirectUrl();
        }
    }

    /**
     * Check whether page elements are empty.
     *
     * @return Boolean
     */
    areElementsEmpty()
    {
        return this.itemNode.val().length === 0 || this.cityNode.val().length === 0;
    }

    /**
     * Get redirection url.
     *
     * @return String
     */
    getRedirectUrl()
    {
        let url = window.location.href.replace("select", this.itemNode.val()),
            splittedUrl = url.split("/"),
            lastIndexedElement = splittedUrl[splittedUrl.length - 1];

        // Remove last element from the array, if it is empty.
        if (lastIndexedElement.length === 0) {
            splittedUrl.splice(-1, 1);
        }

        // Push city value into the array to form the url.
        splittedUrl.push(this.cityNode.val());

        return splittedUrl.join("/");
    }
}

$(document).ready(() => new SelectItem());
