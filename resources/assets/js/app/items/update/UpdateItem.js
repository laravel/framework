import Application from "foundation/Application";
import UpdateItemRequest from "./UpdateItemRequest";
import { applyKeyboardNavigationPatch } from "support/Select2Patches";

/**
 * Vendor class for controlling the password tab.
 *
 * Initializes essentials like form validations for password page.
 */
class UpdateItem extends Application
{
    /**
     * Initialize client update page components.
     *
     * @return void
     */
    execute()
    {
        // Initialize form validations
        let request = new UpdateItemRequest();
        request.init(this.notifier, this.translator, this.formRequisites);
        $(request.formSelector).validate(request.getOptions());

        // On form reset, reset custom fields to initial state.
        this.resetCustomInputsOnFormReset(request.formSelector);
    }

    /**
     * Reset customized fields on form reset.
     *
     * @param  Selector  formSelector
     * @return void
     */
    resetCustomInputsOnFormReset(formSelector)
    {
        $(formSelector).on("reset", () => {
            setTimeout(() => {
                $("#Code").trigger("change");
            }, 0);
        });
    }
}

$(document).ready(() => (new UpdateItem()).execute());
