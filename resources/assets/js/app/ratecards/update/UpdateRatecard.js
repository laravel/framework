import Application from "foundation/Application";
import UpdateRatecardRequest from "./UpdateRatecardRequest";
import { applyKeyboardNavigationPatch } from "support/Select2Patches";

/**
 * Vendor class for controlling the password tab.
 *
 * Initializes essentials like form validations for password page.
 */
class UpdateRatecard extends Application
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
        let request = new UpdateRatecardRequest();
        request.init(this.notifier, this.translator, this.formRequisites);
        this.formValidator = $(request.formSelector).validate(request.getOptions());

        // Apply validation rules and messages.
        this.applyValidationRulesAndMessages();

        // Initialize future ratecards modal.
        this.intializeFutureRatecardsModal();

        // Initialize date pickers.
        this.initializeDatePicker();
        
        // On form reset, clear form customized fields
        this.clearCustomInputsOnFormReset(request.formSelector);
    }

    /**
     * Apply validation rules and messages.
     *
     * @return void
     */
    applyValidationRulesAndMessages()
    {
        $("tbody input.form-control").each(function () {
            let node = $(this),
                requiredMessage = `${node.data("msgName")} can\u0027t be blank`;
            node.rules("add", {
                required: true,
                messages: {
                    required: requiredMessage,
                },
            });
        });
    }

    /**
     * Initialize modal for future ratecards.
     *
     * @return void
     */
    intializeFutureRatecardsModal()
    {
        $("#FutureRatecards").on("show.bs.modal", function (event) {
            $(this).find(`a[href='#${$(event.relatedTarget).data("pricePackageId")}']`).tab("show");
            $(this).find(".hidden").removeClass("hidden");
        });
    }

    /**
     * Initialize date pickers.
     *
     * @return void
     */
    initializeDatePicker()
    {
        $(".date-picker").datepicker({
            autoclose: true,
            clearBtn: true,
            format: "dd-M-yyyy",
            todayHighlight: true,
            startDate: "0d",
            endDate: "+1y",
        }).on('change', function(){
            $(this).valid();
        });
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
        });
    }
}

$(document).ready(() => (new UpdateRatecard()).execute());
