let addEnquiryStatusFormValidator, editEnquiryStatusFormValidator, vueInstance;

/** include needed packages **/
require('../../../bootstrap');

let VueTables = require('vue-tables-2');
Vue.use(VueTables.ClientTable);

//Register components
import OverlayNotification from '../../../components/overlayNotification';

vueInstance = new Vue({
    el: "#EnquiryStatusPage",
    data: {
        EnquiryStatus: [],
        StoreEnquiryStatusRoute: StoreEnquiryStatusRoute,
        ShowSaveStatusLoader: false,
        ShowUpdateEnquiryStatusLoader: false,
        currentEnquiryStatusId: null,
        FormOverLay: true, // Toggle for notification loader
        NotificationIcon: "",
        NotificationMessage: "", //Message for notification loader
        columns: ['Id', 'Name', 'Description', 'IsActive', 'Action'],
        options: {
            headings: {
                Id: '#',
                IsActive: 'Status'
            },
            texts: {
                filterPlaceholder: "Search...",
                noResults: "No matching records found."
            },
            headingsTooltips: {
                Id: "S.No",
                Name: "Status Name",
                Description: "Status Description",
                IsActive: "Active/InActive Status",
                Action: "Action to be taken"
            },
            columnsClasses: {
                'Id': 'map-id',
                'Name': 'map-name',
                'Description': 'map-desc',
                'IsActive' : 'map-status',
                'Action': 'map-action'
            },
            filterable: ['Name', 'Description'],
            sortable: ['Name', 'Description']
        }
    },
    components: {
        'overlay-notification': OverlayNotification
    },
    created() {
        this.EnquiryStatus = EnquiryStatus;
    },
    computed: {
        filteredEnquiryStatus: function () {
            return _.sortBy(this.EnquiryStatus, ["Name"]);
        },
        selectedEnquiryStatusData() {
            if (this.currentEnquiryStatusId) {
                return _.find(this.EnquiryStatus, function (status) {
                    return status.Id === this.currentEnquiryStatusId;
                }.bind(this));
            } else {
                return {};
            }
        }
    },
    methods: {
        //Open Create Status Modal
        addEnquiryStatus() {
            $("#AddEnquiryStatusModal").modal({
                show: true
            });
        },
        //Create New Status request
        OnSubmitAddStatusForm() {
            addEnquiryStatusFormValidator = $("#AddEnquiryStatusForm").validate({
                ignore: [],
                onkeyup: function (element, event) {
                    if (this.invalid.hasOwnProperty(element.name)) {
                        $(element).valid();
                    }
                },
                errorClass: "help-block text-danger",
                errorElement: "span",
                highlight: function (element, errorClass) {
                    $(element).closest('.form-group').addClass("has-error");
                },
                unhighlight: function (element, errorClass) {
                    $(element).closest('.form-group').removeClass("has-error");
                },
                errorPlacement: function (error, element) {
                    error.appendTo($(element).parent());
                },
                rules: {
                    Name: {
                        required: true,
                        validateSentence: true,
                        CheckConsecutiveSpaces: true,
                        minlength: 3,
                        maxlength: 255
                    },
                    Description: {
                        validateSentence: true,
                        CheckConsecutiveSpaces: true,
                        minlength: 3,
                        maxlength: 255
                    },
                    Status: {
                        required: true
                    }
                },
                messages: {
                    Name: {
                        required: "Name can't be blank."
                    },
                    Status: {
                        required: "Status can't be blank."
                    }
                },
                submitHandler: function (form, event) {
                    event.preventDefault();
                    $("#AddStatusFormSubmitBtn").trigger('blur');
                    vueInstance.ShowSaveStatusLoader = true;
                    let formData = new FormData(form);
                    $.ajax({
                        url: form.action,
                        type: 'POST',
                        dataType: 'json',
                        data: formData,
                        processData: false,
                        contentType: false
                    })
                    .done(function (response) {
                        vueInstance.onSuccess(response, true);
                    })
                    .fail(function (error) {
                        vueInstance.onFail(error, addEnquiryStatusFormValidator);
                    })
                    .always(function () {
                        vueInstance.ShowSaveStatusLoader = false;
                        $("#AddStatusFormSubmitBtn").trigger('blur');
                    });
                }
            });
        },
        //Update Status request
        OnSubmitEditEnquiryStatusForm() {
            editEnquiryStatusFormValidator = $("#EditEnquiryStatusForm").validate({
                ignore: [],
                onkeyup: function (element, event) {
                    if (this.invalid.hasOwnProperty(element.name)) {
                        $(element).valid();
                    }
                },
                errorClass: "help-block text-danger",
                errorElement: "span",
                highlight: function (element, errorClass) {
                    $(element).closest('.form-group').addClass("has-error");
                },
                unhighlight: function (element, errorClass) {
                    $(element).closest('.form-group').removeClass("has-error");
                },
                errorPlacement: function (error, element) {
                    error.appendTo($(element).parent());
                },
                rules: {
                    EditEnquiryStatusName: {
                        required: true,
                        validateSentence: true,
                        CheckConsecutiveSpaces: true,
                        minlength: 3,
                        maxlength: 255
                    },
                    EditEnquiryStatusDescription: {
                        validateSentence: true,
                        CheckConsecutiveSpaces: true,
                        minlength: 3,
                        maxlength: 255
                    },
                    EditEnquiryStatus: {
                        required: true
                    }
                },
                messages: {
                    EditEnquiryStatusName: {
                        required: "Name can't be blank."
                    },
                    EditEnquiryStatus: {
                        required: "Status can't be blank."
                    }
                },
                submitHandler: function (form, event) {
                    $("#EditEnquiryStatusFormSubmitBtn").trigger('blur');
                    vueInstance.ShowUpdateEnquiryStatusLoader = true;
                    let formData = new FormData(form);
                    let currentEnquiryStatusId = vueInstance.currentEnquiryStatusId;
                    let data = {
                        name: $("#EditEnquiryStatusName").val(),
                        description: $("#EditEnquiryStatusDescription").val(),
                        status: ($("#EditEnquiryStatusForm input.input-radio:checked").val() === "Active") ? true : false
                    };
                    $.ajax({
                        url: form.action + "/" + currentEnquiryStatusId,
                        type: 'POST',
                        dataType: 'json',
                        data: formData,
                        processData: false,
                        contentType: false
                    })
                    .done(function (response) {
                        vueInstance.onSuccess(response, false, data);
                    })
                    .fail(function (error) {
                        vueInstance.onFail(error, editEnquiryStatusFormValidator);
                    })
                    .always(function () {
                        vueInstance.ShowUpdateEnquiryStatusLoader = false;
                        $("#EditEnquiryStatusFormSubmitBtn").trigger('blur');
                    });
                }
            });
        },
        //Function to execute on Success Http response
        onSuccess(response, isAddForm = true, data = null) {
            if (response.status === "success") {
                if (isAddForm) {
                    // Post submit add form code goes here...
                    let status = ($("#AddEnquiryStatusForm input.input-radio:checked").val() === "Active") ? true : false;
                    vueInstance.EnquiryStatus.push({
                        Id: response.enquiryStatusId,
                        Name: $("#Name").val(),
                        Description: $("#Description").val(),
                        IsActive: status
                    });
                    $("#AddEnquiryStatusForm").trigger('reset');
                } else {
                    this.selectedEnquiryStatusData.Name = data.name;
                    this.selectedEnquiryStatusData.Description = data.description;
                    this.selectedEnquiryStatusData.IsActive = data.status;
                }
                vueInstance.populateNotifications(response);
            } else {
                vueInstance.populateNotifications({
                    status: "error",
                    message: AlertData["10077"]
                });
            }
        },
        //Hide Success Message
        clearOverLayMessage() {
            this.FormOverLay = true;
            this.NotificationMessage = "";
            this.NotificationIcon = "";
        },
        //Function to execute on failed Http response 
        onFail(error, validator) {
            if (error.status === 422) {
                var response = JSON.parse(error.responseText);
                vueInstance.populateFormErrors(response.data.errors, validator);
            } else if (error.status === 500) {
                vueInstance.populateNotifications({
                    status: "error",
                    message: AlertData["10077"]
                });
            } else {
                vueInstance.populateNotifications({
                    status: "error",
                    message: AlertData["10077"]
                });
            }
        },
        //Function to execute on 444 Http response 
        populateFormErrors(errors, formValidator) {
            for (let elementName in errors) {
                let errorObject = {},
                        previousValue = $("#" + elementName).data("previousValue") ? $("#" + elementName).data("previousValue") : {};
                previousValue.valid = false;
                previousValue.message = errors[elementName][0];
                $("#" + elementName).data("previousValue", previousValue);
                errorObject[elementName] = errors[elementName][0];
                formValidator.showErrors(errorObject);
            }
        },
        //Populates notifications of the form.
        populateNotifications(response) {
            this.NotificationMessage = response.message;
            this.NotificationIcon = response.status;
            this.FormOverLay = false;
            if (response.status == "success") {
                this.ShowSaveStatusLoader = false;
                this.NotificationIcon = "check-circle";
                setTimeout(this.clearOverLayMessage, 3000);

            } else if (response.status == 'error') {
                this.NotificationIcon = "ban";
            } else if (response.status == 'warning') {
                this.NotificationIcon = "warning";
            } else {
                this.ShowSaveStatusLoader = false;
                this.NotificationIcon = "ban";
            }
        },
        //Update form modal
        editEnquiryStatus(enquiryStatusId) {
            if (enquiryStatusId) {
                vueInstance.currentEnquiryStatusId = enquiryStatusId;
                $("#EditEnquiryStatusModal").modal({
                    show: true
                });
            } else {
                vueInstance.currentEnquiryStatusId = null;
            }
        },
        //Returns Status description
        getStatusDesc(description) {
            return description ? description : '<small>N/A</small>';
        }
    }
});

$(document).ready(function () {
    //Reset Form on Modal Close
    $('#AddEnquiryStatusModal').on('hidden.bs.modal', function () {
        $("#AddEnquiryStatusForm").trigger('reset');
        addEnquiryStatusFormValidator.resetForm();
    });

    $('#EditEnquiryStatusModal').on('hidden.bs.modal', function () {
        vueInstance.currentEnquiryStatusId = null;
        $(".alert").addClass('hidden');
        editEnquiryStatusFormValidator.resetForm();
    });

    vueInstance.OnSubmitAddStatusForm();
    vueInstance.OnSubmitEditEnquiryStatusForm();
});