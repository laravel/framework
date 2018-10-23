var Project = ProjectId, User = UserId, Room = RoomId, Item = ItemId;

import Application from "foundation/Application";
import ReplyIdeaRequest from "./ReplyIdeaRequest";
import CreateIdeaRequest from "./CreateIdeaRequest";
import { applyKeyboardNavigationPatch } from "support/Select2Patches";

/**
 * Vendor class for controlling the password tab.
 *
 * Initializes essentials like form validations for password page.
 */
class CreateIdea extends Application
{
    /**
     * Initialize expense page components
     *
     * @return void
     */
    init()
    {
        // Initialize create idea form validations
        let createRequest = new CreateIdeaRequest;
        createRequest.init(this.notifier, this.translator, this.formRequisites, this);
        $(createRequest.formSelector).validate(createRequest.getOptions());

        // Initialize select2 on select inputs
        this.initializeCustomerSelect2(createRequest.formSelector);
        $("#Customer").val(User).trigger("select2:select");
        $("#Customer").val(User).trigger("change");
    
        this.initializeDesignItemsSelect2(createRequest.formSelector);

        // On form reset, clear form customized fields
        this.clearCustomInputsOnFormReset(createRequest.formSelector);
        
        this.initializeStickyheader();

        //on click and escape button hide the overlay notification
        this.clearNotificationOverlay();
    }

    /**
     * 
     * @param {type} formSelector
     * @returns {undefined}
     */
    initializeStickyheader()
    {
        $(window).on('scroll', function (event) {
            if ($('#ReplyListHeader').length > 0) {
                let ScreenHeight = $(this).scrollTop();
                let divHeight = $('#ReplyListHeader').offset().top;
                if (ScreenHeight >= divHeight) {
                    $("#ReplyListStickyHeader").removeClass('hidden').width($("#ReplyListHeader").width());
                } else {
                    $("#ReplyListStickyHeader").addClass('hidden');
                }
            }

        });
    }

    /**
     * Initialize customer select2.
     *
     * @param  Selector  formSelector
     * @return void
     */
    initializeCustomerSelect2(formSelector)
    {
        let customerNode = $("#Customer");
        customerNode.select2({
            placeholder: "Select a Customer"
        }).on("select2:select", () => {
            if (customerNode.val().length > 0 && customerNode.valid()) {
                this.hideHiddenIdeaElements();
                this.getCustomerProjects(
                    customerNode.data("apiEndPoint").replace("customerId", customerNode.val()),
                    formSelector
                );
            }
        });
        applyKeyboardNavigationPatch(customerNode);
        customerNode.trigger('focus');
    }

    /**
     * Initialize design items select2.
     *
     * @param  Selector  formSelector
     * @return void
     */
    initializeDesignItemsSelect2(formSelector)
    {
        let designItemNode = $("#DesignItem");

        designItemNode.select2({
            placeholder: "Select a Item"
        }).on('select2:select', () => {
            let customerNode = $("#Customer"),
                projectNode = $("#Project"),
                roomNode = $("#Room");

            if (customerNode.val().length > 0 && customerNode.valid() &&
                projectNode.val().length > 0 && projectNode.valid() &&
                roomNode.val().length > 0 && roomNode.valid() &&
                designItemNode.val().length > 0 && designItemNode.valid()) {
                this.getAnyPreviousIdeas(designItemNode.data("apiEndPoint"), formSelector);
            }
        });
        applyKeyboardNavigationPatch(designItemNode);
    }

