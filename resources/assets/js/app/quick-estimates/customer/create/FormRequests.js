import ChangeRoomsRequest from "./requests/ChangeRoomsRequest";
import CreateEstimateRequest from "./requests/CreateEstimateRequest";
import CreateCustomItemRequest from "./requests/CreateCustomItemRequest";
import UpdateCustomItemRequest from "./requests/UpdateCustomItemRequest";

/**
 * FormRequests class for creating estimation form requests.
 *
 * Initializes form validations for quick estimation, change rooms,
 * create and update custom items and register reset form handlers.
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
            "createEstimateRequest": this.initializeCreateEstimateRequest(),
            "changeRoomsRequest": this.initializeChangeRoomsRequest(),
            "createCustomItemRequest": this.initializeCreateCustomItemRequest(),
            "updateCustomItemRequest": this.initializeUpdateCustomItemRequest(),
        };
    }

    /**
     * Initialize create quick estimate request.
     *
     * @return ./requests/CreateEstimateRequest
     */
    initializeCreateEstimateRequest()
    {
        let request = new CreateEstimateRequest();
        request.init(this.notifier, this.translator, this.formRequisites, this.application);
        $(request.formSelector).validate(request.getOptions());
        // Register create quick estimate form reset handler.
        this.registerCreateEstimateFormResetHandler(request.formSelector);

        return request;
    }

    /**
     * Register create quick estimate form reset handler.
     *
     * @param  Selector  formSelector
     * @return void
     */
    registerCreateEstimateFormResetHandler(formSelector)
    {
        $(formSelector).on("reset", (event) => {
            // Prevent event from happening, because it will override vue instance
            // data by form resetting which will results in unpredictable behaviour.
            event.preventDefault();

            // Trigger an event to destroy all widgets about to be removed.
            $("#CreateQuickEstimateTable").trigger("qe.items.widgets.destroy");
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
            this.application.$nextTick(() => $("#CreateQuickEstimateTable").trigger("qe.items.ready"));
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

    /**
     * Initialize create custom item request.
     *
     * @return ./requests/CreateCustomItemRequest
     */
    initializeCreateCustomItemRequest()
    {
        let request = new CreateCustomItemRequest();
        request.init(this.notifier, this.translator, this.formRequisites, this.application);
        $(request.formSelector).validate(request.getOptions());
        // Add validation rules for custom item prices.
        this.addValidationsForCustomItemPrices();
        // Register create custom item form reset handler.
        this.registerCreateCustomItemFormResetHandler(request.formSelector);

        return request;
    }

    /**
     * Add validation rules for creating custom item's customer and vendor prices.
     *
     * @return void
     */
    addValidationsForCustomItemPrices()
    {
        // Add validation rules for create custom item customer prices.
        $("input.custom-item-customer-price").each(function () {
            // Get price package element name from the input attribute "id".
            let node = $(this);
            // Apply validation rules.
            node.rules("add", {
                required: true,
                min() {
                    // Get parsed float of vendor price.
                    let vendorPrice = parseFloat($(`#${node.attr("id").replace("CustomerPrice", "VendorPrice")}`).val());

                    // Fix "NaN" problem during validation.
                    return isNaN(vendorPrice) ? 0 : vendorPrice;
                },
                messages: {
                    required: "Customer Price can't be blank.",
                },
            });
        });

        // Add validation rules for create custom item vendor prices.
        $("input.custom-item-vendor-price").each(function () {
            // Get price package element name from the input attribute "id".
            let node = $(this);
            // Check customer price on change of vendor price.
            node.on("input", () => $(`#${node.attr("id").replace("VendorPrice", "CustomerPrice")}`).valid());
            // Apply validation rules.
            node.rules("add", {
                required: true,
                min: 1,
                messages: {
                    required: "Vendor Price can't be blank.",
                },
            });
        });
    }

    /**
     * Register create custom item form reset handler.
     *
     * @param  Selector  formSelector
     * @return void
     */
    registerCreateCustomItemFormResetHandler(formSelector)
    {
        $(formSelector).on("reset", () => {
            setTimeout(() => {
                $("#CustomItemRoom, #CustomItemRatecardItems, #CustomItemCategory").trigger("change");
                $(formSelector).data("validator").resetForm();
            }, 0);
        });
    }

    /**
     * Initialize update custom item request.
     *
     * @return ./requests/UpdateCustomItemRequest
     */
    initializeUpdateCustomItemRequest()
    {
        let request = new UpdateCustomItemRequest();
        request.init(this.notifier, this.translator, this.formRequisites, this.application);
        $(request.formSelector).validate(request.getOptions());
        // Add validation rules for custom item prices.
        this.addValidationsForUpdateCustomItemPrices();
        // Register update custom item form reset handler.
        this.registerUpdateCustomItemFormResetHandler(request.formSelector);

        return request;
    }

    /**
     * Add validation rules for updating custom item's customer and vendor prices.
     *
     * @return void
     */
    addValidationsForUpdateCustomItemPrices()
    {
        // Add validation rules for update custom item customer prices.
        $("input.update-custom-item-customer-price").each(function () {
            // Get price package element name from the input attribute "id".
            let node = $(this);
            // Apply validation rules.
            node.rules("add", {
                required: true,
                min() {
                    // Get parsed float of vendor price.
                    let vendorPrice = parseFloat($(`#${node.attr("id").replace("CustomerPrice", "VendorPrice")}`).val());

                    // Fix "NaN" problem during validation.
                    return isNaN(vendorPrice) ? 0 : vendorPrice;
                },
                messages: {
                    required: "Customer Price can't be blank.",
                },
            });
        });

        // Add validation rules for update custom item vendor prices.
        $("input.update-custom-item-vendor-price").each(function () {
            // Get price package element name from the input attribute "id".
            let node = $(this);
            // Check customer price on change of vendor price.
            node.on("input", () => $(`#${node.attr("id").replace("VendorPrice", "CustomerPrice")}`).valid());
            // Apply validation rules.
            node.rules("add", {
                required: true,
                min: 1,
                messages: {
                    required: "Vendor Price can't be blank.",
                },
            });
        });
    }

    /**
     * Register create custom item form reset handler.
     *
     * @param  Selector  formSelector
     * @return void
     */
    registerUpdateCustomItemFormResetHandler(formSelector)
    {
        $(formSelector).on("reset", (event) => {
            // Prevent event from happening, because it will override vue instance
            // data by form resetting which will results in unpredictable behaviour.
            event.preventDefault();

            // Reset current selected custom item data and check form validity.
            //
            // When overriding current properties, use "deep clone".
            // Why? Because javascript objects are "shared by reference".
            this.application.currentCustomItem = _.cloneDeep(this.application.original.tempCurrentCustomItem);
            this.application.$nextTick(() => {
                $("#UpdateCustomItemRoom, #UpdateCustomItemCategory").trigger("change");
                $("#UpdateCustomItemRatecardItems").val("").trigger("change");
                $(formSelector).valid();
            });
        });
    }
}

export default FormRequests;
