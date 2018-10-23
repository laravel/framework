/**
 * SendQuickEstimate class for sending estimate data to backend.
 *
 * Handles ajax data submission to the server and its response.
 */
class SendQuickEstimate
{
    /**
     * Show or hide overlay.
     *
     * @var Boolean
     */
    dontHideOverlay = false;

    /**
     * Get notifier for the SendQuickEstimate.
     *
     * @var /foundation/Notifier|undefined
     */
    notifier = undefined;

    /**
     * Get translator for the SendQuickEstimate.
     *
     * @var /foundation/Translator|undefined
     */
    translator = undefined;

    /**
     * Get form requisites for the SendQuickEstimate.
     *
     * @var /foundation/FormRequisites|undefined
     */
    formRequisites = undefined;

    /**
     * Vue instance of the application.
     *
     * @var /Vue|undefined
     */
    application = undefined;

    /**
     * Create a new instance of the SendQuickEstimate.
     *
     * @param  /foundation/Notifier  notifier
     * @param  /foundation/Translator  translator
     * @param  /foundation/FormRequisites  formRequisites
     * @param  /Vue  application
     * @return void
     */
    constructor(notifier, translator, formRequisites, application)
    {
        this.notifier = notifier;
        this.translator = translator;
        this.formRequisites = formRequisites;
        this.application = application;
    }

    /**
     * Execute sending of data to backend.
     *
     * @param  Element  form
     * @param  FormData  formData
     * @return void
     */
    execute(form, formData)
    {
        $.ajax({
            url: form.action,
            type: form.method,
            dataType: "json",
            data: formData,
            processData: false,
            contentType: false
        })
        .done((response) => {
            this.formRequisites.hideOverlay(`#${form.id}Overlay`);
            $("#success-notify").removeClass('hidden');
            $("#CopyQuickEstimateForm").addClass('hidden');
//            this.notifier.notify(`#${form.id}NotificationArea`, {
//                status: "success",
//                message: response.message
//            });
        })
        .fail((jqXHR) => {
            if (jqXHR.status === 422) {
                let response = this.formRequisites.parseJson(`#${form.id}NotificationArea`, jqXHR.responseText);
                if (! response) {
                    return false;
                }
                this.notifier.populateFormErrors(this.form.id, response.errors);
            } else {
                this.notifier.notify(`#${form.id}NotificationArea`, {
                    status: "error",
                    message: this.translator.trans("system.failure")
                });
            }
        })
        .always(() => {
            if (! this.dontHideOverlay) {
                this.formRequisites.hideOverlay(`#${form.id}Overlay`);
            }
            this.dontHideOverlay = false;
            // Scroll window back to submit button position to view backend result.
            $("#CopyQuickEstimateTable").trigger("qe.items.not-selected");
        });
    }
}

export default SendQuickEstimate;
