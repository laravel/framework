import Request from "foundation/Request";
import ValidationRules from "app/quick-estimates/customer/create/ValidationRules";
import ValidationMessages from "app/quick-estimates/customer/create/ValidationMessages";

/**
 * UpdateCustomItemRequest class for validating update custom item form.
 *
 * Defines rules and messages for the UpdateCustomItemForm validation.
 * Handles updating custom items after form submission and encodes the
 * images to base64 encoding and stores it to browser's local storage.
 */
class UpdateCustomItemRequest extends Request
{
    /**
     * Form selector to perform validation.
     *
     * @var Selector
     */
    formSelector = "#UpdateCustomItemForm";

    /**
     * Show or hide overlay.
     *
     * @var Boolean
     */
    dontHideOverlay = false;

    /**
     * Ignore elements with this selector.
     *
     * @var Selector
     */
    ignore = ".ignore";

    /**
     * Get notifier for the UpdateCustomItemRequest.
     *
     * @var /foundation/Notifier|undefined
     */
    notifier = undefined;

    /**
     * Get translator for the UpdateCustomItemRequest.
     *
     * @var /foundation/Translator|undefined
     */
    translator = undefined;

    /**
     * Get form requisites for the UpdateCustomItemRequest.
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
     * Initialize essentials for the UpdateCustomItemRequest.
     *
     * @param  /foundation/Notifier  notifier
     * @param  /foundation/Translator  translator
     * @param  /foundation/FormRequisites  formRequisites
     * @param  /Vue  application
     * @return void
     */
    init(notifier, translator, formRequisites, application)
    {
        this.notifier = notifier;
        this.translator = translator;
        this.formRequisites = formRequisites;
        this.application = application;
    }

    /**
     * Get validation rules for the application.
     *
     * @return Object
     */
    rules()
    {
        return (new ValidationRules()).get("update");
    }

    /**
     * Get validation messages for the application.
     *
     * @return Object
     */
    messages()
    {
        return (new ValidationMessages()).get(this.translator, "update");
    }

    /**
     * Callback for handling the actual submit when the form is valid.
     *
     * @param  Element  form
     * @param  Event  event
     * @param  ./UpdateCustomItemRequest  that
     * @return void
     */
    handler(form, event, that)
    {
        let item = that.getUpdatedCustomItem(),
            index = _.findIndex(that.application.customItems, ["id", that.application.currentCustomItem.id]);

        // Update the current custom item in custom items list.
        that.application.customItems.splice(index, 1, item);
        // Register event to override the current custom item after updating item.
        $(that.formSelector).on("qe.items.current-custom-item.override", () => {
            this.application.currentCustomItem = _.cloneDeep(item);
            this.application.original.tempCurrentCustomItem = _.cloneDeep(item);
            // Re-initialize custom item payment by tooltip.
            this.application.$nextTick(() => this.reInitializeCustomItemPaymentBy(item.id, item.paymentBy.description));
        });
        // Read and store image into session storage.
        that.readAndUpdateImage(item, index);
        // Change focus to description element from submit button.
        $("#UpdateCustomItemDescription").trigger("focus");

        // Notify user of success response and hide overlay.
        return that.notifier.notifiyOverlaySuccessWithClearance(`${that.formSelector}Overlay`, "Custom item updated");
    }

    /**
     * Update the custom item and return it.
     *
     * @return Object
     */
    getUpdatedCustomItem()
    {
        let paymentByName = $("input[name='UpdateCustomItemPaymentBy']:checked").val(),
            paymentBy = _.find(this.application.paymentByOptions, ["name", paymentByName]),
            item = {
                "id": this.application.currentCustomItem.id,
                "roomId": $("#UpdateCustomItemRoom").val(),
                "description": $("#UpdateCustomItemDescription").val(),
                "quantity": parseInt($("#UpdateCustomItemQuantity").val()),
                "width": parseInt($("#UpdateCustomItemWidth").val()),
                "height": parseInt($("#UpdateCustomItemHeight").val()),
                "depth": parseInt($("#UpdateCustomItemDepth").val()),
                "notes": $("#UpdateCustomItemNotes").val(),
                "categoryId": $("#UpdateCustomItemCategory").val(),
                "paymentBy": {
                    "id": paymentBy.id,
                    "name": paymentBy.name,
                    "description": paymentBy.description,
                    "image": paymentBy.image,
                    "shortcode": paymentBy.shortcode,
                },
                "image": this.application.currentCustomItem.image,
                "isSelected": this.application.currentCustomItem.isSelected,
                "pricePackages": [],
            };

        // Prepare prices for all price packages.
        _.forEach(this.application.pricePackages, (pricePackage) => {
            item.pricePackages.push({
                "id": pricePackage.id,
                "name": pricePackage.name,
                "customerRate": parseInt($(`#UpdateCustomItem-${pricePackage.id}-CustomerPrice`).val()),
                "vendorRate": parseInt($($(`#UpdateCustomItem-${pricePackage.id}-VendorPrice`)).val()),
            });
        });

        return item;
    }

