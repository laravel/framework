import Application from "foundation/Application";
import SelectEnquiryRequest from "./SelectEnquiryRequest";
import { applyKeyboardNavigationPatch } from "support/Select2Patches";

/**
 * SelectEnquiry class for selecting rooms before create estimate.
 *
 * Initializes essentials like form validations, select2 for enquiry and
 * city elements and fetches rooms and redirects to create estimate page.
 */
class SelectEnquiry extends Application
{
    /**
     * Initialize page components.
     *
     * @return void
     */
    execute()
    {
        // Initialize form validations.
        let request = new SelectEnquiryRequest();
        request.init(this.notifier, this.translator, this.formRequisites);
        $(request.formSelector).validate(request.getOptions());

        // Initialize select2 for page elements.
        this.initializeEnquirySelect2();
        this.initializeCitySelect2();

        // Initialize rooms count.
        this.initializeRoomsCount();
    }

    /**
     * Initialize select2 for enquiry.
     *
     * @return void
     */
    initializeEnquirySelect2()
    {
        let node = $("#Enquiry");
        node.select2({
            "placeholder": "Select Enquiry from Dropdown",
        }).on("select2:select", () => {
            return (node.val().length !== 0 && node.valid()) ? this.fetchEnquiryRooms(node.val()) : false;
        });
        applyKeyboardNavigationPatch(node);
    }

    /**
     * Initialize select2 for city.
     *
     * @return void
     */
    initializeCitySelect2()
    {
        let node = $("#City");
        node.select2({
            "placeholder": "Select City from Dropdown",
        }).on("select2:select", () => {
            return node.val().length !== 0 && node.valid();
        });
        applyKeyboardNavigationPatch(node);
    }

    /**
     * Fetch enquiry rooms from the server.
     *
     * @param  Integer  enquiryId
     * @return void
     */
    fetchEnquiryRooms(enquiryId)
    {
        this.formRequisites.prepareFormForAjax("#SelectEnquiryFormOverlay", "Fetching Enquiry Rooms");
        $.ajax({
            url: $("#Enquiry").data("roomsRoute").replace("select", enquiryId),
            type: "GET",
            dataType: "json",
        })
        .done((response) => {
            this.unhideHiddenItems();
            this.uncheckPreviousChecked();
            this.selectCity(response.city);
            this.selectRooms(response.rooms);
        })
        .fail(() => {
            this.notifier.notify("#SelectEnquiryFormNotificationArea", {
                "status": "error",
                "message": this.translator.trans("system.failure"),
            });
        })
        .always(() => {
            $("#SelectEnquiryFormOverlay").addClass("hidden");
        });
    }

    /**
     * Unhide hidden items like city and rooms.
     *
     * @return void
     */
    unhideHiddenItems()
    {
        $(".hidden-item").removeClass("hidden");
    }

    /**
     * Uncheck any previously checked rooms.
     *
     * @return void
     */
    uncheckPreviousChecked()
    {
        $("input.room:checked").prop("checked", false).trigger("change");
    }

    /**
     * Select enquiry city to given cityId.
     *
     * @param  Integer  cityId
     * @return void
     */
    selectCity(cityId)
    {
        $("#City").val(cityId).trigger("change");
    }

    /**
     * Select enquiry rooms from given rooms list.
     *
     * @param  Integer  cityId
     * @return void
     */
    selectRooms(rooms)
    {
        for (let room of rooms) {
            $(`#${room}`).prop("checked", true).trigger("change");
        }
    }

    /**
     * Initialize selected rooms count.
     *
     * @return void
     */
    initializeRoomsCount()
    {
        let that = this,
            roomsCountNode = $("#RoomsCount");

        $(".room").on("change", function () {
            if ($(this).is(":checked")) {
                roomsCountNode.html(
                    that.parseRoomCount(roomsCountNode, true) + 1
                );
            } else {
                roomsCountNode.html(
                    that.parseRoomCount(roomsCountNode) - 1
                );
            }
        });
    }

    /**
     * Parse rooms count into integer.
     *
     * @param  jQuery  roomsCountNode
     * @param  Boolean  isChecked
     * @return void
     */
    parseRoomCount(roomsCountNode, isChecked = false)
    {
        let count = parseInt(roomsCountNode.html());
        if (isChecked) {
            return isNaN(count) ? ($("input.room:checked").length - 1) : count;
        }
        return isNaN(count) ? ($("input.room:checked").length + 1) : count;
    }
}

$(document).ready(() => (new SelectEnquiry()).execute());
