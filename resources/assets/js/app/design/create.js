/*
 * Global variables
 */
var addDesignFormValidator;

require('../../bootstrap');
var jquery = require('jquery');
require('select2');


//Register Vue components
import FileNamesList from '../../components/design/filenameslist';
import UserInformation from '../../components/design/userInformation';
import OverlayNotification from '../../components/overlayNotification';

//Vue Instance
var VueInstance = new Vue({
    el: "#CreateDesignBlock",
    data: {
        TemplateUrl: url,
        UpdateDesignUrl: designLink,
        TemplateFrom: from,
        TemplateTitle: title,
        TemplateSubject: subject,
        TemplateTeam: team,
        Projects: "",
        Rooms: "",
        DisplayDesignName: "",
        DesignName: "",
        DesignerNotes: "",
        DesignAttachmentFields: Fields,
        AllThreeDFiles: [],
        AllTwoDFiles: [],
        AllTwoDWidthoutDFiles: [],
        AllRefImages: [],
        CustomerDetails: null,
        DesignFormOverlay: true,
        OverLayMessage: "",
        FormOverLay: true,
        NotificationIcon: "",
        NotificationMessage:""
    },
    
    //Vue components
    components: {
       'user-information': UserInformation,
       'filenames-list': FileNamesList,
       'overlay-notification': OverlayNotification
    },
    
    computed:{
        CustomerName(){
            if(this.CustomerDetails){
                return this.CustomerDetails.CustomerName;
            }else{
                return "";
            }
        }
    },
    
    methods:{
        
        showEmailTemplatePopUP(){
            $("#EmailTemplate").modal("show");
        },
        
        /*
         * Remote call to fetch project and customer details
         */
        fetchData(Url, Element){
            this.DesignFormOverlay = false;
            this.OverLayMessage = "Fetching Data...";
            let SelfRef = this;
            axios.get(Url)
            .then(function (response) {
                SelfRef.assignData(response.data, Element);
            })
            .catch(function (error) {
               SelfRef.onFail(error);
            })
            .then(() => {
               this.DesignFormOverlay = true;
            });
        },
        
        /*
         * Assign remote call values.
         */
        assignData(Data, Element){

            if(Element === "Projects"){
                this.Projects = Data;
            }
            if(Element === "Rooms"){
                this.Rooms = Data.rooms;
                this.CustomerDetails = Data.UserDetails;
                this.DesignName = Data.ProjectShortCode;
                this.setDesignShortCode();
            }
            initializeDynamicSelect2();
        },
        
        /*
         * Make shortcode
         */
        setDesignShortCode (){
            if ($("#Projects").val()) {
                if($("#Rooms").val()){
                    var roomCodes = "";
                    roomCodes =  "Design for " + $("#Rooms option:selected").attr('ShortCode');
                    
                    if($("#RoomItem").val()){
                        var itemCodes = "";
                        itemCodes = " - " + $("#RoomItem option:selected").attr('ShortCode');
                        var newDesignCode = this.DesignName + ": " + roomCodes + itemCodes;
                        this.DisplayDesignName = newDesignCode;
                        validateDesign();
                    }else{
                        this.DisplayDesignName = this.DesignName + ": " + roomCodes;
                    }
                }else {
                    this.DisplayDesignName = this.DesignName;
                }
            }
        },
        
        /*
         * Remote call to validate design.
         */
        designRemoteValidation(Url){
            
            var formData = new FormData();
            formData.append("DesignHeading", this.DisplayDesignName);
            
            this.DesignFormOverlay = false;
            this.OverLayMessage = "Validating Design...";
            let SelfRef = this;
            
            axios.post(Url, formData)
            .then(function (response) {
                if(response.data.Id){
                    redirectPage(response.data.Id);
                }
            })
            .catch(function (error) {
               SelfRef.onFail(error);
            })
            .then(() => {
               this.DesignFormOverlay = true;
            });
        },
        
        onFail(error) {
            if(typeof(error.response.status) !== 'undefined'){
            if (error.response.status === 422) {
                let formErrors = error.response.data.data;
                populateFormErrors(formErrors.errors, createProjectValidator);
            } else {
                this.populateNotifications({
                    status: "error",
                    message: AlertData["10077"]
                });
            }}
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

            if (elementObject.UploadElement === "TwoDWithoutDFile") {

                var totalFiles = this.AllTwoDWidthoutDFiles;

                totalFiles.splice(elementObject.FileIndex, 1);
                
            } else if (elementObject.UploadElement === "TwoDFile") {

                var totalFiles = this.AllTwoDFiles;

                totalFiles.splice(elementObject.FileIndex, 1);
                
            } else if (elementObject.UploadElement === "ThreeDDesign") {

                var totalFiles = this.AllThreeDFiles;

                totalFiles.splice(elementObject.FileIndex, 1);
            }
             else if (elementObject.UploadElement === "RefImages") {

                var totalFiles = this.AllRefImages;

                totalFiles.splice(elementObject.FileIndex, 1);
            }

            if (totalFiles.length === 0) {

                this.clearFileUpload(elementObject.UploadElement);
            }
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
        
        //Clears form data
        clearData(){
            this.Projects = this.Rooms = ''; 
            this.DesignName = this.DisplayDesignName = '';
            this.CustomerDetails = null;
            this.AllThreeDFiles = [];
            this.AllTwoDFiles = [];
            this.AllTwoDWidthoutDFiles = [];
            this.AllRefImages = []
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
         * Populates notifications of the form.
         *
         * @param  object  response
         * @return  No
         */
        populateNotifications(response)
        {
            this.NotificationMessage = response.message;
            this.NotificationIcon = response.status;
            this.FormOverLay = false;
            if (response.status === "success") {     
                this.NotificationIcon = "check-circle";
                $("#AddDesignForm").trigger('reset');

            } else if (response.status === 'error') {
                this.NotificationIcon = "ban";
            }
        }
    }
});

/**
 * Function intializes Validator.
 * 
 * @return  No
 */
var InitializeValidator = function () {
    addDesignFormValidator = $("#AddDesignForm").validate({
        ignore: [],
        onkeyup: function (element, event) {
            if (this.invalid.hasOwnProperty(element.name)) {
                $(element).valid();
            }
        },
        errorClass: "help-block text-danger",
        errorElement: "span",
        highlight: function (element, errorClass) {
            if (element.id === "Customer" || element.id === "Projects" || element.id === "RoomItem" || element.id === "Rooms") {
                
                $(element).next('span.select2').find(".select2-selection").addClass("select2-selection-error").closest('.form-group').addClass('has-error');
            
            }else if(element.id === "TwoDWithoutDFile" || element.id === "TwoDFile" || element.id === "ThreeDDesign"  || element.id === "RefImages") {
                
                $(element).parent().parent('.form-group').find('i').addClass('text-danger'); 
            }else {
                
                $(element).closest('.form-group').addClass("has-error");
            }
        },
        unhighlight: function (element, errorClass) {
            if (element.id === "Customer" || element.id === "Projects" || element.id === "RoomItem" || element.id === "Rooms") {
                
                $(element).next('span.select2').find(".select2-selection").removeClass("select2-selection-error").closest('.form-group').removeClass('has-error');
            
            }else if(element.id === "TwoDWithoutDFile" || element.id === "TwoDFile" || element.id === "ThreeDDesign" || element.id === "RefImages") {
                
                $(element).parent().parent('.form-group').find('i').removeClass('text-danger'); 
                
            } else {
                $(element).closest('.form-group').removeClass("has-error");
            }
        },
        errorPlacement: function (error, element) {
            if (element.id === "Customer" || element.id === "Projects" || element.id === "Rooms" || element.id === "RoomItem") {
                error.insertAfter($(element).next("span.select2"));
            
            }else {
                error.appendTo($(element).parent());
            }
        },
        rules: {
            Customer: {
                required: true
            },
            Projects: {
                required: true
            },
            Rooms: {
                required: true
            },
            RoomItem: {
                required: true
            },
            DesignHeading: {
                required: true
            },
            DesignType: {
                required: true
            },
            "TwoDFile[]": {
                required: function () {
                    if (VueInstance.AllTwoDFiles.length === 0) {
                        return true;
                    }
                    return false;
                },
                checkMultipleUploadFilesExtensions: true,
                checkPerFileSizeInMultipleFiles: true
            },
            "TwoDWithoutDFile[]": {
                required: function () {
                    if (VueInstance.AllTwoDWidthoutDFiles.length === 0) {
                        return true;
                    }
                    return false;
                },
                checkMultipleUploadFilesExtensions: true,
                checkPerFileSizeInMultipleFiles: true
            },
            "ThreeDDesign[]": {
                checkMultipleUploadFilesExtensions: true,
                checkPerFileSizeInMultipleFiles: true
            },
            "RefImages[]": {
                checkMultipleFilesExtensions: true,
                checkPerFileSizeInMultipleFiles: true
            },
            Notes: {
                validateText: true
            }
        },
        messages: {
            Customer: {
                required: "Customer can't be blank."
            },
            Projects: {
                required: "Project can't be blank."
            },
            Rooms: {
                required: "Rooms can't be blank."
            },
            RoomItem: {
                required: "Items can't be blank."
            },
            DesignHeading: {
                required: "Design Name can't be blank.",
                remote: "Design already exists."
            },
            "TwoDFile[]": {
                required: "Please upload a file."
            },
            "TwoDWithoutDFile[]": {
                required: "Please upload a file."
            },
            "ThreeDDesign[]": {
                required: "Please upload a file."
            }
        },
        submitHandler: function (form) {
            
            VueInstance.DesignFormOverlay = false;
            VueInstance.OverLayMessage = "Saving...";
            $(".alert").addClass('hidden');
            let formData = new FormData(form);
            
            //Append Reference Images
            for (var i = 0; i < VueInstance.AllRefImages.length; i++) {

                formData.append("RefImages[]", VueInstance.AllRefImages[i]);
            }
            
            //Append 2D attachments
            for (var i = 0; i < VueInstance.AllTwoDFiles.length; i++) {

                formData.append("TwoDFile[]", VueInstance.AllTwoDFiles[i]);
            }
            for (var i = 0; i < VueInstance.AllTwoDWidthoutDFiles.length; i++) {

                formData.append("TwoDWithoutDFile[]", VueInstance.AllTwoDWidthoutDFiles[i]);
            }
            
            //Append 3D attachments
            for (var i = 0; i < VueInstance.AllThreeDFiles.length; i++) {

                formData.append("ThreeDDesign[]", VueInstance.AllThreeDFiles[i]);
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
            })
            .fail(function (jqXHR) {
                if (jqXHR.status === 422) {
                    let response = JSON.parse(jqXHR.responseText);
                    populateFormErrors(response.data.errors, addDesignFormValidator);
                } else if (jqXHR.status === 413) {
                    VueInstance.populateNotifications({
                        status: "warning",
                        message: "Max upload file size allowed 10MB. Check files size and try again."
                    });
                } else {
                    VueInstance.populateNotifications({
                        status: "error",
                        message: AlertData["10077"]
                    });
                }
            })
            .always(function () {
                 VueInstance.DesignFormOverlay = true;
            });
        }
    });
};

/*
 * Redirect to edit page if the design exists.
 */
var redirectPage = function(Id){
   
    VueInstance.OverLayMessage = "Fetching Data Please wait...";
    setTimeout(function(){return VueInstance.DesignFormOverlay = false}, 250);
    setTimeout(function(){return window.location.href = "/designs/"+ Id +"/edit"}, 2000);
};

/*
 * Initialize dynamic Select2 dropdowns.
 */
var initializeDynamicSelect2 = function(){
    
    jquery('#Rooms').select2({placeholder: "Select a Room"});
    jquery('#Projects').select2({placeholder: "Select a Project"});
};

/*
 * Validating design.
 */
var validateDesign = function(){
    
    if($("#Projects").val() && $("#Rooms").val() && $("#RoomItem").val()){
        var url = $("#DesignHeading").data("entity-existence-url");
        VueInstance.designRemoteValidation(url);      
    }
};

$(document).ready(function () {
    
    /*
     * Initialize Select2 dropdowns.
     */
    initializeDynamicSelect2();
    jquery('#Customer').select2({placeholder: "Select a Customer"});
    jquery('#RoomItem').select2({placeholder: "Select a Item"});
    
    // Initialize form validator.
    InitializeValidator(); 
    
    jquery("#Customer").on('change', function(event){
        let CustomerId = jquery("#Customer").val();
        if(this.value.length > 0){
            VueInstance.clearData();
            VueInstance.fetchData('/getprojects/'+ CustomerId, "Projects");
        }
    });
    
    jquery("#Projects").on('change', function(event){
        let ProjectId = jquery("#Projects").val();
        if(this.value.length > 0){
            VueInstance.fetchData('/getrooms/'+ ProjectId, "Rooms");
            VueInstance.setDesignShortCode();
        }
    });
    
    jquery("#Rooms, #RoomItem").on('change', function(event){
        if(this.value.length > 0){
            VueInstance.setDesignShortCode();
            $("#DesignHeading").valid();   
        }
    });

    jquery("#Projects, #Rooms, #RoomItem, #Customer").on('change', function(event){
        $(this).valid();
    });
    
    jquery("#AddDesignForm").on('reset', function (event) {
        jquery("#Projects, #Rooms, #RoomItem, #Customer").val("").trigger('change');
        VueInstance.clearData();
        addDesignFormValidator.resetForm();
    });
    
    $(document).keyup(function(event) {
        if (event.key === "Escape") {
            VueInstance.clearOverLayMessage();
        }
    });
});

/**
 * Populate Backend validation errors of the form.
 * @return  No
 */
function populateFormErrors(errors, formValidator)
{
    for (let elementName in errors) {
        let errorObject = {},
                previousValue = $("#" + elementName).data("previousValue") ? $("#" + elementName).data("previousValue") : {};
        previousValue.valid = false;
        previousValue.message = errors[elementName][0];
        $("#" + elementName).data("previousValue", previousValue);
        let Condition = elementName === "TwoDFile" || elementName === "TwoDWithoutDFile" || elementName === "RefImages" || elementName === "ThreeDDesign";
        if (Condition) {
            errorObject[elementName + "[]"] = errors[elementName][0];
        } else {
            errorObject[elementName] = errors[elementName][0];
        }
        formValidator.showErrors(errorObject);
    }
}
