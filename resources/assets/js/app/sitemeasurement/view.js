
let notificationTimeout = 10000, notificationTimeoutID;

// Include needed packages
require('../../bootstrap');
require('magnific-popup');

// Resolve Jquery library conflict
var jquery = require('jquery');

// Register components
// Shows User primary info...
import UserInformation from '../../components/siteMeasurement/userInformation';
// Shows Site Name, Address...
import SiteInformation from '../../components/siteMeasurement/siteInformation';
// Shows Room Notes in pop up...
import RoomNotes from '../../components/siteMeasurement/roomNotes';
// Success/Failure message overlay...
import OverlayNotification from '../../components/overlayNotification';

// Initialize Vue instance 
new Vue({
    
    // Vue root element
    el: '#ViewSiteMPage',
    
    // Vue data variables
    data: {
        
        // Bind the variables which are defined in Controller
        CdnUrl: S3CdnUrl,
        description: description,
        status: JSON.parse(status), // Parse PHP encoded array into JS object
        enquiryProject: enquiryInfo, // Stores enquiry info with Customer details
        siteAttachments: siteAttachments,
        roomsData: [], // Stores all created room measurements  
        statusLabels: {1: 'info', 2: 'info', 3: 'danger', 4: 'info', 5: 'danger', 6: 'success'},
        FormOverLay: true,
        NotificationIcon: "",
        NotificationMessage: "",
        GetFireSprinklersUrl: GetFireSprinklersUrl,
        GetRoomAcUrl: GetRoomAcUrl,
        DesignItems: DesignItems
    },
    
    // Vue object life cycle hook 
    mounted() {
        
        this.initializeSiteGallery();
        $("#SiteDetails").html(this.enquiryProject.siteInfo);
        // Bind the variable which are defined in controller
        this.roomsData = roomsViewData;
    },
    
    // Vue components
    components: {
        
        'user-information': UserInformation,
        'site-information': SiteInformation,
        'room-notes': RoomNotes,
        'overlay-notification': OverlayNotification
    },
    
    methods: {   
        
        // Get Design items
        getDesignItem(itemId) {
            if (itemId) {
                if (this.DesignItems.length > 0) {
                    let item = _.find(this.DesignItems, ["Id", itemId]);
                    if (!_.isUndefined(item)) {
                        return item.Name+':';
                    }
                }
                return "<small>N/A:</small>";
            }
            return "<small>N/A:</small>";
        },
        // Initialize gallery popup when user clicks on room attachment
        initializeRoomThumbnailsPopup(roomThumbnails) {

            jquery(".attachment-gallery").magnificPopup({
                
                items: roomThumbnails,
                gallery: {
                    enabled: true
                },
                type: 'image',
                callbacks: {
                    
                    open: function() {
                        
                        var mfp = jquery.magnificPopup.instance;
                        var proto = jquery.magnificPopup.proto;
                        var Count = mfp.items.length;
                        if(!mfp.index && Count > 1){
                            mfp.arrowLeft.css('display', 'none');
                        }
                        if(!(mfp.index - (Count-1)) && Count > 1){
                            mfp.arrowRight.css('display', 'none');
                        }
                        // Extend function that moves to next item
                        mfp.next = function() {
                            if(mfp.index < (Count-1)) {
                                proto.next.call(mfp);
                            }
                            if(Count > 1){
                                if(!(mfp.index - (Count-1))){
                                    mfp.arrowRight.css('display', 'none');
                                }
                                if(mfp.index > 0){
                                    mfp.arrowLeft.css('display', 'block');
                                }
                            }
                        };
                        // Extend function that moves back to prev item
                        mfp.prev = function() {
                            if(mfp.index > 0) {
                                proto.prev.call(mfp);
                            }
                            if(Count > 1){
                                if(!mfp.index){
                                    mfp.arrowLeft.css('display', 'none');
                                }
                                if(Count > 1){
                                   mfp.arrowRight.css('display', 'block');
                                }
                            }
                        };
                    }
                }
            });
        },
        
        // Opens Note Category pop up
        openNotesPopup(roomNo) {
            
            $("#ViewNotesModal-"+roomNo).modal({
                show: true
            });
        },
        
        // Initialize Site Pictures pop up
        initializeSiteGallery() {

            jquery("#SiteGallery").magnificPopup({
                delegate: 'a',
                gallery: {
                    enabled: true
                },
                type: 'image',
                callbacks: {
                    open: function () {
                        var mfp = jquery.magnificPopup.instance;
                        var proto = jquery.magnificPopup.proto;
                        var Count = mfp.items.length;
                        if (!mfp.index && Count > 1) {
                            mfp.arrowLeft.css('display', 'none');
                        }
                        if (!(mfp.index - (Count - 1)) && Count > 1) {
                            mfp.arrowRight.css('display', 'none');
                        }
                        // extend function that moves to next item
                        mfp.next = function () {
                            if (mfp.index < (Count - 1)) {
                                proto.next.call(mfp);
                            }
                            if (Count > 1) {
                                if (!(mfp.index - (Count - 1))) {
                                    mfp.arrowRight.css('display', 'none');
                                }
                                if (mfp.index > 0) {
                                    mfp.arrowLeft.css('display', 'block');
                                }
                            }
                        };
                        // extend function that moves back to prev item
                        mfp.prev = function () {
                            if (mfp.index > 0) {
                                proto.prev.call(mfp);
                            }
                            if (Count > 1) {
                                if (!mfp.index) {
                                    mfp.arrowLeft.css('display', 'none');
                                }
                                if (Count > 1) {
                                    mfp.arrowRight.css('display', 'block');
                                }
                            }
                        };
                    }
                }
            });
            
            jquery("#ChecklistGallery").magnificPopup({
                delegate: 'a',
                gallery: {
                    enabled: true
                },
                type: 'image',
                callbacks: {
                    open: function () {
                        var mfp = jquery.magnificPopup.instance;
                        var proto = jquery.magnificPopup.proto;
                        var Count = mfp.items.length;
                        if (!mfp.index && Count > 1) {
                            mfp.arrowLeft.css('display', 'none');
                        }
                        if (!(mfp.index - (Count - 1)) && Count > 1) {
                            mfp.arrowRight.css('display', 'none');
                        }
                        // extend function that moves to next item
                        mfp.next = function () {
                            if (mfp.index < (Count - 1)) {
                                proto.next.call(mfp);
                            }
                            if (Count > 1) {
                                if (!(mfp.index - (Count - 1))) {
                                    mfp.arrowRight.css('display', 'none');
                                }
                                if (mfp.index > 0) {
                                    mfp.arrowLeft.css('display', 'block');
                                }
                            }
                        };
                        // extend function that moves back to prev item
                        mfp.prev = function () {
                            if (mfp.index > 0) {
                                proto.prev.call(mfp);
                            }
                            if (Count > 1) {
                                if (!mfp.index) {
                                    mfp.arrowLeft.css('display', 'none');
                                }
                                if (Count > 1) {
                                    mfp.arrowRight.css('display', 'block');
                                }
                            }
                        };
                    }
                }
            });
            
            jquery("#ScannedGallery").magnificPopup({
                delegate: 'a',
                gallery: {
                    enabled: true
                },
                type: 'image',
                callbacks: {
                    open: function () {
                        var mfp = jquery.magnificPopup.instance;
                        var proto = jquery.magnificPopup.proto;
                        var Count = mfp.items.length;
                        if (!mfp.index && Count > 1) {
                            mfp.arrowLeft.css('display', 'none');
                        }
                        if (!(mfp.index - (Count - 1)) && Count > 1) {
                            mfp.arrowRight.css('display', 'none');
                        }
                        // extend function that moves to next item
                        mfp.next = function () {
                            if (mfp.index < (Count - 1)) {
                                proto.next.call(mfp);
                            }
                            if (Count > 1) {
                                if (!(mfp.index - (Count - 1))) {
                                    mfp.arrowRight.css('display', 'none');
                                }
                                if (mfp.index > 0) {
                                    mfp.arrowLeft.css('display', 'block');
                                }
                            }
                        };
                        // extend function that moves back to prev item
                        mfp.prev = function () {
                            if (mfp.index > 0) {
                                proto.prev.call(mfp);
                            }
                            if (Count > 1) {
                                if (!mfp.index) {
                                    mfp.arrowLeft.css('display', 'none');
                                }
                                if (Count > 1) {
                                    mfp.arrowRight.css('display', 'block');
                                }
                            }
                        };
                    }
                }
            });
        },
        
        //Populates Success/Failure messages
        populateOverlayMessage(response)
        {
            this.NotificationMessage = response.message;
            this.NotificationIcon = response.status;
            this.FormOverLay = false;
            if (response.status === "success") {
                this.NotificationIcon = "check-circle";
            } else if (response.status === 'error') {
                this.NotificationIcon = "ban";
            }
        },

        //Clears Success/Failure messages 
        clearOverLayMessage()
        {
            this.FormOverLay = true;
            this.NotificationMessage = "";
            this.NotificationIcon = "";
        },
        
        //Sends SM review notification email to Reviewer
        onReviewSubmit() {

            let self = this;
            var currentUrl = window.location.href.split("/");
            let postUrl = $("#SubmitForReview").data("review-submit-url");
            $("#UpdateSiteLoader").removeClass('hidden').find(".loader-text").html("Sending Review Notification");
            $.ajax({
                url: postUrl,
                data: {"sitemeasurementid": currentUrl.reverse()[0]},
                type: 'POST',
                dataType: 'json'
            })
            .done(function (response) {

                if(response.status === "success") {
                    
                    //On success hide form and show success message
                    self.populateOverlayMessage(response);
                    $("#SubmitForReview").addClass("hidden");
                    return;
                }
                //On error show error message
                self.populateOverlayMessage({
                    status: "error",
                    message: response.message + ` (One of the reason may be User has not been assigned to project)`
                });
            })
            .fail(function (jqXHR) {

                if (jqXHR.status === 403) {

                    self.populateOverlayMessage({
                        status: "error",
                        message: "Access denied!"
                    });
                } else {

                    self.populateOverlayMessage({
                        status: "error",
                        message: AlertData["10077"]
                    });
                }
            })
            .always(function () {

                $("#UpdateSiteLoader").addClass('hidden');
                $("#SubmitForReview").trigger('blur');    
            });
        },
        
        //Sends SM approval notification email to Approver
        onApproveReview() {
            
            let self = this;
            var currentUrl = window.location.href.split("/");
            let postUrl = $("#SubmitForApproval").data("approve-sitem-url");
            $("#UpdateSiteLoader").removeClass('hidden').find(".loader-text").html("Sending Approval Notification");
            $.ajax({

                url: postUrl,
                data: {"sitemeasurementid": currentUrl.reverse()[0]},
                type: 'POST',
                dataType: 'json'
            })
            .done(function (response) {

                if(response.status === "success") {
                    
                    //On success hide form and show success message
                    self.populateOverlayMessage(response);
                    $("#SubmitForApproval, #RejectReview").addClass("hidden");
                    return;
                }
                //On error show error message
                self.populateOverlayMessage({
                    status: "error",
                    message: response.message + ` (One of the reason may be User has not been assigned to project)`
                });
            })
            .fail(function (jqXHR) {

                if (jqXHR.status === 403) {

                    self.populateOverlayMessage({
                        status: "error",
                        message: "Access denied!"
                    });
                } else {

                    self.populateOverlayMessage({
                        status: "error",
                        message: AlertData["10077"]
                    });
                }
            })
            .always(function () {

                $("#UpdateSiteLoader").addClass('hidden');
                $("#SubmitForApproval").trigger('blur');    
            });
        },
        
        //Sends review reject notification email to Supervisor
        onRejectReview() {

            let self = this;
            var currentUrl = window.location.href.split("/");
            let postUrl = $("#RejectReview").data("review-reject-url");
            $("#UpdateSiteLoader").removeClass('hidden').find(".loader-text").html("Sending Reject Notification");
            $.ajax({

                url: postUrl,
                data: {"sitemeasurementid": currentUrl.reverse()[0]},
                type: 'POST',
                dataType: 'json'
            })
            .done(function (response) {
                
                if(response.status === "success") {
                    
                    //On success hide form and show success message
                    self.populateOverlayMessage(response);
                    $("#SubmitForApproval, #RejectReview").addClass("hidden");
                    return;
                }
                //On error show error message
                self.populateOverlayMessage({
                    status: "error",
                    message: response.message + ` (One of the reason may be User has not been assigned to project)`
                }); 
            })
            .fail(function (jqXHR) {

                if (jqXHR.status === 403) {

                    self.populateOverlayMessage({
                        status: "error",
                        message: "Access denied!"
                    });
                } else {

                    self.populateOverlayMessage({
                        status: "error",
                        message: AlertData["10077"]
                    });
                }
            })
            .always(function () {

                $("#UpdateSiteLoader").addClass('hidden');
                $("#RejectReview").trigger('blur');    
            });
        },
        
        //Sends SM approval reject notification email to Reviewer
        onApprovalAccept() {
            
            let self = this;
            var currentUrl = window.location.href.split("/");
            let postUrl = $("#AcceptApproval").data("approval-accept-url");
            $("#UpdateSiteLoader").removeClass('hidden').find(".loader-text").html("Sending Approval Notification");
            $.ajax({

                url: postUrl,
                data: {"sitemeasurementid": currentUrl.reverse()[0]},
                type: 'POST',
                dataType: 'json'
            })
            .done(function (response) {

                if(response.status === "success") {
                    
                    //On success hide form and show success message
                    self.populateOverlayMessage(response);
                    $("#AcceptApproval, #RejectApproval").addClass("hidden");
                    return;
                }
                //On error show error message
                self.populateNotifications({
                    status: "error",
                    message: response.message + ` (One of the reason may be User has not been assigned to project)`
                });
            })
            .fail(function (jqXHR) {

                if (jqXHR.status === 403) {

                    self.populateOverlayMessage({
                        status: "error",
                        message: "Access denied!"
                    });
                } else {

                    self.populateOverlayMessage({
                        status: "error",
                        message: AlertData["10077"]
                    });
                }
            })
            .always(function () {

                $("#UpdateSiteLoader").addClass('hidden');
                $("#AcceptApproval").trigger('blur');    
            });
        },

        //Sends SM approval accept notification email to Reviewer and Superviser
        onApprovalReject() {
            
            let self = this;
            var currentUrl = window.location.href.split("/");
            let postUrl = $("#RejectApproval").data("approval-reject-url");
            $("#UpdateSiteLoader").removeClass('hidden').find(".loader-text").html("Sending Reject Notification");
            $.ajax({

                url: postUrl,
                data: {"sitemeasurementid": currentUrl.reverse()[0]},
                type: 'POST',
                dataType: 'json'
            })
            .done(function (response) {

               if(response.status === "success") {
                    
                    //On success hide form and show success message
                    self.populateOverlayMessage(response);
                    $("#AcceptApproval, #RejectApproval").addClass("hidden");
                    return;
                }
                //On error show error message
                self.populateNotifications({
                    status: "error",
                    message: response.message + ` (One of the reason may be User has not been assigned to project)`
                });
            })
            .fail(function (jqXHR) {

                if (jqXHR.status === 403) {

                    self.populateOverlayMessage({
                        status: "error",
                        message: "Access denied!"
                    });
                } else {

                    self.populateOverlayMessage({
                        status: "error",
                        message: AlertData["10077"]
                    });
                }
            })
            .always(function () {

                $("#UpdateSiteLoader").addClass('hidden');
                $("#RejectApproval").trigger('blur');    
            });
        }
    }
});