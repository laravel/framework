//Global variables
var CommentForm, Updateform, ThreeDDesignUpdate;

//include needed packages
require('../../bootstrap');
var jquery = require('jquery');
require('select2');
require('magnific-popup');

//Register Vue components
import UserInformation from '../../components/design/userInformation';
import SiteInformation from '../../components/design/siteInformation';
import FileNamesList from '../../components/design/filenameslist';
import CommentSection from '../../components/design/commentsection';
import AttachmentSection from '../../components/design/attachmentsection';
import OverlayNotification from '../../components/overlayNotification';

// Magnific popup call back function
import PopUpObj from "./PopUpObj";
let PopUpObjAttr = new PopUpObj;
        
var VueInstance = new Vue({
    el: "#UpdateDesignBlock",
    data: {
        Offset: 10,
        AttachmentFields: Fields,
        Designer: Role,
        Customer: Customer,
        DesignDetails: DesignData,
        CommentsData: Comments,
        CustomerDetails: UserDetails,
        SiteDetails: CustomerSiteDetails,
        Attachments: Object.values(DesignAttachments),
        PageUrl: Baseurl,
        AttachmentCount: LatestVersion,
        ReplyStatuses: ReplyStatuses,
        DesignStatuses: DesignStatus,
        Loader: true,
        LoaderMessage:"",
        ChangeRequestblock: false,
        UpdateRevisedDesignBox: true,
        UpdateCommentBox: true,
        AddCommentBox: false,
        ThreeDDesignUpdateBox: true,
        AllThreeDFiles: [],
        AllTwoDFiles: [],
        AllTwoDWidthoutDFiles: [],
        AllRefImages: [],
        CommentAttachments: [],
        UpdateThreeDFiles: [],
        PickedOption: "",
        RadioButtonLabel: "Add a New Design (New Version)",
        FormOverLay:true,
        NotificationIcon: "",
        NotificationMessage:"",
        DeleteCommentId: null
    },
    
    created(){
                
        this.imageNPdfPopup();
        if(this.Designer && this.DesignDetails.Status === 1){
            this.ChangeRequestblock = true;
        }
        if(this.Designer && this.DesignDetails.Status === 2){
            this.RadioButtonLabel = "Upload Revised Design";
        }
        if(this.Designer && this.DesignDetails.Status === 2){
            this.AddCommentBox = true;
        }
        if(this.DesignDetails.design_attachment.length === 0){
            this.UpdateRevisedDesignBox = false;
            this.UpdateCommentBox = false;
        }
    },
    
    computed:{
        
        ThreeDVerArray: function()
        {
            var VerArray = [];
            if(this.AttachmentCount !== 1){
                for(var i=0; i<this.AttachmentCount-1; i++){
                    VerArray[i]= i+1;
                }
            }
            return VerArray;
        },
        
        ThreeDUpdate: function(){
            if(this.AttachmentCount){
                return false;
            }
            return true;
        }
    },
    //Vue components
    components: {
        'user-information': UserInformation,
        'site-information': SiteInformation,
        'filenames-list': FileNamesList,
        'comment-section': CommentSection,
        'attachment-section': AttachmentSection,
        'overlay-notification': OverlayNotification
    },
    
    methods:{
        //initialize image and pdf popup.
        imageNPdfPopup()
        {
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
        
        //Show Latest Attachments button action.
        showLatestAttachments()
        {
            this.Attachments =  _.slice(this.Attachments, 0,1);
            this.imageNPdfPopup();
        },
        
        //Show Latest Comments button action.
        showLatestComments()
        {
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
            this.LoaderMessage = "Fetching Data...";
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
                SelfRef.PopulateNotifications({
                    status: "error",
                    alertMessage: AlertData["10077"]
                });
            })
            .then(() => {
               this.Loader = true;
            });
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
            this.LoaderMessage = "Deleting Comment...";
            let SelfRef = this;
            
            let Url = this.PageUrl+"designs/comment/"+this.DeleteCommentId;
            
            let formData = new FormData;
            formData.append("_method", "DELETE");
            
            axios.post(Url, formData)
            .then(function (response) {
                SelfRef.PopulateNotifications(response.data);
                if(response.data.status==="success"){
                   SelfRef.updateValues(response.data, "CommentForm");
                }
            })
            .catch(function (error) {
                SelfRef.PopulateNotifications({
                    status: "error",
                    alertMessage: AlertData["10077"]
                });
            })
            .then(() => {
                this.Loader = true;
                this.DeleteCommentId = null;
            });
            
        },
        
        onChnageStatus(StatusData){
            let Url = this.PageUrl+"designs/commentstatus/"+StatusData.CommentId+"/"+StatusData.Status;
            this.Loader = false;
            this.LoaderMessage = "Updating Status...";
            let SelfRef = this;
            
            axios.get(Url)
            .then(function (response) {
                SelfRef.PopulateNotifications(response.data);
                if(response.data.status === "success"){
                   SelfRef.updateValues(response.data, "CommentForm");
                }
            })
            .catch(function (error) {
                SelfRef.PopulateNotifications({
                    status: "error",
                    alertMessage: AlertData["10077"]
                });
            })
            .then(() => {
                this.Loader = true;
            });
        },
        
        //Assign attachment data.
        assignData(Data)
        {
            this.Attachments = Object.values(Data.Attachments).reverse();
            this.imageNPdfPopup();
        },
        
        //Assign comment data.
        assignComment(Data)
        {
            this.CommentsData.Comments = _.concat(this.CommentsData.Comments,Data.Comments);
            this.CommentsData.CommentsCount = Data.Count;
            this.Offset = this.Offset + 10;
            this.imageNPdfPopup();
            replyForm();
        },
        
        //On Change event file upload
        onFileUploadChange(elementEvent, totalCopies)
        {
            let id = elementEvent.target.id;
             if (!$("#"+id).valid()) {
                    
                return false;
             }

            var files = elementEvent.target.files;

            let fileArray = Array.from(files);

            this.storeFiles(fileArray, totalCopies);
            this.clearFileUpload(id)
        },
        
        //Stores files into array variable, if file doesn't exists in array
        storeFiles(filesArray, totalFiles)
        {
            for (let j = 0; j < filesArray.length; j++) {

                if (this.isFileNameExistsInObject(filesArray[j].name, totalFiles)) {

                    totalFiles.push(filesArray[j]);
                }
            }
        },
        
       //Checks whether file name exists in object array or not
        isFileNameExistsInObject(name, list)
        {
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
        deleteFiles(elementObject)
        {
            if (elementObject.UploadElement === "2DWithoutDimensions") {

                var totalFiles = this.AllTwoDWidthoutDFiles;

                totalFiles.splice(elementObject.FileIndex, 1);
                
            } else if (elementObject.UploadElement === "2DWidDimensions") {

                var totalFiles = this.AllTwoDFiles;

                totalFiles.splice(elementObject.FileIndex, 1);
                
            } else if (elementObject.UploadElement === "3D") {

                var totalFiles = this.AllThreeDFiles;

                totalFiles.splice(elementObject.FileIndex, 1);
                
            }else if (elementObject.UploadElement === "RefImages") {

                var totalFiles = this.AllRefImages;

                totalFiles.splice(elementObject.FileIndex, 1);
                
            }else if (elementObject.UploadElement === "CommentAttachments") {

                var totalFiles = this.CommentAttachments;

                totalFiles.splice(elementObject.FileIndex, 1);
                
            }else if (elementObject.UploadElement === "ThreeDDesign") {

                var totalFiles = this.UpdateThreeDFiles;

                totalFiles.splice(elementObject.FileIndex, 1);
            }
            if (totalFiles.length === 0) {

                this.clearFileUpload(elementObject.UploadElement);
            }
        },

        //Clears files from upload element
        clearFileUpload(elementId)
        {
            //Clear Upload Site uploads field, if user deletes all selected files from list
            this.$nextTick(function () {
                var $el = $('#'+elementId);
                $el.wrap('<form>').closest('form').get(0).reset();
                $el.unwrap();
            });
        },
        
        //Update form event call back function
        updateValues(response, formname)
        {
            if(formname === "Updateform"){
                this.updateAttachments(response.response);
            }
            if(formname === "CommentForm" || formname === "ReplyCommentForm"){
                this.CommentsData = response.response;
                if(this.CommentsData.CommentsCount>10){
                    this.Offset = 10;
                }
            }
            if(formname === "ThreeDDesignUpdate"){
                this.updateThreeDDesigns(response.response);
            }
            jquery("#" + formname).trigger('reset');
            replyForm();
            this.imageNPdfPopup();
        },
        
        //Update Three dimension design values
        updateThreeDDesigns(response)
        {
            this.Attachments = Object.values(response.DesignAttachments).reverse();
            this.AttachmentCount = response.LatestVersion;
        },
        
        //Update attachments values
        updateAttachments(response)
        {
            this.Attachments = Object.values(response.DesignAttachments).reverse();
            this.AttachmentCount = response.LatestVersion;
            this.CommentsData = response.Comments;
            this.DesignDetails.Status = response.DesignStatus;
            if(response.UpdatedBy === "NotManager" && response.DesignStatus !==3 ){
                this.ChangeRequestblock = true;
                this.AddCommentBox = false;
            }
        },
        
        cleartUpdateFormData()
        {
            this.AllThreeDFiles = [];
            this.AllTwoDFiles = [];
            this.AllTwoDWidthoutDFiles = [];
            this.AllRefImages = [];
        },
        
        clearCommentFormData()
        {
            this.CommentAttachments = [];
        },
        
        clear3DFormData()
        {
            this.UpdateThreeDFiles = [];
        },
        
        hideAllBlocks()
        {
            this.UpdateCommentBox = true;
            this.UpdateRevisedDesignBox = true;
            this.ThreeDDesignUpdateBox = true;
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
        },
        
        /**
        * PopulateNotifications - function to populate the notifications of the form.
        * 
        * @param  Response
        * @return  No return(void)
        */
        PopulateNotifications(Response) {
            this.NotificationMessage = Response.alertMessage;
            this.NotificationIcon = Response.status;
            this.FormOverLay = false;
            if (Response.status === "success") {
                this.NotificationIcon = "check-circle";
            } else if (Response.status === 'error') {
                this.NotificationIcon = "ban";
            }
       }
    }
});

