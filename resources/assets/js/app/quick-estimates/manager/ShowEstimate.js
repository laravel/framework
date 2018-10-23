/**
 * ShowEstimate class for showing quick estimate.
 *
 * Initializes essentials like tooltips, popovers and modals.
 */
class ShowEstimate
{
    /**
     * Initialize estimate page components.
     *
     * @return void
     */
    execute()
    {
        // Initialize enquiry address and change rooms popovers.
        this.initializeAddressPopover("#EnquiryInformation");

        // Initialize specifications and ratecards modals.
        this.initializeSpecificationsModal();
        this.initializeRatecardsModal();

        // Register application events handlers.
        this.registerGotoTop();
        this.initializeItemsWidgets()

        // Initialize sticky header for estimation table.
        this.initializeStickyHeader();
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

$(document).ready(() => (new ShowEstimate()).execute());
