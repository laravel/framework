import Request from "foundation/Request";
import ValidationRules from "./ValidationRules";
import ValidationMessages from "./ValidationMessages";

/**
 * SelectEnquiryRequest class for validating select estimate form.
 *
 * Defines rules and messages, handles ajax form submission.
 */
class SelectEnquiryRequest extends Request
{
    /**
     * Form selector to perform validation.
     *
     * @var Selector
     */
    formSelector = "#SelectEnquiryForm";

    /**
     * Show or hide overlay.
     *
     * @var Boolean
     */
    dontHideOverlay = false;

    /**
     * Get notifier for the SelectEnquiryRequest.
     *
     * @var /foundation/Notifier|undefined
     */
    notifier = undefined;

    /**
     * Get translator for the SelectEnquiryRequest.
     *
     * @var /foundation/Translator|undefined
     */
    translator = undefined;

    /**
     * Get form requisites for the SelectEnquiryRequest.
     *
     * @var /foundation/FormRequisites|undefined
     */
    formRequisites = undefined;

    /**
     * Initialize essentials for the SelectEnquiryRequest.
     *
     * @param  /foundation/Notifier  notifier
     * @param  /foundation/Translator  translator
     * @param  /foundation/FormRequisites  formRequisites
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
     * Customize placement of created error labels.
     *
     * @param  jQuery  error
     * @param  jQuery  element
     * @return jQuery
     */
    errorPlacement(error, element)
    {
        if (element.attr("name") == "Rooms[]") {
            return error.appendTo(element.closest("div.row").find("div#RoomsErrorBlock").first());
        }
        return error.appendTo(element.parent());
    }

    /**
     * Callback for handling the actual submit when the form is valid.
     *
     * @param  Element  form
     * @param  Event  event
     * @param  ./SelectEnquiryRequest  that
     * @return void
     */
    handler(form, event, that)
    {
        that.formRequisites.prepareFormForAjax(`${that.formSelector}Overlay`, "Fetching Quick Estimate Form");
        let node = $(form), enquiryNode = $("#Enquiry");
        node.attr("action", node.attr("action").replace("select", enquiryNode.val()));
        enquiryNode.removeAttr("name");
        form.submit();
    }
}

export default SelectEnquiryRequest;
