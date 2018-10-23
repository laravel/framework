
/*
 * Global variables
 */
let VueInstance, CreateSiteMeasurementValidator;

//Include needed packages
require('../../bootstrap');
require('select2');

//Resolve Jquery library conflict
var jquery = require('jquery');

//Register Vue components
//Shows SM Scanned Copy upload
import SiteScannedcopyUpload from '../../components/siteMeasurement/smScannedCopies';
//Shows Manual Checklist Copy upload
import SiteChecklistUpload from '../../components/siteMeasurement/smChecklistCopies';
//Shows Site Photos And Videos upload
import SiteAttachmentsUpload from '../../components/siteMeasurement/createSiteAttachments';
//Success/Failure message overlay
import OverlayNotification from '../../components/overlayNotification';

$(document).ready(function () {

    VueInstance = new Vue({
        
        //Vue root element
        el: '#CreateSiteMeasurementPage',

        //Vue data variables
        data: {
            
            projects: Projects,
            
            showFetchDataOverlay: false,
            
            showOverlay: false,
                    
            totalSitePhotos: [],

            shouldRenderFiles: false,
            
            enquiryProject: null, // Stores enquiry info with Customer details
            
            shouldRenderScannedCopies: false,
            
            totalScannedCopies: [],
            
            shouldRenderChecklistCopies: false,
            
            totalChecklistCopies:[],
            
            isRolesExists: isRolesExists, // Checks for reviewer and approver roles availability
            
            FormOverLay: true,
  
            NotificationIcon: "",
            
            NotificationMessage:""
        },
        
        //Vue components
        components: {
            
            'site-scannedcopy-upload': SiteScannedcopyUpload,
            'site-checklist-upload': SiteChecklistUpload,
            'site-attachments-upload': SiteAttachmentsUpload,
            'overlay-notification': OverlayNotification
        },
        
        mounted() {
                            
            //Projects Select2 initialization
            this.initializeSelect2();
        },
        
        //Vue methods
        methods: {
            
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
            
            //On Change event file upload
            onFileUploadChange(elementEvent, totalCopies) {
                
                let id = elementEvent.target.id;
                
                //Hides files if element is invalid
                if (!$("#"+id).valid()) {
                    
                    if(id === "SiteImages") {
                        
                        this.shouldRenderFiles = false;
                    } else if(id === "SMCopy") {
                        
                        this.shouldRenderScannedCopies = false;
                    } else if(id === "ChecklistCopy") {
                        
                        this.shouldRenderChecklistCopies = false;
                    }
                    return false;
                }
                
                var files = elementEvent.target.files;
                
                let fileArray = Array.from(files);

                let totalUploadedFiles = fileArray.length;

                this.showFiles(id, totalUploadedFiles, totalCopies);
                
                this.storeFiles(fileArray, totalCopies);
            },
            
            //Shows files on upload
            showFiles(id, uploadedFiles, totalStoredFiles) {
                
                if(id === "SiteImages") {
                        
                   this.shouldRenderFiles = (uploadedFiles > 0 || totalStoredFiles.length > 0); 
                } else if(id === "SMCopy") {
                        
                   this.shouldRenderScannedCopies = (uploadedFiles > 0 || totalStoredFiles.length > 0); 
                } else if(id === "ChecklistCopy") {
                    
                    this.shouldRenderChecklistCopies = (uploadedFiles > 0 || totalStoredFiles.length > 0);
                }              
            },
            
            //Deletes uploaded files from list
            deleteFiles(elementObject) {
             
                if (elementObject.UploadElement === "SiteImages") {

                    var totalFiles = this.totalSitePhotos;
            
                    totalFiles.splice(elementObject.FileIndex, 1);

                    this.shouldRenderFiles = totalFiles.length > 0;
                } else if (elementObject.UploadElement === "SMCopy") {

                    var totalFiles = this.totalScannedCopies;
            
                    totalFiles.splice(elementObject.FileIndex, 1);

                    this.shouldRenderScannedCopies = totalFiles.length > 0;
                } else if (elementObject.UploadElement === "ChecklistCopy") {

                    var totalFiles = this.totalChecklistCopies;
            
                    totalFiles.splice(elementObject.FileIndex, 1);

                    this.shouldRenderChecklistCopies = totalFiles.length > 0;
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
            
            //Resets all Vue variables
            resetVueVariables() {

                this.totalSitePhotos = [];

                this.totalScannedCopies = [];

                this.totalChecklistCopies = [];

                this.shouldRenderFiles = false;

                this.shouldRenderScannedCopies = false;

                this.shouldRenderChecklistCopies = false;

                this.enquiryProject = null;
            },
            
            //Select2 initialization 
            initializeSelect2() {
                
                let self = this;
                jquery('#Projects').select2({
                    placeholder: "Select a Project",
                    language: {
                        noResults: function () {
                            return "No projects found";
                        }
                    }
                }).on("change", function (e) {
                    self.onProjectSelect(this, this.value, e);
                }).next("span.select2").css({
                    display: 'block',
                    width: '100%'
                });
            },
            
            //On Project Select             
            onProjectSelect(projectRef, projectId, event) {    

                event.preventDefault();
                let self = this;
                if (projectId.length > 0) {

                    self.showFetchDataOverlay = true;

                    VueInstance.fetchSiteInfo(projectId);

                    $(projectRef).valid();

                    $(projectRef).next('span.select2').find(".select2-selection").removeClass("select2-selection-error").closest('.form-group').removeClass('has-error');
                } else {

                    $("#siteInfo").addClass("hidden");
                }
            },
            
            //Gets Site Information
            fetchSiteInfo(projectId) {
                
                let config = {
                    headers: {'Data-Type': 'json'}
                };
                let self = this;
                axios.get('/getsiteinfo/' + projectId, config)
                .then(function (response) {

                    $("#siteInfo").removeClass("hidden");
                    self.enquiryProject = response.data.data.user;
                    $("#ShortCode").val(response.data.data.shortCode);
                    $("#SiteDetails").html(response.data.data.siteInfo);
                })
                .catch(function (error) {
                    
                    self.populateOverlayMessage({
                        status: "error",
                        message: "It seems enquiry is not exists for selected project."
                    });
                })
                .then(() => {
                    
                    self.showFetchDataOverlay = false;
                });
            },
            
            //Populates Success/Failure messages
            populateOverlayMessage(response) {
                this.NotificationMessage = response.message;
                this.NotificationIcon = response.status;
                this.FormOverLay = false;
                if (response.status === "success") {
                    this.NotificationIcon = "check-circle";
                } else if (response.status === 'error') {
                    this.NotificationIcon = "ban";
                } else if (response.status === 'warning') {
                    this.NotificationIcon = "warning";
                } else if (response.status === 'info') {
                    this.NotificationIcon = "info";
                }
            },
            
            //Clears Success/Failure messages 
            clearOverLayMessage() {
                this.FormOverLay = true;
                this.NotificationMessage = "";
                this.NotificationIcon = "";
            },
            
            //Redirect to Site M Edit page
            refreshPage(response) {
                window.location = '/sitemeasurement/' + response.sitemeasurementId.replace(/\"/g, "") + "/edit";
            },
            
           //Populates backend validation errors  
            populateFormErrors(errors, formValidator) {
                
                for (let elementName in errors) {

                    let errorObject = {},

                    previousValue = $("#" + elementName).data("previousValue") ? $("#" + elementName).data("previousValue") : {};

                    previousValue.valid = false;

                    previousValue.message = errors[elementName][0];

                    $("#" + elementName).data("previousValue", previousValue);

                    let Condition = elementName === "SiteImages" || elementName === "SMCopy" || elementName === "ChecklistCopy";

                    if (elementName.match("SiteImages")) {

                        this.shouldRenderFiles = false;

                        this.totalSitePhotos = [];

                        this.clearFileUpload(elementName);

                        errorObject[elementName + "[]"] = errors[elementName][0];
                    } else if (elementName.match("SMCopy")) {

                        this.shouldRenderScannedCopies = false;

                        this.totalScannedCopies = [];

                        this.clearFileUpload(elementName);

                        errorObject[elementName + "[]"] = errors[elementName][0]; 
                    } else if(elementName.match("ChecklistCopy")) {

                        this.shouldRenderChecklistCopies = false;

                        this.totalChecklistCopies = [];

                        this.clearFileUpload(elementName);

                        errorObject[elementName + "[]"] = errors[elementName][0]; 
                    } else {

                        errorObject[elementName] = errors[elementName][0];
                    }

                    formValidator.showErrors(errorObject);
                }
            },
            
            //Clears form element values
            resetForm() {
                
                jquery("#Projects").val(null).trigger('change');
        
                $("#NotificationArea").addClass('hidden');
                
                $("#SiteDetails").empty();
                
                $("#ShortCode").val("");
                
                VueInstance.resetVueVariables();

                CreateSiteMeasurementValidator.resetForm();
            },
            
            //On Successful form submission 
            onSuccess(success) {
                
                let self = this;
                if (success.status === "success") {
                    
                    this.populateOverlayMessage(success);
                    
                    setTimeout(this.clearOverLayMessage, 3000);
                    
                    // Refresh page after 3.5 seconds 
                    setTimeout(this.refreshPage(success), 3500);
                } else if(success.status === "info") {
                    
                    self.populateOverlayMessage({
                        status: "info",
                        message: success.message
                    }); 
                } else {
                    
                    self.populateOverlayMessage({
                        status: "error",
                        message: AlertData["10077"]
                    }); 
                }
            },
            
            //On failed form submission  
            onFail(error) {
                
                let self = this;
                if (error.status === 403) {
                    
                    self.populateOverlayMessage({
                        status: "error",
                        message: "Access denied!. (One of the reason may be Site Measurement has created for selected Site project.)"
                    });
                } else if (error.status === 422) {
                    
                    let response = JSON.parse(error.responseText);
                    self.populateFormErrors(response.data.errors, CreateSiteMeasurementValidator);
                } else if (error.status === 413) {
                    
                    self.populateOverlayMessage({
                        status: "warning",
                        message: "Total Max upload file size for above file upload is 10MB. Check files size and try again."
                    });
                } else {
                    
                    self.populateOverlayMessage({
                        status: "error",
                        message: AlertData["10077"]
                    });
                }
            }
        }
    });

    //Initialize form validator
    InitializeValidator();

    //Reset form event
    $("#CreateSiteMeasurement").on('reset', function () {

        VueInstance.resetForm();
    });
});

/**
 * Function intializes Validator.
 * 
 * @return  No
 */
var InitializeValidator = function () {
    
    CreateSiteMeasurementValidator = $("#CreateSiteMeasurement").validate({
        
        ignore: [],
        onkeyup: function (element, event) {
            
            if (this.invalid.hasOwnProperty(element.name)) {
                
                $(element).valid();
            }
        },
        errorClass: "help-block text-danger",
        errorElement: "span",
        highlight: function (element, errorClass) {
            
            if (element.id === "Projects") {
                
                $(element).next('span.select2').find(".select2-selection").addClass("select2-selection-error").closest('.form-group').addClass('has-error');
            } else if(element.id === "SiteImages" || element.id === "SMCopy" || element.id === "ChecklistCopy") {
                
                $(element).parent('.form-group').find('i').addClass('text-danger'); 
            } else {
                
                $(element).closest('.form-group').addClass("has-error");
            }
        },
        unhighlight: function (element, errorClass) {
            
            if (element.id === "Projects") {
                
                $(element).next('span.select2').find(".select2-selection").removeClass("select2-selection-error").closest('.form-group').removeClass('has-error');
            } else if(element.id === "SiteImages" || element.id === "SMCopy" || element.id === "ChecklistCopy") {
                
                $(element).parent('.form-group').find('i').removeClass('text-danger'); 
            } else {
                
                $(element).closest('.form-group').removeClass("has-error");
            }
        },
        errorPlacement: function (error, element) {
            
            if (element.id === "Projects") {
                
                error.insertAfter($(element).next("span.select2"));
            } else {
                
                error.appendTo($(element).parent());
            }
        },
        rules: {
            
            Projects: {
                required: true
            },
            Description: {
                CheckConsecutiveSpaces: true,
                maxlength: 255
            },
            "SiteImages[]": {
                required: function () {
                    if (VueInstance.totalSitePhotos.length === 0) {

                        return true;
                    } else {

                        return false;
                    }
                },
                checkMultipleVideoImageExtensions: true,
                checkMultipleFilesSize: true,
                checkPerFileSizeInMultipleFiles: true
            },
            "SMCopy[]": {
                required: function () {
                    if (VueInstance.totalScannedCopies.length === 0) {

                        return true;
                    } else {

                        return false;
                    }
                }, 
                checkMultipleFilesExtensions: true,
                checkMultipleFilesSize: true,
                checkPerFileSizeInMultipleFiles: true
            },
            "ChecklistCopy[]": {
                required: function () {
                    if (VueInstance.totalChecklistCopies.length === 0) {

                        return true;
                    } else {

                        return false;
                    }
                }, 
                checkMultipleFilesExtensions: true,
                checkMultipleFilesSize: true, 
                checkPerFileSizeInMultipleFiles: true
            }
        },
        messages: {
            
            Projects: {
                required: "Please select a Project."
            },
            "SiteImages[]": {
                required: "Please upload a file."
            },
            "SMCopy[]": {
                required: "Please upload a file."
            },
            "ChecklistCopy[]": {
                required: "Please upload a file."
            }
        },
        submitHandler: function (form) {
            
            $("#SiteMeasurementFormSubmit").trigger('blur');
            
            VueInstance.showOverlay = true;
            
            $(".alert").addClass('hidden');
            
            let formData = new FormData(form);
            
            //Append Site M / Scanned / CheckList Images/ Vidoes to a post variable
            for (var i = 0; i < VueInstance.totalSitePhotos.length; i++) {

                formData.append("Files_" + VueInstance.totalSitePhotos[i].name, VueInstance.totalSitePhotos[i]);
            }
            
            for (var i = 0; i < VueInstance.totalScannedCopies.length; i++) {

                formData.append("SMScannnedFiles_" + VueInstance.totalScannedCopies[i].name, VueInstance.totalScannedCopies[i]);
            }
            
            for (var i = 0; i < VueInstance.totalChecklistCopies.length; i++) {

                formData.append("SMChecklistFiles_" + VueInstance.totalChecklistCopies[i].name, VueInstance.totalChecklistCopies[i]);
            }
            
            $.ajax({
                url: form.action,
                type: 'POST',
                dataType: 'json',
                data: formData,
                processData: false,
                contentType: false
                })
            .done(function (response) {
               
                VueInstance.onSuccess(response);
            })
            .fail(function (error) {

                VueInstance.onFail(error);
            })
            .always(function () {
                
                VueInstance.showOverlay = false;
                $("#SiteMeasurementFormSubmit").trigger('blur');
            });
        }
    });
};