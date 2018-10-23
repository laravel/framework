import Application from "foundation/Application";
import CreateItemRequest from "./CreateItemRequest";
import { applyKeyboardNavigationPatch } from "support/Select2Patches";

/**
 * Vendor class for controlling the password tab.
 *
 * Initializes essentials like form validations for password page.
 */
class CreateItem extends Application
{
    formValidator = undefined;
    /**
     * Initialize item page components.
     *
     * @return void
     */
    execute()
    {
        // Initialize form validations
        let request = new CreateItemRequest();
        request.init(this.notifier, this.translator, this.formRequisites);
        this.formValidator = $(request.formSelector).validate(request.getOptions());

        // Initialize select2 for select elements
        this.initializeUnitSelect2(request.formSelector);

        // On form reset, clear form customized fields
        this.clearCustomInputsOnFormReset(request.formSelector);
    }

    /**
     * Initialize select2 for unit.
     *
     * @param  String  formSelector
     * @return void
     */
    initializeUnitSelect2(formSelector)
    {
        let unitNode = $("#Unit");
        unitNode.select2().on("select2:select", () => {
            return unitNode.val().length !== 0 && unitNode.valid();
        });

        applyKeyboardNavigationPatch(unitNode);
    }

    /**
     * Clear customized fields on form reset.
     *
     * @return void
     */
    clearCustomInputsOnFormReset(formSelector)
    {
        $(formSelector).on("reset", () => {
            this.formValidator.resetForm();
            setTimeout(() => {
                $("#Unit").trigger("change");
            }, 0);
        });
    }
}

$(document).ready(() => (new CreateItem()).execute());
