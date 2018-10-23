import CustomItems from "./CustomItems";
import FormRequests from "./FormRequests";
import QuickEstimation from "./QuickEstimation";
import SendQuickEstimate from "./SendQuickEstimate";
import Application from "foundation/Application";
import { applyKeyboardNavigationPatch } from "support/Select2Patches";

/**
 * CreateEstimate class for creating quick estimates.
 *
 * Initializes essentials like form validations, tooltips
 * popovers, changes rooms, modals, select2 and resets forms.
 */
class CreateEstimate extends Application
{
    /**
     * Vue instance of the application.
     *
     * @var /Vue|undefined
     */
    application = undefined;

    /**
     * List of all form request instances.
     *
     * @var Object|undefined
     */
    formRequests = undefined;

    /**
     * Initialize estimate page components.
     *
     * @return void
     */
    execute()
    {
        // Initialize application and select2 for enquiry select elements.
        this.initializeApplication();
        $("#Enquiry, #StickyHeaderEnquiry").select2();

        // Initialize enquiry address and change rooms popovers.
        this.initializeAddressPopover("#EnquiryInformation");
        this.initializeAddressPopover("#StickyHeaderEnquiryInformation", "bottom");
        this.initializeChangeRoomsPopover("#ChangeRooms");
        this.initializeChangeRoomsPopover("#StickyHeaderChangeRooms", "bottom");

        // Initialize specifications and ratecards modals.
        this.initializeSpecificationsModal();
        this.initializeRatecardsModal();

        // Register application events handlers.
        this.registerGotoTop();
        this.registerItemsReadyEvent();
        this.registerChangeRoomsModalEvents(this.formRequests.changeRoomsRequest.formSelector);
        this.registerShowNoItemsSelectedEvent();
        // Register destroy widgets event for program efficiency and remove
        // event handlers for the items which we may delete from current view.
        // Simple: Stop listening on the DOM when certain elements are deleted.
        this.registerDestroyItemsWidgetsEvent();

        // Initialize sticky header for estimation table.
        this.initializeStickyHeader();
    }

    /**
     * Initialize estimate application, form requests and custom items.
     *
     * @return void
     */
    initializeApplication()
    {
        // Initialize the vue application.
        let quickEstimation = new QuickEstimation(this.notifier, this.translator, this.formRequisites);
        this.application = quickEstimation.execute();

        // Initialize form requests required for application.
        let createFormRequests = new FormRequests(this.notifier, this.translator, this.formRequisites, this.application);
        this.formRequests = createFormRequests.execute();

        // Initialize custom items functionality for the application.
        let customItems = new CustomItems(this.notifier, this.translator, this.formRequisites, this.application);
        customItems.execute(
            this.formRequests.createCustomItemRequest.formSelector,
            this.formRequests.updateCustomItemRequest.formSelector
        );

        // Initialize sending quick estimate on "qe.items.ready-to-submit" event.
        let sendQuickEstimate = new SendQuickEstimate(this.notifier, this.translator, this.formRequisites, this.application);
        $("#CreateQuickEstimateForm").on("qe.items.ready-to-submit", (event, form, formData) => {
            sendQuickEstimate.execute(form, formData);
        });
    }

    /**
     * Initialize address popover.
     *
     * @param  Selector  selector
     * @param  String  placement
     * @return void
     */
    initializeAddressPopover(selector, placement = "top")
    {
        let node = $(selector);
        node.popover({
            "html": true,
            "placement": placement,
            "content": this.getAddressPopoverContent(node.data("address")),
        }).on({
            mouseout() { node.popover("hide"); },
            mouseover() { node.popover("show"); },
        });
    }

    /**
     * Get address popover html content.
     *
     * @return String
     */
    getAddressPopoverContent(address)
    {
        return `
            <strong>${address.project},</strong>
            <div>${address.builder},</div>
            <div>${address.address}.</div>
        `;
    }

    /**
     * Initialize change rooms popover.
     *
     * @param  Selector  selector
     * @param  String  placement
     * @return void
     */
    initializeChangeRoomsPopover(selector, placement = "top")
    {
        let node = $(selector);
        node.popover({
            "container": "body",
            "placement": placement,
        }).on({
            mouseout() { node.popover("hide"); },
            mouseover() { node.popover("show"); },
            click() { $("#ChangeRoomsModal").modal("show"); },
        });
    }

    /**
     * Initialize specifications modal.
     *
     * @return void
     */
    initializeSpecificationsModal()
    {
        $("a.item-specifications").on("click", (event) => {
            event.preventDefault();
            $("#SpecificationsModal").modal("show");
        });
    }

    /**
     * Initialize ratecards modal.
     *
     * @return void
     */
    initializeRatecardsModal()
    {
        $("a.item-ratecards").on("click", (event) => {
            event.preventDefault();
            $("#RatecardsModal").modal("show");
        });
    }

    /**
     * Register "goto top" of the document.
     *
     * @return void
     */
    registerGotoTop()
    {
        $("#QuickEstimationGoToTop").on("click", () => $(window).scrollTop(0));
    }

    /**
     * Register "qe.items.ready" event.
     *
     * @return void
     */
    registerItemsReadyEvent()
    {
        $("#CreateQuickEstimateTable").on("qe.items.ready", () => this.initializeItemsWidgets());
    }

