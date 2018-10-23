import { applyKeyboardNavigationPatch } from 'support/Select2Patches';

/**
 * Vendor class for controlling the password tab.
 *
 * Initializes essentials like form validations for password page.
 */
class SelectItem
{
    /**
     * Create a new instance of SelectClient.
     *
     * @return void
     */
    constructor()
    {
        let itemNode = $("#Item");
        itemNode.select2().on('select2:select', () => {
            $("#SelectionOverlay").removeClass('hidden');
            let url = window.location.href.replace('select', itemNode.val());
            window.location = url.substr(0, url.indexOf("edit") + 4);
        });
        applyKeyboardNavigationPatch(itemNode);
        itemNode.trigger('focus');
    }
}

$(document).ready(() => new SelectItem());