/*
 * Update Design form validation
 */
var InitializeValidator = function () {
    Updateform = $("#Updateform").validate({
        ignore: [],
        onkeyup: function (element, event) {
            if (this.invalid.hasOwnProperty(element.name)) {
                $(element).valid();
            }
        },
        errorClass: "help-block text-danger",
        errorElement: "span",
        highlight: function (element, errorClass) {
            if(element.id === "2DWithoutDimensions" || element.id === "2DWidDimensions" || element.id === "3D" || element.id === "RefImages") {
                
                $(element).parent().parent('.form-group').find('i').addClass('text-danger'); 
            }else {
                
                $(element).closest('.form-group').addClass("has-error");
            }
        },
        unhighlight: function (element, errorClass) {
           if(element.id === "2DWithoutDimensions" || element.id === "2DWidDimensions" || element.id === "3D" || element.id === "RefImages") {
                
                $(element).parent().parent('.form-group').find('i').removeClass('text-danger'); 
                
            } else {
                $(element).closest('.form-group').removeClass("has-error");
            }
        },
        errorPlacement: function (error, element) {

            error.appendTo($(element).parent());

        },
        rules: {
            "RefImages[]": {
                checkMultipleFilesExtensions: true,
                checkPerFileSizeInMultipleFiles: true
            },
            "2DWithoutDimensions[]": {
                required: function () {
                    if (VueInstance.PickedOption === "ReplyWidExp") {
                        return false;
                    }
                    if(VueInstance.AllTwoDWidthoutDFiles.length === 0){
                        return true;
                    }
                    return false;
                },
                checkMultipleUploadFilesExtensions: true,
                checkPerFileSizeInMultipleFiles: true
            },
            "2DWidDimensions[]": {
                required: function () {
                    if (VueInstance.PickedOption === "ReplyWidExp" ) {
                        return false;
                    }
                    if(VueInstance.AllTwoDFiles.length === 0){
                        return true;
                    }
                    return false;
                },
                checkMultipleUploadFilesExtensions: true,
                checkPerFileSizeInMultipleFiles: true
            },
            "3D[]": {
                checkMultipleUploadFilesExtensions: true,
                checkPerFileSizeInMultipleFiles: true
            },
             AttachmentComment: {
                required: true,
                validateText: true
            }
        },
        messages: {
            "2DWithoutDimensions[]": {
                required: "Please upload a file."
            },
            "2DWidDimensions[]": {
                required: "Please upload a file."
            },
            "3D[]": {
                required: "Please upload a file."
            },
             AttachmentComment: {
                required: "Comment is required.",
                minlength: "Minimum three characters."
            }
        },
        submitHandler: function (form) {
            let formData = new FormData(form);
            
            //Append Reference Images
            for (var i = 0; i < VueInstance.AllRefImages.length; i++) {

                formData.append("RefImages[]", VueInstance.AllRefImages[i]);
            }
            
            //Append attachments
            if (VueInstance.PickedOption !== "ReplyWidExp") {
                
                for (var i = 0; i < VueInstance.AllTwoDFiles.length; i++) {

                    formData.append("2DWidDimensions[]", VueInstance.AllTwoDFiles[i]);
                }

                for (var i = 0; i < VueInstance.AllTwoDWidthoutDFiles.length; i++) {

                    formData.append("2DWithoutDimensions[]", VueInstance.AllTwoDWidthoutDFiles[i]);
                }
            
                //Append 3D attachments
                for (var i = 0; i < VueInstance.AllThreeDFiles.length; i++) {

                    formData.append("3D[]", VueInstance.AllThreeDFiles[i]);
                }
            }
            formSubmit(formData , "Updateform", Baseurl+"designs/update", Updateform, "Updating...");
        }
    });
    
}

