//Global variables
var FormValidator;

//include needed packages
require('../../bootstrap');
var jquery = require('jquery');
require('magnific-popup');

//Register Vue components
import SiteInformation from '../../components/design/siteInformation';
import FileNamesList from '../../components/design/filenameslist';
import CommentSection from '../../components/design/commentsection';
import AttachmentSection from '../../components/design/attachmentsection';
import OverlayNotification from '../../components/overlayNotification';

// Magnific popup call back function.
import PopUpObj from "./PopUpObj";
let PopUpObjAttr = new PopUpObj;

//Vue instance    
var VueInstance = new Vue({
    el: "#DesignBlock",
    data: {
        Offset: 10,
        SiteDetails: CustomerSiteDetails,
        DesignDetails: DesignData,
        Customer: Customer,
        CommentsData: Comments,
        Attachments: Object.values(DesignAttachments),
        AttachmentCount: LatestVersion,
        DesignStatuses: DesignStatus,
        PageUrl: Baseurl,
        Loader: true,
        AllUploadFiles: [],
        CommentBox: false,
        SubmitTypeBox: false,
        OverLayMessage: "",
        FormOverLay:true,
        NotificationIcon: "",
        NotificationMessage:"",
        DeleteCommentId: null
    },
    
    created(){
        this.imageNPdfPopup();
        if(this.DesignDetails.Status === 1){
            this.CommentBox = true;
        }
        if(this.DesignDetails.Status === 2 || this.DesignDetails.Status === 3){
            this.SubmitTypeBox = true;
        }
    },
    //Vue components
    components: {
        'site-information': SiteInformation,
        'filenames-list': FileNamesList,
        'comment-section': CommentSection,
        'attachment-section': AttachmentSection,
        'overlay-notification': OverlayNotification
    },
    
    methods:{

        clearCommentFormData(){
            this. AllUploadFiles = [];
        },
        
        //initialize image and pdf popup.
        imageNPdfPopup() {
            this.$nextTick(function () {
                jquery('.design-img').each(function () { // the containers for all your galleries
                    jquery(this).magnificPopup({
                        delegate: 'a.image', // the selector for gallery item
                        type: 'image',
                        gallery: {
                            enabled: true
                        },
                        callbacks: PopUpObjAttr.callBack()
                    });
                });
                jquery('.attachment_container').each(function () { // the containers for all your galleries
                    jquery(this).magnificPopup({
                        delegate: 'a.iframe', // the selector for gallery item
                        type: 'iframe',
                        gallery: {
                            enabled: true
                        },
                        callbacks: PopUpObjAttr.callBack()
                    });
                });
                jquery('.attachments-container').each(function () { // the containers for all your galleries
                    jquery(this).magnificPopup({
                        delegate: 'a.image-comment', // the selector for gallery item
                        type: 'image',
                        gallery: {
                            enabled: true
                        },
                        callbacks: PopUpObjAttr.callBack()
                    });
                });
            });
        },

        //Show Latest Attachments button action
         showLatestAttachments(){
            this.Attachments =  _.slice(this.Attachments, 0,1);
            this.imageNPdfPopup();
        },
        
        //Show Latest Comments button action
        showLatestComments(){
            this.CommentsData.Comments = _.slice(this.CommentsData.Comments, 0, 10);
            this.Offset = 10;
            this.imageNPdfPopup();
            replyForm();
        },
        
        getAttachments(Obj)
        {
            this.fetchData(Obj.Url, Obj.Element);
        },
        
        //Show more attachments and comments remote call function.
        fetchData(Url, Element)
        {
            this.Loader = false;
            this.OverLayMessage = "Fetching Data...";
            let SelfRef = this;
            axios.get(Url)
            .then(function (response) {
                if(Element === "Attachments"){
                    SelfRef.assignData(response.data);
                }
                if(Element === "Comments"){
                    SelfRef.assignComment(response.data);
                }
            })
            .catch(function (error) {
                SelfRef.populateNotifications({
                    status: "error",
                    alertMessage: AlertData["10077"]
                });
            })
            .then(() => {
               this.Loader = true;
            });
        },
        
        //Assign attachment data
        assignData(Data){
            this.Attachments = Object.values(Data.Attachments).reverse();
            this.imageNPdfPopup();
        },
        
        //Assign comment data
        assignComment(Data){
            this.CommentsData.Comments = _.concat(this.CommentsData.Comments,Data.Comments);
            this.CommentsData.CommentsCount = Data.Count;
            this.Offset = this.Offset + 10;
            this.imageNPdfPopup();
            replyForm();
        },
        
        //Update form event call back function
        updateValues(Data){
            this.CommentsData = Data.CommentsView;
            
            if(this.CommentsData.CommentsCount > 10){
                this.Offset = 10;
            }
            if(Data.SubmitType === "Approved"){
                this.SubmitTypeBox = true;
                this.DesignDetails = Data.DesignData;
            }
            if(Data.SubmitType === "ChangeRequest"){
                this.SubmitTypeBox = true;
                this.DesignDetails = Data.DesignData;
            }
            this.AllUploadFiles = [];
            $("#DescriptionLabel").html("Add Comment<span class='text-danger'>*</span>");
            this.imageNPdfPopup();    
            replyForm();
        },
        
        //On Change event file upload
        onFileUploadChange(elementEvent, totalCopies) {

            let id = elementEvent.target.id;

            if (!$("#"+id).valid()) {
                    
                return false;
            }
            var files = elementEvent.target.files;

            let fileArray = Array.from(files);

            this.storeFiles(fileArray, totalCopies);
            this.clearFileUpload(id);
        },
        
        //Stores files into array variable, if file doesn't exists in array
        storeFiles(filesArray, totalFiles) {

            for (let j = 0; j < filesArray.length; j++) {

                if (this.isFileNameExistsInObject(filesArray[j].name, totalFiles)) {

                    totalFiles.push(filesArray[j]);
                }
            }
        },
        
       //Checks whether file name exists in object array or not
        isFileNameExistsInObject(name, list) {

            if (list.length > 0) {

                let files = _.find(list, ["name", name]);

                if (files) {

                    return false;
                }
                return true;
            }
            return true;
        },

        //Deletes uploaded files from list
        deleteFiles(elementObject) {

            if (elementObject.UploadElement === "UploadFiles") {

                var totalFiles = this.AllUploadFiles;

                totalFiles.splice(elementObject.FileIndex, 1);
                
            }
            if (totalFiles.length === 0) {

                this.clearFileUpload(elementObject.UploadElement);
            }
        },
        
        //Confirmation modal popup
        confirmDeleteComment: function(Id){
            this.DeleteCommentId = Id;
            $("#ConfirmationModal").modal("show");
        },
            
        deleteComment()
        {
            $("#ConfirmationModal").modal("hide");
            this.Loader = false;
            this.OverLayMessage = "Deleting Comment...";
            let SelfRef = this;
            
            let Url = this.PageUrl+"designs/comment/"+this.DeleteCommentId;
            
            let formData = new FormData;
            formData.append("_method", "DELETE");
            
            axios.post(Url, formData)
            .then(function (response) {
                SelfRef.populateNotifications(response.data);
                if(response.data.status==="success"){
                   SelfRef.updateComments(response.data);
                }
            })
            .catch(function (error) {
                SelfRef.populateNotifications({
                    status: "error",
                    alertMessage: AlertData["10077"]
                });
            })
            .then(() => {
                this.Loader = true;
                this.DeleteCommentId = null;
            });
            
        },
        
        updateComments(CommentData){
            this.CommentsData = CommentData.response;
            if(this.CommentsData.CommentsCount > 10){
                this.Offset = 10;
            }
            this.imageNPdfPopup();    
            replyForm();
        },
        //Clears files from upload element
        clearFileUpload(elementId) {

            //Clear Upload Site uploads field, if user deletes all selected files from list
            this.$nextTick(function () {

                var $el = $('#'+elementId);
                $el.wrap('<form>').closest('form').get(0).reset();
                $el.unwrap();
            });
        },
        
        //reply form update event call back function
        replyCommentCallBackFunction(response)
        {
            this.CommentsData = response.response;
            this.imageNPdfPopup();
           replyForm();
        },
        
        /**
         * Populates notifications of the form.
         *
         * @param  object  response
         * @return void
         */
        populateNotifications(response)
        {
            this.NotificationMessage = response.alertMessage;
            this.NotificationIcon = response.status;
            this.FormOverLay = false;
            
            if (response.status === "success") {
                this.NotificationIcon = "check-circle";
                this.NotificationMessage = response.alertMessage;

            }else if (response.status === 'error') {
                this.NotificationIcon = "ban";
            }
        },
        
        /**
        * Clears overlay message.
        * @return  No
        */
        clearOverLayMessage()
        {
           this.FormOverLay = true;
           this.NotificationMessage = "";
           this.NotificationIcon = "";
        }
    }
});    

