import Request from "foundation/Request";
import { generateRandomId } from "support/Utilities";
import ValidationRules from "app/quick-estimates/customer/create/ValidationRules";
import ValidationMessages from "app/quick-estimates/customer/create/ValidationMessages";

/**
 * CreateCustomItemRequest class for validating create custom item form.
 *
 * Defines rules and messages for the CreateCustomItemForm validation.
 * Handles updating custom items after form submission and generates
 * psuedo-random ids, encodes images to base64 encoding and stores it
 * to the browser's local storage.
 */
class CreateCustomItemRequest extends Request
{
    /**
     * Form selector to perform validation.
     *
     * @var Selector
     */
    formSelector = "#CreateCustomItemForm";

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
     * Get notifier for the CreateCustomItemRequest.
     *
     * @var /foundation/Notifier|undefined
     */
    notifier = undefined;

    /**
     * Get translator for the CreateCustomItemRequest.
     *
     * @var /foundation/Translator|undefined
     */
    translator = undefined;

    /**
     * Get form requisites for the CreateCustomItemRequest.
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
     * Initialize essentials for the CreateCustomItemRequest.
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
     * @param  ./CreateCustomItemRequest  that
     * @return void
     */
    handler(form, event, that)
    {
        event.preventDefault();
        let item = that.getCustomItem(),
            index = that.application.customItems.length;

        // Store the item into custom items.
        that.application.customItems.push(item);
        // Read and store image into session storage.
        that.readAndStoreImage(item.id, index);
        // Change focus to description element from submit button.
        $("#CustomItemDescription").trigger("focus");
        // Initialize items widgets after DOM next update cycle.
        that.application.$nextTick(() => {
            this.initializeCustomItemNotes(item.id);
            this.initializeCustomItemPaymentBy(item.id, item.paymentBy.description);
        });

        // Notify user of success response and hide overlay.
        return that.notifier.notifiyOverlaySuccessWithClearance(`${that.formSelector}Overlay`, "Custom item created");
    }

    /**
     * Prepare the custom item and return it.
     *
     * @return Object
     */
    getCustomItem()
    {
        let paymentByName = $("input[name='CustomItemPaymentBy']:checked").val(),
            paymentBy = _.find(this.application.paymentByOptions, ["name", paymentByName]),
            item = {
                "id": generateRandomId(),
                "roomId": $("#CustomItemRoom").val(),
                "description": $("#CustomItemDescription").val(),
                "quantity": parseInt($("#CustomItemQuantity").val()),
                "width": parseInt($("#CustomItemWidth").val()),
                "height": parseInt($("#CustomItemHeight").val()),
                "depth": parseInt($("#CustomItemDepth").val()),
                "notes": $("#CustomItemNotes").val(),
                "categoryId": $("#CustomItemCategory").val(),
                "paymentBy": {
                    "id": paymentBy.id,
                    "name": paymentBy.name,
                    "description": paymentBy.description,
                    "image": paymentBy.image,
                    "shortcode": paymentBy.shortcode,
                },
                "image": false,
                "isSelected": true,
                "pricePackages": [],
            };

        // Prepare prices for all price packages.
        _.forEach(this.application.pricePackages, (pricePackage) => {
            item.pricePackages.push({
                "id": pricePackage.id,
                "name": pricePackage.name,
                "customerRate": parseInt($(`#CustomItem-${pricePackage.id}-CustomerPrice`).val()),
                "vendorRate": parseInt($($(`#CustomItem-${pricePackage.id}-VendorPrice`)).val()),
            });
        });

        return item;
    }

    /**
     * Read and store image into session storage.
     *
     * @param  String  itemId
     * @param  Integer  index
     * @return void
     */
    readAndStoreImage(itemId, index)
    {
        let images = $("#CustomItemImage").get(0).files;
        // Check whether there are any images selected.
        if (images.length > 0) {
            // Make a promise and store image into session storage.
            return this.readImage(images[0], itemId, index).then((value) => this.storeImage(value));

            // Other way to read and store image using promises.
            //
            // Do not remove the scope binding "this" on store image method. You will not get
            // "this" reference in store image method without binding. Why? because "this" is
            // always the object the method is called on. However while using promises, passing
            // the method to then(), we are "not calling it"!. So the method will be stored
            // somewhere and called from there later. So to preserve this" use scope binding or
            // use ES6 closure like in the above statement.
            //
            // return this.readImage(images[0], itemId, index).then(this.storeImage.bind(this));
        }
        // Reset the form if there are no images to store.
        $(this.formSelector).trigger("reset");
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
     * Store encoded image into session storage.
     *
     * @param  Object  value
     * @return Promise
     */
    storeImage(value)
    {
        window.sessionStorage.setItem(value.id, value.image);
        this.application.customItems[value.index].image = {
            "name": value.name,
            "type": value.type,
        };
        this.application.$nextTick(() => this.initializeCustomItemPopover(value.id));
        $(this.formSelector).trigger("reset");
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

    /**
     * Resize notes textarea on "focus" and restore on "blur".
     *
     * @param  String  id
     * @return void
     */
    initializeCustomItemNotes(id)
    {
        $(`#${id}-CustomItem-Notes`).on({
            focus() { $(this).attr("rows", 3); },
            blur() { $(this).attr("rows", 1); },
        });
    }

    /**
     * Initialize given custom item payment by tooltip.
     *
     * @param  String  id
     * @param  String  title
     * @return void
     */
    initializeCustomItemPaymentBy(id, title)
    {
        $(`#${id}-CustomItem-PaymentBy`).tooltip({
            "container": "body",
            "placement": "top",
            "title": title,
        });
    }
}

export default CreateCustomItemRequest;