/*
 * Comment Form Validation
 */
var commentFormValidator = function () {
    //Define form validation for comment form submit
    CommentForm = $("#CommentForm").validate({
        ignore: [],
        onkeyup: function (element, event) {
            if (this.invalid.hasOwnProperty(element.name)) {
                $(element).valid();
            }
        },
        errorClass: "help-block text-danger",
        errorElement: "span",
        highlight: function (element, errorClass) {
            if(element.id === "CommentAttachments") {
                $(element).parent('.form-group').find('i').addClass('text-danger'); 
            }else {
                $(element).closest('.form-group').addClass("has-error");
            }
        },
        unhighlight: function (element, errorClass) {
            if(element.id === "CommentAttachments") {
                $(element).parent().parent('.form-group').find('i').removeClass('text-danger'); 
            }else {
                $(element).closest('.form-group').removeClass("has-error");
            }
        },
        errorPlacement: function (error, element) {

            error.appendTo($(element).parent());

        },
        rules: {
            CommentText: {
                required: true,
                validateText: true
            },
            "CommentAttachments[]": {
                checkMultipleFilesExtensions: true,
                checkPerFileSizeInMultipleFiles: true
            }
        },
        messages: {
            CommentText: {
                required: "Comment is required."
            }
        },
        submitHandler: function (form) {
            let formData = new FormData(form);
            for (var i = 0; i < VueInstance.CommentAttachments.length; i++) {

                formData.append("CommentAttachments[]", VueInstance.CommentAttachments[i]);
            }
            formSubmit(formData, "CommentForm", Baseurl+"designs/updatecomment", CommentForm, "Saving Data...");
        }
    });
}

