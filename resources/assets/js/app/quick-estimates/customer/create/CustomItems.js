import { applyKeyboardNavigationPatch } from "support/Select2Patches";

/**
 * CustomItems class for handling custom items for application.
 *
 * Register modal events for create and update custom items,
 * initializes rooms, category and ratecard items select2, fetches
 * ratecards from the server for selected item and populates it.
 */
class CustomItems
{
    /**
     * Get notifier for the CustomItems.
     *
     * @var /foundation/Notifier|undefined
     */
    notifier = undefined;

    /**
     * Get translator for the CustomItems.
     *
     * @var /foundation/Translator|undefined
     */
    translator = undefined;

    /**
     * Get form requisites for the CustomItems.
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
     * Create a new instance of CustomItems.
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
     * @param  Selector  createFormSelector
     * @param  Selector  updateFormSelector
     * @return void
     */
    execute(createFormSelector, updateFormSelector)
    {
        // Register custom item events.
        this.registerCreateCustomItemModal();
        this.registerCreateCustomItemModalEvents();
        this.registerUpdateCustomItemModalEvents();

        // Initialize custom item select2.
        this.initializeCustomItemRoomSelect2();
        this.initializeCustomItemCategorySelect2();
        this.initializeCreateCustomItemRatecardSelect2(createFormSelector);
        this.initializeUpdateCustomItemRatecardSelect2(updateFormSelector);
    }

    /**
     * Register create custom items modal.
     *
     * @return void
     */
    registerCreateCustomItemModal()
    {
        $("#CreateCustomItem").on("click", (event) => {
            event.preventDefault();
            $("#CreateCustomItemModal").modal("show");
        });
    }

    /**
     * Register create custom item modal events handlers.
     *
     * @return void
     */
    registerCreateCustomItemModalEvents()
    {
        // Focus on description input on modal show.
        $("#CreateCustomItemModal").on("shown.bs.modal", () => $("#CustomItemDescription").trigger("focus"));
        // Reset create custom item form on its modal when hidden.
        $("#CreateCustomItemModal").on("hidden.bs.modal", () => {
            // Reset current custom item to its original template.
            this.application.currentCustomItem = _.cloneDeep(this.application.original.currentCustomItem);
            // Reset form and its validator to its original state.
            $("#CreateCustomItemForm").trigger("reset").data("validator").resetForm();
        });
    }

    /**
     * Register update custom item modal events handlers.
     *
     * @return void
     */
    registerUpdateCustomItemModalEvents()
    {
        // Trigger change on custom item room and focus on description input on modal is shown.
        $("#UpdateCustomItemModal").on("shown.bs.modal", () => {
            $("#UpdateCustomItemRoom, #UpdateCustomItemCategory").trigger("change");
            $("#UpdateCustomItemDescription").trigger("focus");
        });
        // Reset update custom item form on its modal when hidden.
        $("#UpdateCustomItemModal").on("hide.bs.modal", () => {
            // Clear custom item reference image if selected any.
            if (this.application.currentCustomItem.image === false) {
                $("#UpdateCustomItemImage").val("");
            }
            // Reset current custom item to its original template.
            this.application.currentCustomItem = _.cloneDeep(this.application.original.currentCustomItem);
            this.application.original.tempCurrentCustomItem = undefined;
            // Reset update item ratecards and its form validator to its original state.
            $("#UpdateCustomItemRatecardItems").val("").trigger("change");
            $("#UpdateCustomItemForm").data("validator").resetForm();
        });
    }

    /**
     * Initialize create and update custom item room select2.
     *
     * @return void
     */
    initializeCustomItemRoomSelect2()
    {
        $("select.custom-item-room").each(function () {
            let node = $(this);
            node.select2({
                "placeholder": "Select a room from dropdown",
            }).on("select2:select", () => {
                return node.val().length !== 0 && node.valid();
            });
            applyKeyboardNavigationPatch(node);
        });
    }

