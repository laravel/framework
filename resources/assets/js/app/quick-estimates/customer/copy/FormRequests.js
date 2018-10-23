import ChangeRoomsRequest from "./requests/ChangeRoomsRequest";
import CopyEstimateRequest from "./requests/CopyEstimateRequest";

/**
 * FormRequests class for creating estimation form requests.
 *
 * Initializes form validations for quick estimation,
 * change rooms and register the reset form handlers.
 */
class FormRequests
{
    /**
     * Get notifier for the FormRequests.
     *
     * @var /foundation/Notifier|undefined
     */
    notifier = undefined;

    /**
     * Get translator for the FormRequests.
     *
     * @var /foundation/Translator|undefined
     */
    translator = undefined;

    /**
     * Get form requisites for the FormRequests.
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
     * Create a new instance of FormRequests.
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
     * Initialize estimate page components.
     *
     * @return Object
     */
    execute()
    {
        return {
            "copyEstimateRequest": this.initializeCopyEstimateRequest(),
            "changeRoomsRequest": this.initializeChangeRoomsRequest(),
        };
    }

    /**
     * Initialize copy quick estimate request.
     *
     * @return ./requests/CopyEstimateRequest
     */
    initializeCopyEstimateRequest()
    {
        let request = new CopyEstimateRequest();
        request.init(this.notifier, this.translator, this.formRequisites, this.application);
        $(request.formSelector).validate(request.getOptions());
        // Register copy quick estimate form reset handler.
        this.registerCopyEstimateFormResetHandler(request.formSelector);

        return request;
    }

    /**
     * Register copy quick estimate form reset handler.
     *
     * @param  Selector  formSelector
     * @return void
     */
    registerCopyEstimateFormResetHandler(formSelector)
    {
        $(formSelector).on("reset", (event) => {
            // Prevent event from happening, because it will override vue instance
            // data by form resetting which will results in unpredictable behaviour.
            event.preventDefault();

            // Trigger an event to destroy all widgets about to be removed.
            $("#CopyQuickEstimateTable").trigger("qe.items.widgets.destroy");
            // Set application data to its default state.
            //
            // When overriding current properties, use "deep clone".
            // Why? Because javascript objects are "shared by reference".
            this.application.estimationName = "";
            this.application.roomItems = _.cloneDeep(this.application.original.roomItems);
            this.application.selectedRooms = _.cloneDeep(this.application.original.selectedRooms);
            this.application.tempSelectedRooms = _.cloneDeep(this.application.original.selectedRooms);
            // Code that will run only after the entire view has been re-rendered.
            // Trigger an event to notify application that items data is ready.
            this.application.$nextTick(() => $("#CopyQuickEstimateTable").trigger("qe.items.ready"));
        });
    }

    /**
     * Initialize change estimation rooms request.
     *
     * @return ./requests/ChangeRoomsRequest
     */
    initializeChangeRoomsRequest()
    {
        let request = new ChangeRoomsRequest();
        request.init(this.notifier, this.translator, this.formRequisites, this.application);
        $(request.formSelector).validate(request.getOptions());
        // Register change estimation rooms form reset handler.
        this.registerChangeRoomsFormResetHandler(request.formSelector);

        return request;
    }

    /**
     * Register change estimation rooms form reset handler.
     *
     * @param  Selector  formSelector
     * @return void
     */
    registerChangeRoomsFormResetHandler(formSelector)
    {
        $(formSelector).on("reset", (event) => {
            // Prevent event from happening, because it will override vue instance
            // data by form resetting which will results in unpredictable behaviour.
            event.preventDefault();

            // Reset temporary selected rooms only if actual selected rooms and
            // current temporary selected rooms differs in terms of their sizes.
            //
            // When overriding current properties, use "deep clone".
            // Why? Because javascript objects are "shared by reference".
            if (! _.isEqual(this.application.tempSelectedRooms, this.application.selectedRooms)) {
                this.application.tempSelectedRooms = _.cloneDeep(this.application.selectedRooms);
            }
        });
    }
}

export default FormRequests;