/*
 * Form validation for comment form
 */
function initializeValidator(){
    
    FormValidator = $("#commentForm").validate({
        ignore: [],
        onkeyup: function (element, event) {
            if (this.invalid.hasOwnProperty(element.name)) {
                $(element).valid();
            }
        },
        errorClass: "help-block text-danger",
        errorElement: "span",
        highlight: function (element, errorClass) {
            if(element.id === "UploadFiles") {
                $(element).parent('.form-group').find('i').addClass('text-danger'); 
            }else {
                $(element).closest('.form-group').addClass("has-error");
            }
        },
        unhighlight: function (element, errorClass) {
            if(element.id === "UploadFiles") {
                $(element).parent().parent('.form-group').find('i').removeClass('text-danger'); 
            }else {
                $(element).closest('.form-group').removeClass("has-error");
            }
        },
        errorPlacement: function (error, element) {

            error.appendTo($(element).parent());

        },
        rules: {
            Description: {
                required: function () {
                    if($('[name="SubmitType"]:checked').val() === "DesignApprove" && VueInstance.AllUploadFiles.length !== 0){
                        return true;
                    }
                    if ($('[name="SubmitType"]:checked').val() === "DesignApprove") {
                        return false;
                    }
                    return true;
                }
            },
            "UploadFiles[]": {
                checkMultipleFilesExtensions: true,
                checkMultipleFilesSize: true
            }
        },
        messages: {
            Description: { 
                required:"Comment should not be empty."
            }
        },
        submitHandler: function (form, event) {
            event.preventDefault();
            VueInstance.Loader = false;
            VueInstance.OverLayMessage = "Updating...";
            $("#NotificationArea").children(".alert").addClass('hidden');
            $("#CalloutsArea").children('.callout').addClass('hidden');
            
            let formData = new FormData(form);
            
            //Append Reference Images
            for (var i = 0; i < VueInstance.AllUploadFiles.length; i++) {

                formData.append("UploadFiles[]", VueInstance.AllUploadFiles[i]);
            }
            
            $.ajax({
                url: form.action,
                type: 'POST',
                dataType: 'json',
                contentType: false,
                processData: false,
                data: formData
            })
            .done(function (response) {
                VueInstance.populateNotifications(response);
                if(response.status === "success"){
                    VueInstance.updateValues(response);
                    jquery("#commentForm").trigger('reset');
                }
            })
            .fail(function (jqXHR) {
                if (jqXHR.status === 422) {
                    let response = JSON.parse(jqXHR.responseText);
                    populateFormErrors(response.data.errors, FormValidator);
                
                } else if(jqXHR.status === 413){
                    VueInstance.populateNotifications({
                        status: "warning",
                        alertMessage: "Max upload file size allowed 10MB. Check files size and try again."
                    });
                
                }else {
                    VueInstance.populateNotifications({
                        status: "error",
                        alertMessage: AlertData["10077"]
                    });
                }
            })
            .always(function () {
                VueInstance.Loader = true;
            });
        }
    });    
}