    /**
     * Get customer projects from the given api endpoint.
     *
     * @param  String  apiEndPoint
     * @param  Selector  formSelector
     * @return void
     */
    getCustomerProjects(apiEndPoint, formSelector)
    {
        this.formRequisites.prepareFormForAjax(formSelector + "Overlay", "Fetching Customer Projects");
        $.ajax({
            url: apiEndPoint,
            type: 'GET',
            dataType: 'json',
        })
        .done((response) => {
            if (response.length !== 0) {
                return this.prepareProjectsSelectOptions(response, formSelector);
            }
            let createProjectHref = $("#Customer").data("projectsNotFound");
            this.notifier.notify(formSelector + "NotificationArea", {
                status: "success",
                message: {
                    type: "info",
                    body: this.translator.trans("system.not_found_manager", [
                        {
                            "replace": "attribute",
                            "with": "Projects"
                        },
                        {
                            "replace": "link",
                            "with": "<a href='" + createProjectHref + "'>create</a>"
                        }
                    ])
                }
            });
        })
        .fail(() => {
            this.notifier.notify(formSelector + "NotificationArea", {
                status: "error",
                message: this.translator.trans('system.failure')
            });
        })
        .always(() => {
            this.formRequisites.hideOverlay(formSelector + "Overlay");
        });
    }

    /**
     * Prepare the project select options.
     *
     * @param  Array  projects
     * @param  Selector  formSelector
     * @return void
     */
    prepareProjectsSelectOptions(projects, formSelector)
    {
        let projectNode = $("#Project"),
            closestGridNode = projectNode.closest(".col-md-3");
        if (projectNode.hasClass("select2-hidden-accessible")) {
            projectNode.select2("destroy").html("");
        }

        let selectDefault = new Option("Select", "");
        projectNode.append($(selectDefault));
        for (let project of projects) {
            let option = new Option(project.name, project.id);
            projectNode.append($(option).data("quickEstimationId", project.quickEstimationId));
        }
            
        this.initializeProjectSelect2(projectNode, formSelector);

        if (closestGridNode.hasClass("hidden")) {
            closestGridNode.removeClass("hidden");
        }
        
        $("#Project").val(Project).trigger("select2:select");
        $("#Project").val(Project).trigger("change");
    }

    /**
     * Initialize select2 for the project element.
     *
     * @param  jQuery  projectNode
     * @param  Selector  formSelector
     * @return void
     */
    initializeProjectSelect2(projectNode, formSelector)
    {
       var closestGridNode = projectNode.closest(".col-md-3");
        projectNode.select2({
            placeholder: "Select a Project"
        }).on('select2:select', () => {
            if (projectNode.val().length > 0 && projectNode.valid()) {
                if (! $("#Idea").parents("div.row").hasClass("hidden")) {
                    $("ul#PreviousIdeas, #DesignVersionInfo").addClass('hidden');
                }
                this.getProjectRooms(
                    projectNode.data("apiEndPoint"),
                    formSelector,
                    projectNode
                );
            }
        });
        applyKeyboardNavigationPatch(projectNode);
        
        if (closestGridNode.hasClass("hidden")) {
            closestGridNode.removeClass("hidden");
        }
    }

    /**
     * Get project rooms from the given api endpoint.
     *
     * @param  String  apiEndPoint
     * @param  Selector  formSelector
     * @param  jQuery  projectNode
     * @return void
     */
    getProjectRooms(apiEndPoint, formSelector, projectNode)
    {
        this.formRequisites.prepareFormForAjax(formSelector + "Overlay", "Fetching Project Rooms");
        let formData = new FormData;
        formData.append("QuickEstimationId", projectNode.children("option[value=" + projectNode.val() + "]").data("quickEstimationId"));
        formData.append("CustomerId", $("#Customer").val());
        $.ajax({
            url: apiEndPoint,
            type: "POST",
            dataType: "json",
            data: formData,
            processData: false,
            contentType: false
        })
        .done((response) => {
            this.prepareRoomsSelectOptions(response, formSelector);
        })
        .fail(() => {
            this.notifier.notify(formSelector + "NotificationArea", {
                status: "error",
                message: this.translator.trans('system.failure')
            });
        })
        .always(() => {
            this.formRequisites.hideOverlay(formSelector + "Overlay");
        });
    }

