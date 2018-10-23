import Request from "foundation/Request";
import SentenceRule from "validation/rules/SentenceRule";

/**
 * CreateEstimateRequest class for validating create estimate form.
 *
 * Defines rules and messages for the CreateEstimateForm validation.
 * Adds sentence validation method and handles ajax form submission.
 */
class CreateEstimateRequest extends Request
{
    /**
     * Form selector to perform validation.
     *
     * @var Selector
     */
    formSelector = "#CreateQuickEstimateForm";

    /**
     * Show or hide overlay.
     *
     * @var Boolean
     */
    dontHideOverlay = false;

    /**
     * Get notifier for the CreateEstimateRequest.
     *
     * @var /foundation/Notifier|undefined
     */
    notifier = undefined;

    /**
     * Get translator for the CreateEstimateRequest.
     *
     * @var /foundation/Translator|undefined
     */
    translator = undefined;

    /**
     * Get form requisites for the CreateEstimateRequest.
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
     * Initialize essentials for the CreateEstimateRequest.
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
        this.addCustomValidationMethods();
    }

    /**
     * Add additional custom validation methods.
     *
     * @return void
     */
    addCustomValidationMethods()
    {
        (new SentenceRule()).add();
    }

    /**
     * Get validation rules for the application.
     *
     * @return Object
     */
    rules()
    {
        return {
            "Name": {
                "required": true,
                "CheckConsecutiveSpaces": true,
            },
        };
    }

    /**
     * Get validation messages for the application.
     *
     * @return Object
     */
    messages()
    {
        return {
            "Name": {
                "required": this.translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "Estimation Name",
                    },
                ]),
            },
        };
    }

    /**
     * Callback for handling the actual submit when the form is valid.
     *
     * @param  Element  form
     * @param  Event  event
     * @param  ./CreateEstimateRequest  that
     * @return void
     */
    handler(form, event, that)
    {
        // Check whether atleast one item is selected, if not show no items selected warning.
        if (that.application.showNoItemsWarning) {
            $("#CreateQuickEstimateTable").trigger("qe.items.not-selected");
            return false;
        }
        // Prepare form for submission and convert any base64 encoded images to blobs.
        that.formRequisites.prepareFormForAjax(`${that.formSelector}Overlay`, "Creating Quick Estimation");
        // Scroll current window position to half of the current view.
        $(window).scrollTop($("form#CreateQuickEstimateForm > div.table-responsive").height() / 2);
        // Prepare form data for ajax submission.
        let formData = new FormData();
        formData.append("Name", that.application.estimationName);
        formData.append("Rooms", JSON.stringify(that.application.selectedRooms));
        formData.append("SelectedItems", that.getSelectedItems());
        formData.append("CustomItems", that.getCustomItems());
        if (that.application.customItems.length > 0) {
            that.decodeImagesAndSubmit(form, formData);
        } else {
            $("#CreateQuickEstimateForm").trigger("qe.items.ready-to-submit", [form, formData]);
        }
    }

    /**
     * Get all selected items for creating quick estimation.
     *
     * @return String
     */
    getSelectedItems()
    {
        let items = [];
        // Prepare list of items data for submission.
        _.forEach(this.application.roomItems, (room) => {
            _.forEach(room.items, (item) => {
                if (item.isSelected === true) {
                    items.push({
                        "id": item.id,
                        "roomId": room.id,
                        "quantity": item.quantity,
                        "width": item.width,
                        "height": item.height,
                        "notes": item.customerNotes,
                    });
                }
            });
        });

        return JSON.stringify(items);
    }

    /**
     * Get all selected custom items for creating quick estimation.
     *
     * @return String
     */
    getCustomItems()
    {
        let items = [];
        // Prepare custom items data for submission.
        _.forEach(this.application.customItems, (customItem) => {
            if (customItem.isSelected === true) {
                let item = _.cloneDeep(customItem);
                item.paymentBy = _.isPlainObject(item.paymentBy) ? item.paymentBy.id : null;
                items.push(item);
            }
        });

        return JSON.stringify(items);
    }

    /**
     * Initialize decoding base64 images back to png and submit the form.
     *
     * @param  Element  form
     * @param  FormData  formData
     * @return void
     */
    decodeImagesAndSubmit(form, formData)
    {
        let promises = [];
        // Create promises for custom items images decoding.
        _.forEach(this.application.customItems, (customItem) => {
            if (_.isPlainObject(customItem.image)) {
                promises.push(this.loadImage(customItem).then(this.drawImage).then(this.getBlob));
            }
        });
        // Run all promises and store decoded images onto form data.
        Promise.all(promises).then((values) => {
            _.forEach(values, (value) => {
                formData.append(value.id, value.blob, value.name);
            });
            // Trigger "qe.items.ready-to-submit" event for estimate form submission.
            $("#CreateQuickEstimateForm").trigger("qe.items.ready-to-submit", [form, formData]);
        })
    }

    /**
     * Load image of given custom item id from session storage.
     *
     * @param  Object  customItem
     * @return Promise
     */
    loadImage(customItem)
    {
        return new Promise((resolve, reject) => {
            let image = new Image();
            image.addEventListener("load", () => resolve({
                "id": customItem.id,
                "image": image,
                "name": customItem.image.name,
                "type": customItem.image.type,
            }), false);
            image.addEventListener("error", reject, false);
            image.src = window.sessionStorage.getItem(customItem.id);
        });
    }

    /**
     * Draw image onto a canvas and convert it into a blob.
     *
     * @param  Object  value
     * @return Promise
     */
    drawImage(value)
    {
        return new Promise((resolve, reject) => {
            let canvas = document.createElement("canvas");
            // It is very important to have "canvas" width and height.
            // Other wise only some portion of given image is drawn.
            canvas.width = value.image.width;
            canvas.height = value.image.height;
            canvas.getContext("2d").drawImage(value.image, 0, 0, value.image.width, value.image.height);
            canvas.toBlob((blob) => resolve({
                "id": value.id,
                "blob": blob,
                "name": value.name,
            }), value.type);
        });
    }

    /**
     * Get blob from the canvas and return a promise.
     *
     * @param  Object  value
     * @return Promise
     */
    getBlob(value)
    {
        return new Promise((resolve, reject) => resolve(value));
    }
}

export default CreateEstimateRequest;
