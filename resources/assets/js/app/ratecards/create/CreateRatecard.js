import Application from "foundation/Application";
import CreateRatecardRequest from "./CreateRatecardRequest";
import { applyKeyboardNavigationPatch } from "support/Select2Patches";

/**
 * Vendor class for controlling the password tab.
 *
 * Initializes essentials like form validations for password page.
 */
class CreateRatecard extends Application
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
        let request = new CreateRatecardRequest();
        request.init(this.notifier, this.translator, this.formRequisites);
        this.formValidator = $(request.formSelector).validate(request.getOptions());

        // Apply validation rules and messages.
        this.applyValidationRulesAndMessages();

        // Initialize date pickers.
        this.initializeDatePicker();

        // On form reset, clear form customized fields
        this.clearCustomInputsOnFormReset(request.formSelector);
        this.remoteValidation();
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
     * Apply validation for current rate card.
     *
     * @return void
     */
    remoteValidation()
    {
        $("tbody input.form-control").each(function () {
            let node = $(this);
            if(node.attr('data-msg-name') === 'Start Date'){    
                node.removeClass('error').parent().remove(".error");
                let Url = $("#CreateRatecardForm").attr('action')+'/validate/futureratecard',
                 ItemId = node.attr('name');
                 node.rules("add", {
                    remote: {
                        url: Url,
                        type: "post",
                        data: {
                          itemId:ItemId.replace("StartDate", "")
                        }
                    },messages: {
                        remote: "No current Ratecard available cannot add future Ratecard.",
                    },
                });
            }
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
            $("#Unit").trigger("change");
        });
    }
}

$(document).ready(() => (new CreateRatecard()).execute());
