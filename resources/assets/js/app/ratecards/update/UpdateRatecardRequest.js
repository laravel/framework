import Request from "foundation/Request";

/**
 * CreateVendorRequest class for validating create vendor form.
 *
 * Defines rules and messages for the CreateVendorForm validation.
 * Adds password strength validation method and handles ajax
 * form submission.
 */
class UpdateRatecardRequest extends Request
{
    /**
     * Form selector to perform validation.
     *
     * @var Selector
     */
    formSelector = '#UpdateRatecardForm';

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
    }

    /**
     * Get validation rules for the application.
     *
     * @return Object
     */
    rules()
    {
        return {};
    }

    /**
     * Get validation messages for the application.
     *
     * @return Object
     */
    messages()
    {
        return {};
    }

    /**
     * Callback for handling the actual submit when the form is valid.
     *
     * @param  Element  form
     * @param  Event  event
     * @param  UpdateRatecardRequest  that
     * @return void
     */
    handler(form, event, that)
    {
        event.preventDefault();
        that.formRequisites.prepareFormForAjax(that.formSelector + "Overlay", "Updating Ratecard");
        $.ajax({
            url: form.action,
            type: form.method,
            dataType: 'json',
            data: that.getFormData(that.formSelector),
            processData: false,
            contentType: false
        })
        .done((response) => {
            that.dontHideOverlay = true;
            that.notifier.notifiyOverlaySuccessWithClearance(that.formSelector + "Overlay", "Ratecard updated");
            that.notifier.notify(that.formSelector + "NotificationArea", {
                status: "success",
                message: response.message
            });
            $(that.formSelector).trigger('reset');
        })
        .fail((jqXHR) => {
            if (jqXHR.status === 422) {
                let response = that.formRequisites.parseJson(that.formSelector + "NotificationArea", jqXHR.responseText);
                if (! response) {
                    return false;
                }
                that.notifier.populateFormErrors(that.formSelector, that.normalizeErrors(response.errors));
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

    /**
     * Prepare form data for ajax submission.
     *
     * @param  Selector  formSelector
     * @return String
     */
    getFormData(formSelector)
    {
        let inputData = {};
        $(`${formSelector} tbody input.form-control`).each(function () {
            let node = $(this),
                elementId = node.attr("id"),
                matchIndex = elementId.match(/[0-9]+/).index,
                packageId = elementId.slice(matchIndex),
                indexName = elementId.slice(0, matchIndex);

            if (inputData[packageId] === undefined) {
                inputData[packageId] = {};
            }
            inputData[packageId][indexName] = node.val();
        });

        let formData = new FormData();
        formData.append("_method", $(`${formSelector} input[name='_method']`).val());
        formData.append("input", JSON.stringify(inputData));

        return formData;
    }

    /**
     * Normalize response errors for form validation.
     *
     * @param  JSONObject  errors
     * @return JSONObject
     */
    normalizeErrors(errors)
    {
        let normalizedErrors = {};
        for (let index in errors) {
            let splittedIndex = index.split(".");
            normalizedErrors[`${splittedIndex[1]}${splittedIndex[0]}`] = errors[index];
        }

        return normalizedErrors;
    }
}

export default UpdateRatecardRequest;
