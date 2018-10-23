import Request from "../../replyformrequest/ReplyRequest";
import ReplyIdeaRequestRules from "./ReplyIdeaRequestRules";
import ReplyIdeaRequestMessages from "./ReplyIdeaRequestMessages";

/**
 * ReplyIdeaRequest class for validating reply idea form.
 *
 * Defines rules and messages for the ReplyIdeaForm validation.
 * Handles ajax submission after successful form validation.
 */
class ReplyIdeaRequest extends Request
{
    /**
     * Form selector to perform validation.
     *
     * @var Selector
     */
    formSelector = undefined;

    /**
     * Show or hide overlay.
     *
     * @var Boolean
     */
    dontHideOverlay = false;

    /**
     * Get notifier for the ReplyIdeaRequest.
     *
     * @var foundation/Notifier|undefined
     */
    notifier = undefined;

    /**
     * Get translator for the ReplyIdeaRequest.
     *
     * @var foundation/Translator|undefined
     */
    translator = undefined;

    /**
     * Get form requisites for the ReplyIdeaRequest.
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
     * For show Overlay and Notifications
     * 
     */
    formOverlayNotification = "#ReplyIdeaForm"

    /**
     * Initialize essentials for the ReplyIdeaRequest.
     *
     * @param  Selector  formSelector
     * @param  foundation/Notifier  notifier
     * @param  foundation/Translator  translator
     * @param  foundation/FormRequisites  formRequisites
     * @param  app/ideas/create/manager/CreateIdea  createIdea
     * @return void
     */
    init(formSelector, notifier, translator, formRequisites, createIdea)
    {
        this.formSelector = formSelector;
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
        return (new ReplyIdeaRequestRules).get();
    }

    /**
     * Get validation messages for the application.
     *
     * @return Object
     */
    messages()
    {
        return (new ReplyIdeaRequestMessages).get(this.translator);
    }

    /**
     * Callback for handling the actual submit when the form is valid.
     *
     * @param  Element  form
     * @param  Event  event
     * @param  ReplyIdeaRequest  that
     * @return void
     */
    handler(form, event, that)
    {
        that.createIdea.initializePageOverlay("Posting Reply...");
        let formData = new FormData(form);
        formData.append("Status", 2);
        $.ajax({
            url: form.action,
            type: form.method,
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false
        })
        .done((response) => {
            $("#DesignItem").trigger('select2:select');
            that.createIdea.notifySuccessOverlay(response.message.body);
            $(that.formSelector).trigger("reset");
        })
        .fail((jqXHR) => {
            if (jqXHR.status === 422) {
                let response = that.formRequisites.parseJson(that.formOverlayNotification + "NotificationArea", jqXHR.responseText);
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

export default ReplyIdeaRequest;