    /**
     * Initialize create and update custom item category select2.
     *
     * @return void
     */
    initializeCustomItemCategorySelect2()
    {
        $("select.custom-item-category").each(function () {
            let node = $(this);
            node.select2({
                "placeholder": "Select a category from dropdown",
            }).on("select2:select", () => {
                return node.val().length !== 0 && node.valid();
            });
            applyKeyboardNavigationPatch(node);
        });
    }

    /**
     * Initialize create custom item ratecard select2.
     *
     * @param  Selector  formSelector
     * @return void
     */
    initializeCreateCustomItemRatecardSelect2(formSelector)
    {
        let node = $("#CustomItemRatecardItems");
        node.select2({
            "placeholder": "Select a ratecard item from dropdown",
        }).on("select2:select", () => {
            if (node.val().length !== 0 && node.valid()) {
                return this.fetchItemCurrentRatecard(
                    node.data("ratecardsUrl").replace("select", node.val()),
                    formSelector
                );
            }
        });
        applyKeyboardNavigationPatch(node);
    }

    /**
     * Fetch item's current ratecards.
     *
     * @param  String  url
     * @param  Selector  formSelector
     * @param  Boolean  update
     * @return void
     */
    fetchItemCurrentRatecard(url, formSelector, update = false)
    {
        this.formRequisites.prepareFormForAjax(`${formSelector}Overlay`, "Fetching item's ratecards");
        $.ajax({
            url: url,
            type: "GET",
            dataType: "json",
        })
        .done((response) => {
            if (update == false) {
                return this.setCreateItemCurrentRatecards(response.ratecards);
            }
            return this.setUpdateItemCurrentRatecards(response.ratecards);
        })
        .fail(() => {
            // Notify user of backend system failure, if no form validation errors
            // are responsible for error on ajax submission to the backend server.
            this.notifier.notify(`${formSelector}NotificationArea`, {
                status: "error",
                message: this.translator.trans("system.failure"),
            });
        })
        .always(() => {
            // Always hide overlay on request completion.
            this.formRequisites.hideOverlay(`${formSelector}Overlay`);
        });
    }

    /**
     * Set create custom item's current ratecards.
     *
     * @param  JSONObject  ratecards
     * @return void
     */
    setCreateItemCurrentRatecards(ratecards)
    {
        // Set custom item customer and vendor prices.
        _.forEach(this.application.pricePackages, (pricePackage) => {
            let ratecard = _.find(ratecards, ["id", pricePackage.id]);
            $(`#CustomItem-${pricePackage.id}-CustomerPrice`).val(ratecard.customerRate).valid();
            $(`#CustomItem-${pricePackage.id}-VendorPrice`).val(ratecard.vendorRate).valid();
        });
    }

    /**
     * Initialize update custom item ratecard select2.
     *
     * @param  Selector  formSelector
     * @return void
     */
    initializeUpdateCustomItemRatecardSelect2(formSelector)
    {
        let node = $("#UpdateCustomItemRatecardItems");
        node.select2({
            "placeholder": "Select a ratecard item from dropdown",
        }).on("select2:select", () => {
            if (node.val().length !== 0 && node.valid()) {
                return this.fetchItemCurrentRatecard(
                    node.data("ratecardsUrl").replace("select", node.val()),
                    formSelector,
                    true
                );
            }
        });
        applyKeyboardNavigationPatch(node);
    }

    /**
     * Set update custom item current ratecards.
     *
     * @param  JSONObject  ratecards
     * @return void
     */
    setUpdateItemCurrentRatecards(ratecards)
    {
        // Set custom item customer and vendor price.
        _.forEach(this.application.currentCustomItem.pricePackages, (pricePackage) => {
            let ratecard = _.find(ratecards, ["id", pricePackage.id]);
            pricePackage.customerRate = ratecard.customerRate;
            pricePackage.vendorRate = ratecard.vendorRate;
        });
    }
}

export default CustomItems;