/*
 * Three Dimension Design Update form validation. 
 */
var UpdateThreeDFormValidator = function () {
    //Define form validation for comment form submit
    ThreeDDesignUpdate = $("#ThreeDDesignUpdate").validate({
        ignore: [],
        onkeyup: function (element, event) {
            if (this.invalid.hasOwnProperty(element.name)) {
                $(element).valid();
            }
        },
        errorClass: "help-block text-danger",
        errorElement: "span",
        highlight: function (element, errorClass) {
            if(element.id === "ThreeDDesign") {
                
                $(element).parent('.form-group').find('i').addClass('text-danger'); 
                
            } else {
                $(element).closest('.form-group').addClass("has-error");
            }
        },
        unhighlight: function (element, errorClass) {
            if(element.id === "ThreeDDesign") {
                
                $(element).parent('.form-group').find('i').removeClass('text-danger'); 
                
            } else {
                $(element).closest('.form-group').removeClass("has-error");
            }

        },
        errorPlacement: function (error, element) {

            error.appendTo($(element).parent());

        },
        rules: {
            "ThreeDDesign[]": {
                required: function () {
                    if(VueInstance.UpdateThreeDFiles.length === 0 && VueInstance.PickedOption === "Update3DDesign"){
                        return true;
                    }
                    return false;
                },
                checkMultipleUploadFilesExtensions: true,
                checkPerFileSizeInMultipleFiles: true
            }
        },
        messages: {
            ThreeDDesign: {
                required: "Please upload a file."
            }
        },
        submitHandler: function (form) {
            let formData = new FormData(form);
            for (var i = 0; i < VueInstance.UpdateThreeDFiles.length; i++) {

                formData.append("ThreeDDesign[]", VueInstance.UpdateThreeDFiles[i]);
            }
            formSubmit(formData, "ThreeDDesignUpdate", Baseurl+"designs/updatethreeddesign", ThreeDDesignUpdate, "Updating...");
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
                    },
                    Status:{
                        required: true
                    }
                },
                messages: {
                    ReplyText: {
                        required: "Reply can't be blank."
                    },
                    "ReplyAttachments[]": {
                        checkPerFileSizeInMultipleFiles: "Max Upload file size is 2MB per file."
                    },
                    Status:{
                        required: "Status can't be blank."
                    }
                },
                submitHandler: function (form, event) {
                    event.preventDefault();
                    VueInstance.Loader = false;
                    VueInstance.LoaderMessage = "Replying...";
                    let formData = new FormData(form);
                    var ReplyForm = this;

                    $.ajax({
                        url: Baseurl+"designs/replycomment",
                        type: 'POST',
                        dataType: 'json',
                        contentType: false,
                        processData: false,
                        data: formData
                    })
                    .done(function (response) {
                        VueInstance.PopulateNotifications(response);
                        if(response.status=="success"){
                           VueInstance.updateValues(response, "ReplyCommentForm");
                        }
                    })
                    .fail(function (jqXHR) {
                        if (jqXHR.status === 422) {
                           var responsedata = JSON.parse(jqXHR.responseText);
                           populateFormErrors(responsedata.data.errors, ReplyForm);
                        } else if(jqXHR.status === 413){
                           VueInstance.PopulateNotifications({
                               status:"warning",
                               alertMessage: "Total Max upload file size for above three file upload is 10MB. Check files size and try again."
                           });
                        } else {
                           VueInstance.PopulateNotifications({
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
   /*
    * Calling Form Validators
    */
    InitializeValidator();
    commentFormValidator();
    UpdateThreeDFormValidator();
    replyForm();
    
    $("input[type=radio][name='UpdateType']").on('change', function (event) {
        event.preventDefault();
        VueInstance.hideAllBlocks();
        var type = VueInstance.PickedOption;
        if(type === "ReplyWidExp"){
            VueInstance.UpdateCommentBox = false;
            VueInstance.UpdateRevisedDesignBox = true;
            $("#Update-Description-Label").html("Explanation <span class='text-danger'>*</span>");
        }
        if(type === "UpdateRevDesig"){
            VueInstance.UpdateCommentBox = false;
            VueInstance.UpdateRevisedDesignBox = false;
            $("#Update-Description-Label").html("Update Comment <span class='text-danger'>*</span>");
        }
        if(type === "Update3DDesign"){
            VueInstance.ThreeDDesignUpdateBox = false;
        }
    });
    
    //Design dropdown selection on change
    jquery("#Designs").select2({
    }).on('change', function () {
        if ($(this).val() !== "") {
            window.location = '/designs/' + $('#Designs option:selected').val().replace(/\"/g, "") + '/edit';
        }
    });   
    
    /*
     * Form reset event for all forms
     */
    jquery("#Updateform").on('reset', function (event) {
        VueInstance.cleartUpdateFormData();
        Updateform.resetForm();
    });
    
    jquery("#CommentForm").on('reset', function (event) {
        VueInstance.clearCommentFormData();
        CommentForm.resetForm();
    });
    
    jquery("#ThreeDDesignUpdate").on('reset', function (event) {
        VueInstance.clear3DFormData();
        ThreeDDesignUpdate.resetForm();
    });
    
    $(document).keyup(function(event) {
        if (event.key == "Escape") {
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
 * Function to post the form by ajax.
 * 
 * @return  No return(void)
 */
function formSubmit (formData, FormName, postUrl, FormVariable, LoaderMessage) 
{    
    var AttachmentType;
    AttachmentType = $('div.tab-content div.active').attr('id');
    $(".alert").addClass('hidden');
    $("#CommentSubmit").trigger('blur');
    VueInstance.Loader = false;
    VueInstance.LoaderMessage = LoaderMessage;
    
    formData.append('DesignId', $('#DesignId').val());
    formData.append('CustomerId', $('#CustomerId').val());
    formData.append('ShortCode', $('#ShortCode').val());
    formData.append('EntityTypeName', $('#EntityTypeName').val());
    $.ajax({
        url: postUrl,
        type: 'POST',
        dataType: 'json',
        contentType: false,
        processData: false,
        data: formData
    })
    .done(function (response) {
        if(response.status == "success"){
            VueInstance.updateValues(response, FormName);
        }
        VueInstance.PopulateNotifications(response);
    })
    .fail(function (jqXHR) {
        if (jqXHR.status === 422) {
            var responsedata = JSON.parse(jqXHR.responseText);
            populateFormErrors(responsedata.data.errors, FormVariable);
        }
        else if (jqXHR.status === 413) {
            PopulateWarning({
                status: "warning",
                alertMessage: "Max upload file size allowed 10MB. Check files size and try again."
            });
        }
        else if (jqXHR.status === 403) {
            VueInstance.PopulateNotifications({
                status: "error",
                alertMessage: "Access denied you don't have permission for this request."
            });
        }else {
            VueInstance.PopulateNotifications({
                status: "error",
                alertMessage: AlertData["10077"]
            });
        }
    })
    .always(function () {
        VueInstance.Loader = true;
    });
}

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
        if (elementName === "CommentAttachments" || elementName === "3D" || elementName === "2DWidDimensions" || elementName === "2DWithoutDimensions" || elementName === "RefImages" || elementName === "ReplyAttachments" || elementName === "ThreeDDesign") {
            errorObject[elementName + "[]"] = errors[elementName];
        } else {
            errorObject[elementName] = errors[elementName];
        }
        formValidator.showErrors(errorObject);
    }
}
