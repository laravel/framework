/*
 * Global variables
 */
let editProjectValidator;

//include needed packages
require('../../bootstrap');
var jquery = require('jquery');
require('select2');

//Register Vue components
import OverlayNotification from '../../components/overlayNotification';

const VueInstance = new Vue({
    el: "#UpdateProject",
    data: {
        quickEstimates: QuickEstimates,
        rolesWithUsers: rolesWithUsers,
        AllStatus: AllStatuses,
        selectedStatus: ProjectStatus,
        CustomerName: "",
        CustomerPhoneNumber: "",
        CustomerEmail: "",
        ProjectName: ProjectName,
        SuperBuildUpArea: "",
        Unit: "",
        SiteProjectName: "",
        SiteCity: "",
        ProjectFormOverlay: true,
        OverLayMessage: "",
        FormOverLay: true,
        NotificationIcon: "",
        NotificationMessage:""
    },
     //Vue components
    components: {
       'overlay-notification': OverlayNotification
    },
    
    computed: {
        filterProjectName: function () {
            return this.ProjectName;
        },
        filterCustomerName: function () {
            return this.CustomerName;
        },
        filterCustomerPhoneNumber: function () {
            return this.CustomerPhoneNumber;
        },
        filterCustomerEmail: function () {
            return this.CustomerEmail;
        },
        filterSuperBuildArea: function () {
            return this.SuperBuildUpArea;
        },
        filterUnit: function () {
            return this.Unit;
        },
        filterSiteProjectName: function () {
            return this.SiteProjectName;
        },
        filterSiteCity: function () {
            return this.SiteCity;
        }
    },
    
    methods: {
        onSuccess(response) {
            this.populateNotifications(response.data);
        },
        onFail(error) {
            if (error.response.status === 422) {
                let formErrors = error.response.data.data;
                populateFormErrors(formErrors.errors, editProjectValidator);
            } else {
                this.populateNotifications({
                    status: "error",
                    message: AlertData["10077"]
                });
            }
        },
        // remote call to get project details from quick estimation id
        fetchSiteDetails(Id) {
            this.ProjectFormOverlay = false;
            this.OverLayMessage = "";
            
            axios.get('/projectmanagement/getsitedetails/'+Id)
            .then(function (response) {
                VueInstance.assignValues(response.data);
            })
            .catch(function (error) {
                VueInstance.onFail(error);
            })
            .then(() => {
                this.ProjectFormOverlay = true;
            });
        },
        // initialize the values from controller
        assignValues(Data){
            this.ProjectName = Data.ProjectName;
            this.CustomerName = Data.CustomerName;
            this.CustomerPhoneNumber = Data.Phone;
            this.CustomerEmail = Data.Email;
            this.SuperBuildUpArea = Data.SuperBuildUpArea + "Sq/ft ,";
            this.Unit = Data.Unit;
            this.SiteProjectName = Data.SiteProjectName + ",";
            this.SiteCity = Data.SiteCity;
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
            if (response.status == "success") {     
                this.NotificationIcon = "check-circle";

            } else if (response.status == 'error') {
                this.NotificationIcon = "ban";
            }
        }
    }
});

$(document).ready(function () {
    
    initializUsersSelect2(); 
    
    initializeQESelect2();
    
    initializStatusSelect2();
    
    initializeEditProjectValidator();
    
    resetForm();
    
    jquery(".search-estimates").val(selectedEstimate).trigger("change");
    
    $(document).keyup(function(event) {
        if (event.key == "Escape") {
            VueInstance.clearOverLayMessage();
        }
    });
});
/**
 * Intializing the search autoselect
 * 
 */
var initializUsersSelect2 = function() {
    jquery(".search-users").select2({
        placeholder: 'Please Select User',
        language: {
            noResults: function() {
                return "No users found";
            }
        }
    }).next("span.select2").css({
        display: 'block',
        width: '100%'
    });
};

/**
 * Intializing the search autoselect
 * 
 */
var initializeQESelect2 = function() {
    jquery(".search-estimates").select2({
        placeholder: 'Please Select Estimation',
        language: {
            noResults: function () {
                return "No estimations found";
            }
        }
    }).next("span.select2").css({
        display: 'block',
        width: '100%'
    });
    jquery(".search-estimates").on('change', function () {
        if (this.value.length > 0) {
            VueInstance.fetchSiteDetails(this.value);
            $(this).next('span.select2').find(".select2-selection").removeClass("select2-selection-error");
            $(this).valid();
            $("#Name").next('span').empty().parent().removeClass('has-error');
        }
    });  
};

/**
 * Intializing the status
 * 
 */
var initializStatusSelect2 = function() {
    jquery("#ProjectStatus").select2({
        placeholder: 'Please Select Status',
        language: {
            noResults: function() {
                return "No Status found";
            }
        }
    }).next("span.select2").css({
        display: 'block',
        width: '100%'
    });
    jquery("#ProjectStatus").on('change', function () {
        if (this.value.length > 0) {
            $(this).next('span.select2').find(".select2-selection").removeClass("select2-selection-error");
            $(this).valid();
        }
    });
};

/**
 * Function initializes Create Project Validator.
 * 
 * @return  No
 */
