/**
 * Vendor class for controlling the password tab.
 *
 * Initializes essentials like form validations for company page.
 */
class SelectCity
{
    /**
     * Initialize company page components.
     *
     * @return void
     */
    constructor()
    {
        let cityNode = $("#City");
        cityNode.select2().on("change", () => {
            if (cityNode.val().length > 0) {
                $("#SelectionOverlay").removeClass("hidden");
                let splittedUrl = window.location.href.split("/");
                splittedUrl.push(cityNode.val());
                window.location = splittedUrl.join("/");
            }
        });
    }
}

$(document).ready(() => new SelectCity());