    /**
     * Prepare select options for rooms element.
     *
     * @param  Array  rooms
     * @param  Selector  formSelector
     * @return void
     */
    prepareRoomsSelectOptions(rooms, formSelector)
    {
        let roomNode = $("#Room"),
            closestGridNode = roomNode.closest('.col-md-3');
        if (roomNode.hasClass("select2-hidden-accessible")) {
            roomNode.select2('destroy').html("");
        }

        let selectDefault = new Option("Select", "");
        roomNode.append($(selectDefault));
        for (let room of rooms) {
            let option = new Option(room.name, room.id);
            roomNode.append($(option));
        }
            
        this.initializeRoomSelect2(roomNode, formSelector);

        if (closestGridNode.hasClass('hidden')) {
            closestGridNode.removeClass('hidden');
        }

        $("#Room").val(Room).trigger("change");       
        $("#DesignItem").val(Item).trigger("select2:select");
        $("#DesignItem").val(Item).trigger("change");
        
        this.showIdeaFormElements();
    }

    /**
     * Initialize select2 for rooms.
     *
     * @param  jQuery  roomNode
     * @param  Selector  formSelector
     * @return void
     */
    initializeRoomSelect2(roomNode, formSelector)
    {
        roomNode.select2({
            placeholder: "Select a Room"
        }).on('select2:select', () => {
            let customerNode = $("#Customer"),
                projectNode = $("#Project"),
                roomNode = $("#Room"),
                designItemNode = $("#DesignItem");

            if (customerNode.val().length > 0 && customerNode.valid() &&
                projectNode.val().length > 0 && projectNode.valid() &&
                roomNode.val().length > 0 && roomNode.valid() &&
                designItemNode.val().length > 0) {
                this.getAnyPreviousIdeas(designItemNode.data("apiEndPoint"), formSelector);
            }
        });
        applyKeyboardNavigationPatch(roomNode);
    }

    /**
     * Show idea form elements.
     *
     * @return void
     */
    showIdeaFormElements()
    {
        $("#DesignItem").parents("div.col-md-3").removeClass("hidden");
        this.showAttachments();
        this.showIdeaInput();
        this.showSubmitButtons();
    }

    /**
     * Get any previous ideas.
     *
     * @param  String  apiEndPoint
     * @param  Selector  formSelector
     * @return void
     */
    getAnyPreviousIdeas(apiEndPoint, formSelector)
    {
        this.formRequisites.prepareFormForAjax(formSelector + "Overlay", "Fetching Previous Ideas");
        let formData = new FormData;
        formData.append("CustomerId", $("#Customer").val());
        formData.append("Project", $("#Project").val());
        formData.append("Room", $("#Room").val());
        formData.append("DesignItem", $("#DesignItem").val());
        $.ajax({
            url: apiEndPoint,
            type: 'POST',
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false
        })
        .done((response) => {
            
            if (response.isDesignVersioned) {
                $("#DesignVersionHref").attr("href", response.designHref);
                $(".hidden-versioned-element").addClass("hidden");
                $("#DesignVersionInfo").removeClass("hidden");
                $("#PreviousIdeas").html(response.ideasHtml).removeClass("hidden");
            } 
            else {
                $("#DesignVersionInfo").addClass("hidden");
                $(".hidden-versioned-element").removeClass("hidden");
                $("#PreviousIdeas").html(response.ideasHtml).removeClass("hidden");
            }
            
            if (response.ideasHtml.length > 0) {

                this.initializeAttachmentsGallery();
                this.activateReloadEvent();
                this.intializeReplyFormValidator();
                this.activateDeleteIdea();
                this.initializeFileUpload();
                this.initializeAttachementAction();
                this.activateStatusUpdate();
                this.deleteIdea();
            }
        })
        .fail(() => {
            this.notifier.notify(formSelector + "NotificationArea", {
                status: "error",
                message: this.translator.trans('system.failure')
            });
        })
        .always(() => {
            this.formRequisites.hideOverlay(formSelector + "Overlay");
        });
    }
    