    /**
     * Re-initialize given custom item payment by tooltip.
     *
     * @param  String  id
     * @param  String  title
     * @return void
     */
    reInitializeCustomItemPaymentBy(id, title)
    {
        let node = $(`#${id}-CustomItem-PaymentBy`);
        node.tooltip("destroy");
        // Do not remove "timeout" function on re-creating tooltip.
        //
        // Keep this timeout of 1 second for re-creating a tooltip.
        // There is weird behaviour of "destroy" method which might be a async operation
        // of destroying event handlers and its data. So we have to wait until prev tooltip
        // destroyed. Right now using 1 second as deferred execution for re-creating tooltip.
        // If its not working out fine then we can increase to 2 seconds which is enough time
        // to re-create tooltip with updated title.
        setTimeout(() => {
            node.tooltip({
                "container": "body",
                "placement": "top",
                "title": title,
            });
        }, 1000);
    }

    /**
     * Read and update image into session storage.
     *
     * @param  Object  item
     * @param  Integer  index
     * @return void
     */
    readAndUpdateImage(item, index)
    {
        let imageNode = $("#UpdateCustomItemImage");
        // Check whether there is an update image node on DOM.
        if (imageNode.length == 1) {
            let images = imageNode.get(0).files;
            // Check whether current item's image is removed.
            // CASE 1: Removed image which is already presented on custom item.
            if (images.length == 0 && _.isPlainObject(this.application.original.tempCurrentCustomItem.image)) {
                // Purge item's old image from session storage.
                return this.purgeItemOldImage(item);
            }
            // Check whether current item's image is updated.
            // CASE 2: Updated image which is already presented on custom item.
            // CASE 3: New image for custom item which is not present right now.
            if (
                (images.length > 0 && _.isPlainObject(this.application.original.tempCurrentCustomItem.image)) ||
                (images.length > 0 && this.application.original.tempCurrentCustomItem.image === false)
            ) {
                // Make a promise and update image into session storage.
                return this.readImage(images[0], item.id, index).then((value) => this.updateImage(value));

                // Other way to read and update image using promises.
                //
                // Do not remove the scope binding "this" on update image method. You will not get
                // "this" reference in update image method without binding. Why? because "this" is
                // always the object the method is called on. However while using promises, passing
                // the method to then(), we are "not calling it"!. So the method will be stored
                // somewhere and called from there later. So to preserve this" use scope binding or
                // use ES6 closure like in the above statement.
                //
                // return this.readImage(images[0], itemId, index).then(this.updateImage.bind(this));
            }
        }

        // Override to updated custom item.
        // CASE 4: When image is already present on custom item.
        return $(this.formSelector).trigger("qe.items.current-custom-item.override");
    }

    /**
     * Purge item's old image from session storage.
     *
     * @param  Object  item
     * @return void
     */
    purgeItemOldImage(item)
    {
        item.image = false;
        this.application.original.tempCurrentCustomItem.image = false;
        $(`#${item.id}-CustomItem-Image`).popover("destroy");
        $(this.formSelector).trigger("qe.items.current-custom-item.override");
        return window.sessionStorage.removeItem(item.id);
    }

    /**
     * Read given image into base64 encoded string.
     *
     * @param  File  image
     * @param  String  itemId
     * @param  Integer  index
     * @return Promise
     */
    readImage(image, itemId, index)
    {
        return new Promise((resolve, reject) => {
            let fileReader = new FileReader();
            fileReader.addEventListener("load", () => resolve({
                "id": itemId,
                "name": image.name,
                "type": image.type,
                "image": fileReader.result,
                "index": index,
            }), false);
            fileReader.addEventListener("error", () => reject, false);
            fileReader.readAsDataURL(image);
        });
    }

    /**
     * Update encoded image into session storage.
     *
     * @param  Object  value
     * @return Promise
     */
    updateImage(value)
    {
        window.sessionStorage.setItem(value.id, value.image);
        this.application.customItems[value.index].image = {
            "name": value.name,
            "type": value.type,
        };
        this.application.original.tempCurrentCustomItem.image = {
            "name": value.name,
            "type": value.type,
        };
        $(this.formSelector).trigger("qe.items.current-custom-item.override");
        this.application.$nextTick(() => this.initializeCustomItemPopover(value.id));
    }

    /**
     * Initialize image popover of the custom item.
     *
     * @param  String  id
     * @return void
     */
    initializeCustomItemPopover(id)
    {
        let node = $(`#${id}-CustomItem-Image`);
        node.popover("destroy");
        node.popover({
            "container": "body",
            "content": this.getReferenceImagePopoverContent(node.data("sessionStorageId")),
            "html": true,
            "placement": "right",
            "trigger": "hover",
        });
    }

    /**
     * Get reference image popover html content.
     *
     * @param  String  id
     * @return String
     */
    getReferenceImagePopoverContent(id)
    {
        return `<img src="${window.sessionStorage.getItem(id)}" alt="Reference image" class="img-responsive"/>`;
    }
}

export default UpdateCustomItemRequest;
