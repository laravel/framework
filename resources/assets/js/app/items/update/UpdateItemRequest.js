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
            that.notifier.notifiyOverlaySuccessWithClearance(that.formSelector + "Overlay", response.message.body);
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
                that.notifier.populateFormErrors(that.formSelector, response.errors);
            } else {
                that.notifier.notify(that.formSelector + "NotificationArea", {
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
}

export default UpdateItemRequest;