    /**
     * Initialize all form on reply
     * 
     * @returns void
     */
    intializeReplyFormValidator()
    {
        let that = this;
        $("form.ReplyIdeaForm").each(function () {
            let replyRequest = new ReplyIdeaRequest,
                form = $(this);
            replyRequest.init("#" + form.attr('id'), that.notifier, that.translator, that.formRequisites, that);
            $(replyRequest.formSelector).validate(replyRequest.getOptions());
        });
    }

    /**
     * Activate reply modal.
     *
     * @return void
     */
    activateReplyModal()
    {
        $("span.reply").on("click", function (event) {
            event.preventDefault();
            $("#ReplyModal").modal("show");
            $("#CommentId").val($(this).data("commentId"));
        });
        $("#ReplyModal").on("hide.bs.modal", function (event) {
            $("#CommentId").val("");
        });
        $("#Status").select2({
            placeholder: "Select",
            minimumResultsForSearch: Infinity
        }).on("select2:select", function () {
            if ($("#Status").val().length > 0) {
                $("#Status").valid();
            }
        });
    }

    /**
     * Show attachments element.
     *
     * @return void
     */
    showAttachments()
    {
        let attachmentsNode = $("#Attachments"),
            closestGridNode = attachmentsNode.closest(".col-md-3");

        if (closestGridNode.hasClass('hidden')) {
            closestGridNode.removeClass('hidden');
        }
    }

    /**
     * Show idea element.
     *
     * @return void
     */
    showIdeaInput()
    {
        let ideaNode = $("#Idea"),
            closestGridNode = ideaNode.closest("div.row");

        if (closestGridNode.hasClass('hidden')) {
            closestGridNode.removeClass('hidden');
        }
    }

    /**
     * Show submit action elements.
     *
     * @return void
     */
    showSubmitButtons()
    {
        let submitNode = $("#CreateIdeaFormSubmit"),
            closestGridNode = submitNode.closest("div.row");

        if (closestGridNode.hasClass('hidden')) {
            closestGridNode.removeClass('hidden');
        }
    }

    /**
     * Initialize attachments gallery in the previous ideas container.
     *
     * @return void
     */
    initializeAttachmentsGallery()
    {
        $("div.attachments-container").magnificPopup({
            delegate: "img",
            type: "image"
        });
    }

    /**
     * Activate reload event for previous ideas.
     *
     * @return void
     */
    activateReloadEvent()
    {
        $("#ReloadPreviousIdeas").removeClass('hidden').off().on("click", function (event) {
            event.preventDefault();
            $("#Room").trigger("select2:select");
        });
    }

    /**
     * Clear customized fields on form reset.
     *
     * @param  Selector  ideaFormSelector
     * @param  Selector  replyFormSelector
     * @return void
     */
    clearCustomInputsOnFormReset(ideaFormSelector, replyFormSelector)
    {
        $(ideaFormSelector).on('reset', () => {
            setTimeout(() => {
                $("#Customer, #Project, #Room, #DesignItem").trigger('change');
                $("#PreviousIdeas, .hidden-idea-element").addClass("hidden");
                $("#ReloadPreviousIdeas").addClass('hidden').off("click");
            }, 0);
        });

        $(replyFormSelector).on("reset", function () {
            setTimeout(() => {
                $("#Status").trigger('change');
            }, 0);
        });
    }

    /**
     * Hide hidden idea elements.
     *
     * @return void
     */
    hideHiddenIdeaElements()
    {
       $(".hidden-idea-element, ul#PreviousIdeas, #DesignVersionInfo").addClass('hidden');
    }

    /**
     * Delete idea confirmation modal pop-up.
     *
     * @return void
     */
    activateDeleteIdea()
    {
        $("a.delete-idea").off().on('click', function (event) {
            event.preventDefault();
            $(".modal-confirmation").attr("href", $(this).attr("href"));
            $("#ConfirmationModal").modal('show');
        });
    }
    