var initializeEditProjectValidator = function () {    
    editProjectValidator = $("#EditProjectForm").validate({
        ignore: [],
        onkeyup: function (element, event) {
            if (this.invalid.hasOwnProperty(element.name)) {
                $(element).valid();
            }
        },
        errorClass: "help-block text-danger",
        errorElement: "span",
        
        highlight: function (element, errorClass) {
            if ((element.id == "QuickEstimation") || (element.id == "ProjectStatus")) {
                $(element).next('span.select2').find(".select2-selection").addClass("select2-selection-error").closest('.form-group').addClass('has-error');
            } else if($(element).attr('id').match("RolesUsersMap")){
                $(element).next('span.select2').find(".select2-selection").addClass("select2-selection-error").closest('.form-group').addClass('has-error');
            } else  {
                $(element).parent().addClass("has-error");
            }
        },
        unhighlight: function (element, errorClass) {     
            if ((element.id == "QuickEstimation") || (element.id == "ProjectStatus")) {
                $(element).next('span.select2').find(".select2-selection").removeClass("select2-selection-error").closest('.form-group').removeClass('has-error');
            } else if($(element).attr('id').match("RolesUsersMap")){
                $(element).next('span.select2').find(".select2-selection").removeClass("select2-selection-error").closest('.form-group').removeClass('has-error');
            } else  {
                $(element).parent().removeClass("has-error");
            }
        }, 
        errorPlacement: function (error, element) {
            if ((element.id == "QuickEstimation") || (element.class == "search-users") || (element.id == "ProjectStatus")) {
                error.insertAfter($(element).next("span.select2"));
            } else{
                error.appendTo($(element).parent());
            }
        },        
        rules: {   
            QuickEstimation: {
                required: true
            },
            Name: {
                required: true,
                remote: {
                    async: false,
                    url: $("#Name").data("entity-existence-url"),
                    type: 'POST',
                    data: { ProjectId: $("#ProjectId").val() },
                    statusCode: {
                        500: function () {
                             VueInstance.populateNotifications({
                                status: "error",
                                message: AlertData["10077"]
                            });
                        }
                    },
                    success: function (response) {
                        populateRemoteError(response, "Name");
                    }
                }
            },
            Description: {
                CheckConsecutiveSpaces: true,
                maxlength: 255
            },
            ProjectStatus: {
                required: true
            }
        },        
        messages: {
            QuickEstimation: {
                required: "Please select Estimation."
            },
            Name: {
                required: "Project Name can't be blank."
            },
            Description: {
                maxlength: "Maximum 255 characters are allowed in Description."
            },
            ProjectStatus: {
                required: "Please select Status."
            }
        },
        submitHandler: function (form) {
            
            VueInstance.ProjectFormOverlay = false;
            VueInstance.OverLayMessage = "Updating Data...";
            
            const config = { headers: { 'Data-Type': 'json', 'Content-Type': 'false', 'Process-Data': 'false' } };
            
            return new Promise((resolve, reject) => {
                
                axios.post(form.action, new FormData(form), config)
                
                .then(response => {
                    
                    VueInstance.onSuccess(response.data);
            
                    resolve(response.data);              
                })
                .catch(error => {
                    
                    VueInstance.onFail(error);
            
                    reject(error.response.data);                  
                })
                .then(() => {
                   
                   VueInstance.ProjectFormOverlay = true;
                });
            });
        }
    });
};

/**
 * Function initializes reset form event
 * 
 * @return  No
 */
var resetForm = function() {
    $("#EditProjectForm").on('reset', function (event) {
        location.reload();
    });
};

/**
 * Populates the laravel validator error's.
 * 
 * @type type
 */
function populateFormErrors(errors, formValidator)
{
    for (var elementName in errors) {
        var errorObject = {},
                previousValue = $("#" + elementName).data("previousValue") ? $("#" + elementName).data("previousValue") : {};
        previousValue.valid = false;
        previousValue.message = errors[elementName][0];
        $("#" + elementName).data("previousValue", previousValue);
        errorObject[elementName] = errors[elementName][0];
        formValidator.showErrors(errorObject);
    }
}

/**
 * Populates remote object errors of Update Project form.
 *
 * @param   JSON    response
 * @param   string  elementName
 * @return  void
 */
function populateRemoteError(response, elementName)
{
    let currentElement = $("#" + elementName),
        previousElement = editProjectValidator.previousValue(currentElement[0]),
        submitted, valid;

    if (response.status === "success") {

        editProjectValidator.resetInternals();
        editProjectValidator.toHide = editProjectValidator.errorsFor(currentElement[0]);
        editProjectValidator.successList.push(currentElement[0]);
        delete editProjectValidator.invalid[elementName];
        delete editProjectValidator.pending[elementName];
        editProjectValidator.pendingRequest -= 1;
        editProjectValidator.showErrors();
        let previousValue = $("#" + elementName).data("previousValue");
        previousValue.valid = true;
        $("#" + elementName).data("previousValue", previousValue);
        $("#" + elementName).parent().removeClass('has-error');

    } else if (response.status === "fail") {

        let errorObject = {},
            errors = response.data.errors;

        for (let index in errors) {
            errorObject[index] = errors[index][0];
            editProjectValidator.invalid[elementName] = true;
            delete editProjectValidator.pending[elementName];
            editProjectValidator.pendingRequest -= 1;
            editProjectValidator.showErrors(errorObject);
            var previousValue = $("#" + index).data("previousValue");
            previousValue.valid = false;
            previousValue.message = errors[index][0];
            $("#" + index).data("previousValue", previousValue);
        }

    } else if (response.status === "error") {
        
        VueInstance.populateNotifications({
            status: "error",
            message: response.message
        });
        
    } else {
        
        console.error("Unknown response given to populateRemoteError()");
    }
}