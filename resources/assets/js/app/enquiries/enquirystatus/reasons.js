let addValidator, editValidator, vueInstance;

var rules = {
    Description: {
        required: true,
        CheckConsecutiveSpaces: true,
        minlength: 3,
        maxlength: 255
    },
    EnquiryStatus: {
        required: true
    },
    Status: {
        required: true
    }
};

var messages = {
    Description: {
        required: "Description can't be blank."
    },
    EnquiryStatus: {
        required: "Enquiry Status can't be blank."
    },
    Status: {
        required: "Status can't be blank."
    }
};

/* include needed packages */
require('../../../bootstrap');

let VueTables = require('vue-tables-2');
Vue.use(VueTables.ClientTable);

/* Register components */
import CreateReason from '../../../components/enquiries/enquirystatus/CreateReason';
import EditReason from '../../../components/enquiries/enquirystatus/EditReason';

vueInstance = new Vue({
    el: "#StatusReasonsPage",
    data: {
        reasons: [],
        StatusAvailable: [],
        StoreReasonRoute: StoreReasonRoute,
        UpdateReasonRoute: UpdateReasonRoute,
        ShowSaveLoader: false,
        ShowUpdateLoader: false,
        currentReasonId: null,
        FormOverLay: true, // Toggle for notification loader
        NotificationIcon: "",
        NotificationMessage: "", //Message for notification loader
        columns: ['Id', 'EnquiryStatus', 'Reason', 'IsActive', 'Action'],
        options: {
            headings: {
                Id: '#',
                EnquiryStatus: 'Enquiry Status',
                IsActive: 'Status'
            },
            texts: {
                filterPlaceholder: "Search...",
                noResults: "No matching records found."
            },
            headingsTooltips: {
                Id: "S.No",
                EnquiryStatus: "Enquiry Status",
                Reason: "Status Reason",
                IsActive: "Active/InActive Status",
                Action: "Action to be taken"
            },
            columnsClasses: {
                'Id': 'desc-id',
                'EnquiryStatus': 'desc-enquirystatus',  
                'Description': 'desc-desc',
                'IsActive': 'desc-status',
                'Action': 'desc-action'
            },
            filterable: ['Reason'],
            sortable: ['Reason', 'EnquiryStatus']
        }
    },
    components: {
        'create-reason': CreateReason,
        'edit-reason': EditReason
    },
    created() {
        this.reasons = StatusReasons;
        this.StatusAvailable = EnquiryStatus;
    },
    computed: {
        filteredReasons: function () {
            return _.sortBy(this.reasons, ["EnquiryStatus"]);
        },
        selectedReason() {
            if (this.currentReasonId) {
                return _.find(this.reasons, function (status) {
                    return status.Id === this.currentReasonId;
                }.bind(this));
            } else {
                return {};
            }
        }
    },
    methods: {
        /**
         * Return Enquiry Status
         *
         * @param { string } id
         * @returns string
         */
        getStatus(id) {
            if (id) {
                if (this.StatusAvailable.length > 0) {
                    let status = _.find(this.StatusAvailable, ["Id", id]);
                    if (!_.isUndefined(status)) {
                       return status.Name;
                    }
                }
            }
            return 'N/A';
        },
        /**
         * Open Create Reason Modal
         *
         * @param No
         * @returns void
         */
        addReason() {
            $("#AddReasonModal").modal({
                show: true
            });
        },
         /**
         * Make New Reason create http request
         *
         * @param No
         * @returns void
         */
        OnCreateReasonRequest() {
            addValidator = $("#AddReasonForm").validate({
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
                rules: rules,
                messages: messages,
                submitHandler: function (form, event) {
                    event.preventDefault();
                    $("#AddReasonSubmitBtn").trigger('blur');
                    vueInstance.ShowSaveLoader = true;
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
                        vueInstance.onFail(error, addValidator);
                    })
                    .always(function () {
                        vueInstance.ShowSaveLoader = false;
                        $("#AddReasonSubmitBtn").trigger('blur');
                    });
                }
            });
        },
        /**
         * Opens edit reason modal
         *
         * @param {String} reasonId
         * @returns void
         */ 
        editReason(reasonId) {
            if (reasonId) {
                vueInstance.currentReasonId = reasonId;
                $("#EditReasonModal").modal({
                    show: true
                });
            } else {
                vueInstance.currentReasonId = null;
            }
        },
        /**
         * Update Reason request
         *
         * @param {String} reasonId
         * @returns void
         */
        OnUpdateReasonRequest() {
            editValidator = $("#EditReasonForm").validate({
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
                rules: rules,
                messages: messages,
                submitHandler: function (form, event) {
                    $("#EditReasonSubmitBtn").trigger('blur');
                    vueInstance.ShowUpdateLoader = true;
                    let formData = new FormData(form);
                    let data = {
                        enquirystatus: $("#EditEnquiryStatus").val(),
                        description: $("#EditDescription").val(),
                        status: ($("#EditReasonForm input.input-radio:checked").val() === "Active") ? true : false
                    };
                    $.ajax({
                        url: form.action,
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
                        vueInstance.onFail(error, editValidator);
                    })
                    .always(function () {
                        vueInstance.ShowUpdateLoader = false;
                        $("#EditReasonSubmitBtn").trigger('blur');
                    });
                }
            });
        },
         /**
         * Function to execute on Success Http response
         *
         * @param {Object} response
         * @param {boolean} isAddForm
         * @param {string} data
         * @returns void
         */
        onSuccess(response, isAddForm = true, data = null) {
            if (response.status === "success") {
                if (isAddForm) {
                    let status = ($("#AddReasonForm input.input-radio:checked").val() === "Active") ? true : false;
                    vueInstance.reasons.push({
                        Id: response.id,
                        EnquiryStatusId: $("#EnquiryStatus").val(),
                        EnquiryStatus: $("#EnquiryStatus option:selected").text(),
                        Reason: $("#Description").val(),
                        IsActive: status
                    });
                    $("#AddReasonForm").trigger('reset');
                } else {
                    this.selectedReason.EnquiryStatusId = data.enquirystatus;
                    this.selectedReason.Reason = data.description;
                    this.selectedReason.EnquiryStatus = this.getStatus(data.enquirystatus);
                    this.selectedReason.IsActive = data.status;
                }
                vueInstance.populateNotifications(response);
            } else {
                vueInstance.populateNotifications({
                    status: "error",
                    message: AlertData["10077"]
                });    
            }
        },
         /**
         * Function to execute on failed Http response
         *
         * @param {Object} error
         * @param {Object} validator
         * @returns void
         */ 
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
        /**
         * Populates notifications of the form.
         *
         * @param {Object} response
         * @returns void
         */ 
        populateNotifications(response) {
            this.NotificationMessage = response.message;
            this.NotificationIcon = response.status;
            this.FormOverLay = false;
            if (response.status === "success") {
                this.ShowSaveLoader = false;
                this.NotificationIcon = "check-circle";
                setTimeout(this.clearOverLayMessage, 3000);

            } else if (response.status === 'error') {
                this.NotificationIcon = "ban";
            } else if (response.status === 'warning') {
                this.NotificationIcon = "warning";
            } else {
                this.ShowSaveLoader = false;
                this.NotificationIcon = "ban";
            }
        },
        /**
         * Hide Success Message
         *
         * @param No
         * @returns void
         */ 
        clearOverLayMessage() {
            this.FormOverLay = true;
            this.NotificationMessage = "";
            this.NotificationIcon = "";
        },
        /**
         * Function to execute on 422 Http response 
         *
         * @param {Object} errors
         * @param  {Object} formValidator
         * @returns void
         */ 
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
        }
    }
});

$(document).ready(function () {
    //Reset forms on Modal Close
    $('#AddReasonModal').on('hidden.bs.modal', function () {
        $("#AddReasonForm").trigger('reset');
        addValidator.resetForm();
    });
    $('#EditReasonModal').on('hidden.bs.modal', function () {
        vueInstance.currentReasonId = null;
        $(".alert").addClass('hidden');
        editValidator.resetForm();
    });
    vueInstance.OnCreateReasonRequest();
    vueInstance.OnUpdateReasonRequest();
});