/*
 * Form validator for reply form
 */
var replyCommentFormValidator = function(){
        $('form.ReplyCommentForm').each( function(){
            $(this).validate({
                ignore: [],
                onkeyup: function (element, event) {
                    if (this.invalid.hasOwnProperty(element.name)) {
                        $(element).valid();
                    }
                },
                errorClass: "help-block text-danger",
                errorElement: "span",
                highlight: function (element, errorClass) {
                    if(element.id === "ReplyAttachments"){
                        $(element).closest('.row').find('button.btn-attachmentupload').addClass('text-danger');
                    }else{
                         $(element).closest('.form-group').addClass("has-error");
                    }
                },
                unhighlight: function (element, errorClass) {
                     if(element.id === "ReplyAttachments"){
                        $(element).closest('.row').find('button.btn-attachmentupload').removeClass('text-danger');
                    }else{
                        $(element).closest('.form-group').removeClass("has-error");
                    }
                },
                errorPlacement: function (error, element) {

                    if($(element).attr("id") === "ReplyAttachments"){
                        $(element).closest('.row').find('i.fa-paperclip').removeClass("text-blue");
                        $(element).closest('.row').find('button.btn-attachmentupload').attr('data-original-title', error[0].innerText).trigger("mouseover");
                    }else{
                        error.appendTo($(element).parent());
                    }
                },
                rules: {
                    "ReplyAttachments[]": {
                        checkPerFileSizeInMultipleFiles: true
                        },
                    ReplyText: {
                        required: true,
                        validateText: true
                    }
                },
                messages: {
                    ReplyText: {
                        required: "Reply is required."
                    },
                    "ReplyAttachments[]": {
                        checkPerFileSizeInMultipleFiles: "Max Upload file size is 2MB per file."
                    }
                },
                submitHandler: function (form, event) {
                    event.preventDefault();
                    VueInstance.Loader = false;
                    VueInstance.OverLayMessage = "Replying...";
                    let formData = new FormData(form);
                    var ReplyForm = this;

                    $.ajax({
                        url: ReplyPostUrl,
                        type: 'POST',
                        dataType: 'json',
                        contentType: false,
                        processData: false,
                        data: formData
                    })
                    .done(function (response) {
                        VueInstance.populateNotifications(response);
                        if(response.status === "success"){
                            VueInstance.replyCommentCallBackFunction(response);
                        }
                    })
                    .fail(function (jqXHR) {
                        if (jqXHR.status === 422) {
                            var responsedata = JSON.parse(jqXHR.responseText);
                            populateFormErrors(responsedata.data.errors, ReplyForm);
                        } else if(jqXHR.status === 413){
                            PopulateWarning({
                                status:"warning",
                                alertMessage:"Total Max upload file size for above three file upload is 10MB. Check files size and try again."
                            });
                        } else {
                            VueInstance.populateNotifications({
                                status: "error",
                                alertMessage: AlertData["10077"]
                            });
                        }
                    })
                    .always(function () {
                       VueInstance.Loader = true;
                    });

                }
            });
        });
};

