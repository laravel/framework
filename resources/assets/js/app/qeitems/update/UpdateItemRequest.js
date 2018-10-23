import Request from "foundation/Request";
import ValidationRules from "./ValidationRules";
import ValidationMessages from "./ValidationMessages";
import SentenceRule from "validation/rules/SentenceRule";

/**
 * CreateVendorRequest class for validating create vendor form.
 *
 * Defines rules and messages for the CreateVendorForm validation.
 * Adds password strength validation method and handles ajax
 * form submission.
 */
class UpdateItemRequest extends Request
{
    /**
     * Form selector to perform validation.
     *
     * @var Selector
     */
    formSelector = '#UpdateItemForm';

    /**
     * Show or hide overlay.
     *
     * @var Boolean
     */
    dontHideOverlay = false;

    /**
     * Get notifier for the PasswordRequest.
     *
     * @var \Core\Notifier|undefined
     */
    notifier = undefined;

    /**
     * Get translator for the PasswordRequest.
     *
     * @var \Core\Translator|undefined
     */
    translator = undefined;

    /**
     * Get form requisites for the PasswordRequest.
     *
     * @var \Core\FormRequisites|undefined
     */
    formRequisites = undefined;

    /**
     * Initialize essentials for the PasswordRequest.
     *
     * @param  \Core\Notifier  notifier
     * @param  \Core\Translator  translator
     * @param  \Core\FormRequisites  formRequisites
     * @return void
     */
    init(notifier, translator, formRequisites)
    {
        this.notifier = notifier;
        this.translator = translator;
        this.formRequisites = formRequisites;
        this.addCustomValidationMethods();
    }

    /**
     * Add additional custom validation methods.
     *
     * @return void
     */
    addCustomValidationMethods()
    {
        (new SentenceRule()).add();
    }

    /**
     * Get validation rules for the application.
     *
     * @return Object
     */
    rules()
    {
        return (new ValidationRules()).get();
    }

    /**
     * Get validation messages for the application.
     *
     * @return Object
     */
    messages()
    {
        return (new ValidationMessages()).get(this.translator);
    }

    /**
     * Callback for handling the actual submit when the form is valid.
     *
     * @param  Element  form
     * @param  Event  event
     * @param  UpdateItemRequest  that
     * @return void
     */
    handler(form, event, that)
    {
        that.formRequisites.prepareFormForAjax(that.formSelector + "Overlay", 'Updating Item');
        let then = this;
        $.ajax({
            url: form.action,
            type: form.method,
            dataType: 'json',
            data: new FormData(form),
            processData: false,
            contentType: false
        })
        .done((response) => {
            that.dontHideOverlay = true;
            that.notifier.notifiyOverlaySuccessWithClearance(that.formSelector + "Overlay", "Item updated");
            that.notifier.notify(that.formSelector + "NotificationArea", {
                status: response.message.type,
                message: response.message
            });
        })
        .fail((jqXHR) => {
            if (jqXHR.status === 422) {
                let response = that.formRequisites.parseJson(that.formSelector + "NotificationArea", jqXHR.responseText);
                if (! response) {
                    return false;
                }
                then.populateFormErrors(that.formSelector, response.data.errors);
            } else {
                then.notify(that.formSelector + "NotificationArea", {
                    status: "error",
                    message: that.translator.trans('system.failure')
                });
            }
        })
        .always(() => {
            if (! that.dontHideOverlay) {
                that.formRequisites.hideOverlay(that.formSelector + "Overlay");
            }
            that.dontHideOverlay = false;
        });
    }
    
     /**
     * Populates errors for the given form.
     *
     * @param  Selector  selector
     * @param  Object  errors
     * @return void
     */
    populateFormErrors(selector, errors)
    {
        let validator = $(selector).data('validator');
        for (let elementName in errors) {
            let errorObject = {},
                previousValue = $("#" + elementName).data("previousValue") ? $("#" + elementName).data("previousValue") : {};
            previousValue.valid = false;
            previousValue.message = errors[elementName][0];
            $("#" + elementName).data("previousValue", previousValue);
            let Condition = elementName === "References";
            if (Condition) {
                errorObject[elementName + "[]"] = errors[elementName][0];
            } else {
                errorObject[elementName] = errors[elementName][0];
            }
            validator.showErrors(errorObject);
        }
    }
}

export default UpdateItemRequest;
