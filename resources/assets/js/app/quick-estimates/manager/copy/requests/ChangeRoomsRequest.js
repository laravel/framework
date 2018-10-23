import Request from "foundation/Request";

/**
 * ChangeRoomsRequest class for validating change rooms form.
 *
 * Defines rules and messages for the ChangeRoomsForm validation.
 * Handles ajax form submission by fetching and removing items.
 */
class ChangeRoomsRequest extends Request
{
    /**
     * Form selector to perform validation.
     *
     * @var Selector
     */
    formSelector = "#ChangeRoomsForm";

    /**
     * Show or hide overlay.
     *
     * @var Boolean
     */
    dontHideOverlay = false;

    /**
     * Get notifier for the ChangeRoomsRequest.
     *
     * @var /foundation/Notifier|undefined
     */
    notifier = undefined;

    /**
     * Get translator for the ChangeRoomsRequest.
     *
     * @var /foundation/Translator|undefined
     */
    translator = undefined;

    /**
     * Get form requisites for the ChangeRoomsRequest.
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
     * Initialize essentials for the ChangeRoomsRequest.
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
        return {
            "Rooms[]": {
                "required": true,
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
            "Rooms[]": {
                "required": this.translator.trans('validation.required', [
                    {
                        "replace": "attribute",
                        "with": "Rooms",
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
     * @param  ./ChangeRoomsRequest  that
     * @return void
     */
    handler(form, event, that)
    {
        event.preventDefault();
        that.formRequisites.prepareFormForAjax(`${that.formSelector}Overlay`, "Fetching Items");
        let serializedRooms = that.normalizeRooms();
        $.ajax({
            url: `${form.action}?${serializedRooms}`,
            type: form.method,
            dataType: "json",
        })
        .done((response) => {
            // Destroy all widgets about to be removed.
            $("#CreateQuickEstimateTable").trigger("qe.items.widgets.destroy");
            // Concat the response items and any old items.
            let roomItems = _.concat(that.application.roomItems, response.roomItems);
            // Sort out room items based on room name and set it on vue instance.
            that.application.roomItems = _.orderBy(roomItems, ["order"], ["asc"]);
            // Reset selected rooms data from "temp data" --> as it is now source of truth.
            that.application.selectedRooms = that.application.tempSelectedRooms;
            // Code that will run only after the entire view has been re-rendered.
            // Trigger an event to notify application that items data is ready.
            that.application.$nextTick(() => $("#CreateQuickEstimateTable").trigger("qe.items.ready"));

            // Notify user of success response and hide overlay.
            that.notifier.notifiyOverlaySuccessWithClearance(`${that.formSelector}Overlay`, "Items fetched.");
        })
        .fail((jqXHR) => {
            // Check whether form validation errors exists on ajax submission failure.
            if (jqXHR.status === 422) {
                // Try to parse the json response, if fails notify user of "unknown failure" to ui.
                let response = that.formRequisites.parseJson(`${that.formSelector}NotificationArea`, jqXHR.responseText);
                if (! response) {
                    return false;
                }
                // If json parsing is OK, then populate form errors using validator.
                that.notifier.populateFormErrors(that.formSelector, response.errors);
            } else {
                // Notify user of backend system failure, if no form validation errors
                // are responsible for error on ajax submission to the backend server.
                that.notifier.notify(`${that.formSelector}NotificationArea`, {
                    status: "error",
                    message: that.translator.trans("system.failure")
                });
            }
        })
        .always(() => {
            // Always hide overlay on request completion.
            that.dontHideOverlay = false;
        });
    }

    /**
     * Normalize room items based on temporary rooms selections.
     *
     * @return String
     */
    normalizeRooms()
    {
        let negatedRooms = [];
        // Get negated rooms of selected rooms.
        _.forEach(this.application.rooms, (room) => {
            if (this.application.tempSelectedRooms.indexOf(room.id) == -1 && this.application.defaultRooms.indexOf(room.id) == -1) {
                negatedRooms.push(room.id);
            }
        });
        // Remove all negated rooms from "roomItems" array.
        _.remove(this.application.roomItems, (roomItem) => {
            return negatedRooms.indexOf(roomItem.id) != -1;
        });
        // Remove room ids which are already present on "roomItems" array from selectedRooms.
        let serializedRooms = $("#ChangeRoomsForm").serializeArray();
        _.forEach(this.application.roomItems, (room) => {
            // Remove room ids from serialized form array.
            _.remove(serializedRooms, (serializedRoom) => {
                return serializedRoom.value == room.id;
            });
        });

        // Return url encoded serialized rooms string.
        return $.param(serializedRooms);

        //****************************************************************//
        // "DO NOT DELETE" these comments. Use these comments as a fallback
        // if above removal of negated rooms fails to understand or execute.
        //****************************************************************//
        //
        // Other way to remove where "vue can react directly" to changes.
        //
        // Store indexes into an array because remove in this loop
        // will disturb the index with the loop because the array is mutating
        // after each removal of roomItem which may come out with unpredictable behavior.
        //
        // let indexes = [];
        // _.forEach(this.application.roomItems, (roomItem, index) => {
        //     if (negatedRooms.indexOf(roomItem.id) != -1) {
        //         indexes.push(index);
        //     }
        // });
        //
        // Remove roomItem based its index previously stored.
        // _.forEach(indexes, (index, localIndex) => {
        //     this.application.roomItems.splice(index - localIndex, 1);
        // });
    }
}

export default ChangeRoomsRequest;