var replySubmit = function(){   
    $(".btn-attachmentupload").off().on('click', function (event) {
        let inputField = $(this).closest('form').find('input.fileupload');
        inputField.trigger("click");
    });
};

 /**
* Initialize File upload input.
*
* @return void
*/
var initializeAttachementAction = function(){
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
};

var replyForm = function (){
    VueInstance.$nextTick(function () {
        replyCommentFormValidator();
    });
    replySubmit();
    initializeAttachementAction();
};

$(document).ready(function () {  
   
    $("input[type=radio][name='SubmitType']").on('change', function (event) {
        event.preventDefault();
        var type = $('[name="SubmitType"]:checked').val();
        $("#comment-box").removeClass('hidden');
        if(type === "ChangeRequest"){
            $("#DescriptionLabel").html("Request for Change Comment <span class='text-danger'>*</span>");
        }
        if(type === "DesignApprove"){
            $("#DescriptionLabel").html("Approve Comment&nbsp;&nbsp;<small>(Optional)</small>");
        }
        $("#Description").val('');
        $("#Description").next('span').empty().parent().removeClass('has-error');
        $("#UploadFileId").val("");
        $("#UploadFileId").removeClass("text-danger").next('span').empty().parent().removeClass('has-error');
    });

    /*
     * Initializing form validator
     */
    initializeValidator();
    replyForm();
    
    $("#commentForm").on('reset', function (event) {
        VueInstance.clearCommentFormData();
        FormValidator.resetForm();
    });
    
    $(document).keyup(function(event) {
        if (event.key === "Escape") {
            VueInstance.clearOverLayMessage();
        }
    });
    
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
});

/**
 * Populates the laravel validator error's.
 * 
 * @return  No return(void)
 */
 function populateFormErrors(errors, formValidator)
{
    for (let elementName in errors) {
        let errorObject = {},
        previousValue = $("#" + elementName).data("previousValue") ? $("#" + elementName).data("previousValue") : {};
        previousValue.valid = false;
        previousValue.message = errors[elementName][0];
        $("#" + elementName).data("previousValue", previousValue);
        if (elementName === "UploadFiles" || elementName === "ReplyAttachments") {
            errorObject[elementName + "[]"] = errors[elementName];
        } else {
            errorObject[elementName] = errors[elementName];
        }
        formValidator.showErrors(errorObject);
    }
}