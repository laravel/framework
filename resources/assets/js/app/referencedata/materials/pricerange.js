let addValidator, editValidator, vueInstance;

var rules = {
    Name: {
        required: true,
        CheckConsecutiveSpaces: true,
        minlength: 3,
        maxlength: 255
    },
    Description: {
        CheckConsecutiveSpaces: true,
        minlength: 3,
        maxlength: 255
    },
    Status: {
        required: true
    }
};

var messages = {
    Name: {
        required: "Name can't be blank."
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
import CreateRange from '../../../components/referencedata/CreateRange';
import EditRange from '../../../components/referencedata/EditRange';

vueInstance = new Vue({
    el: "#PriceRangePage",
    data: {
        PriceRanges: [],
        StoreRoute: StoreRoute,
        UpdateRoute: UpdateRoute,
        ShowSaveLoader: false,
        ShowUpdateLoader: false,
        currentRangeId: null,
        FormOverLay: true,
        NotificationIcon: "",
        NotificationMessage: "",
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
                Name: "Name",
                Description: "Description",
                IsActive: "Active/InActive Status",
                Action: "Action to be taken"
            },
            columnsClasses: {
                'Id': 'range-id',
                'Name': 'range-name',
                'Description': 'range-desc',
                'IsActive': 'range-status',
                'Action': 'range-action'
            },
            filterable: ['Name', 'Description'],
            sortable: ['Name', 'Description']
        }
    },
    components: {
        'create-range': CreateRange,
        'edit-range': EditRange
    },
    created() {
        this.PriceRanges = Ranges;
    },
    computed: {
        filteredRanges() {
            return this.PriceRanges;
        },
        selectedRange() {
            if (this.currentRangeId) {
                return _.find(this.PriceRanges, function (status) {
                    return status.Id === this.currentRangeId;
                }.bind(this));
            } else {
                return {};
            }
        }
    },
    methods: {
        /**
         * Open Create Price Range Modal
         *
         * @param No
         * @returns void
         */
        addRange() {
            $("#AddRangeModal").modal({
                show: true
            });
        },
         /**
         * Make new Price Range create http request
         *
         * @param No
         * @returns void
         */
        OnCreateRangeRequest() {
            addValidator = $("#AddRangeForm").validate({
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
                    $("#AddRangeSubmitBtn").trigger('blur');
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
                        $("#AddRangeSubmitBtn").trigger('blur');
                    });
                }
            });
        },
        /**
         * Opens edit range modal
         *
         * @param {String} rangeId
         * @returns void
         */ 
        editRange(rangeId) {
            if (rangeId) {
                vueInstance.currentRangeId = rangeId;
                $("#EditRangeModal").modal({
                    show: true
                });
            } else {
                vueInstance.currentRangeId = null;
            }
        },
        /**
         * Update Range request
         *
         * @param No
         * @returns void
         */
        OnUpdateRangeRequest() {
            editValidator = $("#EditRangeForm").validate({
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
                    $("#EditRangeSubmitBtn").trigger('blur');
                    vueInstance.ShowUpdateLoader = true;
                    let formData = new FormData(form);
                    let data = {
                        name: $("#EditName").val(),
                        description: $("#EditDescription").val(),
                        status: ($("#EditRangeForm input.input-radio:checked").val() === "Active") ? true : false
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
                        $("#EditRangeSubmitBtn").trigger('blur');
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
                    let status = ($("#AddRangeForm input.input-radio:checked").val() === "Active") ? true : false;
                    vueInstance.PriceRanges.push({
                        Id: response.rangeId,
                        Name: $("#Name").val(),
                        Description: $("#Description").val(),
                        IsActive: status
                    });
                    $("#AddRangeForm").trigger('reset');
                } else {
                    this.selectedRange.Name = data.name;
                    this.selectedRange.Description = data.description;
                    this.selectedRange.IsActive = data.status;
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
    $('#AddRangeModal').on('hidden.bs.modal', function () {
        $("#AddRangeForm").trigger('reset');
        addValidator.resetForm();
    });
    $('#EditRangeModal').on('hidden.bs.modal', function () {
        vueInstance.currentRangeId = null;
        $(".alert").addClass('hidden');
        editValidator.resetForm();
    });
    vueInstance.OnCreateRangeRequest();
    vueInstance.OnUpdateRangeRequest();
});