    /**
     * Delete idea remote call.
     *
     * @return void
     */
    deleteIdea()
    {
        $(".modal-confirmation").off().on('click', function (event) {
            event.preventDefault();
            
            $("#ConfirmationModal").modal('hide');
            console.log("here" + $(this).attr("href"));
            let formData = new FormData;
            formData.append("_method", "DELETE");
            var that = new CreateIdea();
            that.initializePageOverlay("Deleting the idea...");

            $.ajax({
                url: $(this).attr("href"),
                type: "POST",
                dataType: "json",
                data: formData,
                contentType: false,
                processData: false
            })
            .done(function (response) {
                that.notifySuccessOverlay(response.message.body);
            })
            .fail(function () {
                that.notifyErrorOverlay();
            });
        });
    }
    /**
     * Activate status update.
     *
     * @return void
     */
    activateStatusUpdate()
    {
        let that = this;
        $(".status-update").off().on('change', function (event) {
            event.preventDefault();
            
            that.initializePageOverlay("Updating Status...");
            let Status = $(this).val();
            let RefUrl = $(this).attr("reference");
            let value = RefUrl.split("/");
            value.pop();
            let Url = value.join("/")+"/"+Status;
            $.ajax({
                url: Url,
                type: "get",
                dataType: "json",
                contentType: false,
                processData: false
            })
            .done(function (response) {
                that.notifySuccessOverlay(response.message.body);
            })
            .fail(function () {
                that.notifyErrorOverlay();
            });
        });
    }
    /**
     * Initialize File upload input.
     *
     * @return void
     */
    initializeFileUpload(){
        $(".btn-attachmentupload").off().on('click', function (event) {
            let inputField = $(this).closest('form').find('input.fileupload');
            inputField.trigger("click");
        });
    }
    
    /**
     * Initialize File upload input.
     *
     * @return void
     */
    initializeAttachementAction(){
        $("input.fileupload").off().on('change', function (event) {
            let attachmentBTN = $(this).closest('form').find('.btn-attachmentupload');
            let attachmentPin = attachmentBTN.find('.fa-paperclip');
            let filesLength = $(this)[0].files.length;
            let tooltipTitle = " File Attached";
            if (filesLength > 0) {
                if (filesLength > 1) {
                    tooltipTitle = " Files Attached";
                }
                attachmentBTN.tooltip('hide')
                        .attr('data-original-title', filesLength + tooltipTitle)
                        .tooltip('fixTitle');
                attachmentPin.addClass("text-blue");
            } else {
                attachmentBTN.tooltip('hide')
                        .attr('data-original-title', 'Add Attachments')
                        .tooltip('fixTitle');
                attachmentPin.removeClass("text-blue");
            }
        });
    }

    initializePageOverlay(message)
    {
        let pageOverlayNode = $("#PageOverlay");
        pageOverlayNode.find("div.loader-text").html(message);
        pageOverlayNode.find(".page-overlay-close").addClass("hidden");
        pageOverlayNode.removeClass("hidden");
    }

    clearPageOverlay()
    {
        $("#PageOverlay").addClass('hidden');
    }
    
    clearNotificationOverlay()
    {
        $(document).keyup(function (event) {
            if (event.key === "Escape") {
                $("#NotificationOverlay").addClass('hidden');
            }
        });

        $("#notif-overlay-close, #NotificationOverlay").off().on("click", function (event) {
            event.preventDefault();
            $("#NotificationOverlay").addClass("hidden");
        });
    }

    notifySuccessOverlay(message)
    {
        $("#PageOverlay").addClass('hidden');
        $("#NotificationOverlay").removeClass('hidden');
        $("#NotificationOverlay").find("div.notification-message").html(message).parent().find("div.notification-icon").addClass("fa fa-check-circle text-success overlay-notification-icon");
        $("#ReloadPreviousIdeas").trigger("click");
    }

    notifyErrorOverlay()
    {
        $("#PageOverlay").addClass('hidden');
        $("#NotificationOverlay").removeClass('hidden');
        $("#NotificationOverlay").find("div.notification-message").html(this.translator.trans("system.failure")).parent().find("div.notification-icon").addClass("fa fa-exclamation-circle text-danger overlay-notification-icon");
    }
}

$(document).ready(() => (new CreateIdea).init());