    /**
     * Register change rooms modal events.
     *
     * @param  Selector  formSelector
     * @return void
     */
    registerChangeRoomsModalEvents(formSelector)
    {
        $("#ChangeRoomsModal").on("hide.bs.modal", () => $(formSelector).trigger("reset"));
    }

    /**
     * Initialize items widgets on "qe.items.ready" event.
     *
     * @return void
     */
    initializeItemsWidgets()
    {
        // Initialize tooltips and reference images popovers.
        this.initializeTooltips();
        this.initializeCommentsTooltips();
        this.initializeReferenceImagesPopover();
        this.initializeNotesTextareaResizing();
    }

    /**
     * Initialize tooltips.
     *
     * @return void
     */
    initializeTooltips()
    {
        let selectors = [
            "span.header-tooltip",
            "span.payment-tooltip",
            "span.notes-tooltip",
        ];

        for (let selector of selectors) {
            this.createTooltips(selector);
        }
    }

    /**
     * Create tooltips for given selector.
     *
     * @param  String  selector
     * @return void
     */
    createTooltips(selector)
    {
        $(selector).tooltip({
            "container": "body",
            "placement": "top",
        });
    }

    /**
     * Create items comments tooltips.
     *
     * @return void
     */
    initializeCommentsTooltips()
    {
        let that = this;
        $("span.comments-tooltip").each(function () {
            let node = $(this);
            node.tooltip({
                "container": "body",
                "html": true,
                "placement": "top",
                "title": that.getCommentTooltipContent(node.data("comments")),
            });
        });
    }

    /**
     * Get comments tooltip html content.
     *
     * @param  Array  comments
     * @return Element
     */
    getCommentTooltipContent(comments)
    {
        // There is no need for ordered list if there is only one comment.
        if (comments.length === 1) {
            return comments[0];
        }
        // Prepare an ordered list of comments and return them.
        let orderedList = document.createElement("ol");
        orderedList.setAttribute("class", "pd-lt-15 pd-tp-5 mr-bt-3 text-left");
        _.forEach(comments, (comment) => {
            let list = document.createElement("li");
            list.appendChild(document.createTextNode(comment));
            orderedList.appendChild(list);
        });

        return orderedList;
    }

    /**
     * Initialize reference images popover.
     *
     * @return void
     */
    initializeReferenceImagesPopover()
    {
        let that = this;
        $("span.reference-images").each(function () {
            let node = $(this);
            node.popover({
                "container": "body",
                "content": that.getReferenceImagePopoverContent(node.data("url")),
                "html": true,
                "placement": "right",
                "trigger": "hover",
            });
        });
    }

    /**
     * Get reference image popover html content.
     *
     * @param  String  url
     * @return String
     */
    getReferenceImagePopoverContent(url)
    {
        return `<img src="${url}" alt="Reference image" class="img-responsive"/>`;
    }

    /**
     * Resize notes textarea on "focus" and restore on "blur".
     *
     * @return void
     */
    initializeNotesTextareaResizing()
    {
        $("textarea.user-notes").on({
            focus() { $(this).attr("rows", 3); },
            blur() { $(this).attr("rows", 1); },
        });
    }

    /**
     * Scroll window to warning element position on "qe.items.not-selected" event.
     *
     * @return void
     */
    registerShowNoItemsSelectedEvent()
    {
        $("#CreateQuickEstimateTable").on("qe.items.not-selected", () => {
            let estimationTableNode = $("form#CreateQuickEstimateForm > div.table-responsive");
            $(window).scrollTop(estimationTableNode.height() - estimationTableNode.offset().top);
        });
    }

    /**
     * Register "qe.items.widgets.destroy" event handler.
     *
     * @return void
     */
    registerDestroyItemsWidgetsEvent()
    {
        $("#CreateQuickEstimateTable").on("qe.items.widgets.destroy", () => {
            // Destroy all tooltips.
            let selectors = [
                "span.header-tooltip",
                "span.payment-tooltip",
                "span.comments-tooltip",
                "span.notes-tooltip",
            ];

            for (let selector of selectors) {
                $(selector).tooltip("destroy");
            }
            // Destroy all image popovers.
            $("span.reference-images").each(function () {
                $(this).popover("destroy");
            });
            // Turn off "focus" and "blur" events on notes.
            $("textarea.user-notes").off("focus blur");
        });
    }

    /**
     * Initialize sticky header for estimation table.
     *
     * @return void
     */
    initializeStickyHeader()
    {
        let estimationStickyHeaderNode = $("#CreateQuickEstimateStickyHeader"),
            estimationTableNode = $("#CreateQuickEstimateTable");

        estimationStickyHeaderNode.css("width", estimationTableNode.css("width"));
        // Show or hide estimation table sticky header on window "scroll" event.
        $(window).on("scroll", () => {
            let scrollTop = $(window).scrollTop();
            // Calculate current scroll position and show sticky header based on that.
            if (scrollTop >= 209 && estimationTableNode.height() + estimationTableNode.offset().top - 450 >= scrollTop) {
                estimationStickyHeaderNode.removeClass("hidden");
            } else {
                estimationStickyHeaderNode.addClass("hidden");
            }
        });
    }
}

$(document).ready(() => (new CreateEstimate()).execute());
