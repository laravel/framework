import Request from 'foundation/Request';
import CreateIdeaRequestRules from './CreateIdeaRequestRules';
import CreateIdeaRequestMessages from './CreateIdeaRequestMessages';

/**
 * CreateIdeaRequest class for validating create idea form.
 *
 * Defines rules and messages for the CreateIdeaForm validation.
 * Handles ajax submission after successful form validation.
 */
class CreateIdeaRequest extends Request
{
    /**
     * Form selector to perform validation.
     *
     * @var Selector
     */
    formSelector = '#CreateIdeaForm';

    /**
     * Show or hide overlay.
     *
     * @var Boolean
     */
    dontHideOverlay = false;

    /**
     * Get notifier for the CreateIdeaRequest.
     *
     * @var foundation/Notifier|undefined
     */
    notifier = undefined;

    /**
     * Get translator for the CreateIdeaRequest.
     *
     * @var foundation/Translator|undefined
     */
    translator = undefined;

    /**
     * Get form requisites for the CreateIdeaRequest.
     *
     * @var foundation/FormRequisites|undefined
     */
    formRequisites = undefined;

    /**
     * Get CreateIdea instance for the CreateIdeaRequest.
     *
     * @var app/ideas/create/customer/CreateIdea|undefined
     */
    createIdea = undefined;

    /**
     * Initialize essentials for the CreateIdeaRequest.
     *
     * @param  foundation/Notifier  notifier
     * @param  foundation/Translator  translator
     * @param  foundation/FormRequisites  formRequisites
     * @param  app/ideas/create/customer/CreateIdea  createIdea
     * @return void
     */
    init(notifier, translator, formRequisites, createIdea)
    {
        this.notifier = notifier;
        this.translator = translator;
        this.formRequisites = formRequisites;
        this.createIdea = createIdea;
    }

    /**
     * Get validation rules for the application.
     *
     * @return Object
     */
    rules()
    {
        return (new CreateIdeaRequestRules).get();
    }

    /**
     * Get validation messages for the application.
     *
     * @return Object
     */
    messages()
    {
        return (new CreateIdeaRequestMessages).get(this.translator);
    }

    /**
     * Callback for handling the actual submit when the form is valid.
     *
     * @param  Element  form
     * @param  Event  event
     * @param  CreateIdeaRequest  that
     * @return void
     */
    handler(form, event, that)
    {
        that.createIdea.initializePageOverlay("Creating idea...");
        $.ajax({
            url: form.action,
            type: form.method,
            dataType: 'json',
            data: new FormData(form),
            processData: false,
            contentType: false
        })
        .done((response) => {
            $("#DesignItem").trigger('select2:select');
            that.createIdea.notifySuccessOverlay(response.message.body);
            $("#Attachments, #Idea").val("");
        })
        .fail((jqXHR) => {
            if (jqXHR.status === 422) {
                let response = that.formRequisites.parseJson(that.formSelector + "NotificationArea", jqXHR.responseText);
                if (! response) {
                    return false;
                }
                that.notifier.populateFormErrors(that.formSelector, response.errors);
            } else {
                that.createIdea.notifyErrorOverlay()
            }
        })
        .always(()=>{
            that.createIdea.clearPageOverlay();
        });
    }
}

export default CreateIdeaRequest;
