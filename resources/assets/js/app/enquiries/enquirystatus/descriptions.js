let addValidator, editValidator, vueInstance;

/** include needed packages **/
require('../../../bootstrap');

let VueTables = require('vue-tables-2');
Vue.use(VueTables.ClientTable);

//Register components
import OverlayNotification from '../../../components/overlayNotification';

vueInstance = new Vue({
    el: "#StatusDescriptionsPage",
    data: {
        descriptions: [],
        StatusAvailable: [],
        StoreDescriptionUrl: StoreDescriptionRoute,
        ShowSaveDescLoader: false,
        ShowUpdateDescLoader: false,
        currentDescriptionId: null,
        FormOverLay: true, // Toggle for notification loader
        NotificationIcon: "",
        NotificationMessage: "", //Message for notification loader
        columns: ['Id', 'EnquiryStatus', 'Description', 'IsActive', 'Action'],
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
                Description: "Status Description",
                IsActive: "Active/InActive Status",
                Action: "Action to be taken"
            },
            columnsClasses: {
                'Id': 'desc-id',
                'EnquiryStatus': 'desc-enquirystatus',
                'Description': 'desc-desc',
                'IsActive' : 'desc-status',
                'Action': 'desc-action'
            },
            filterable: ['Description', 'EnquiryStatus'],
            sortable: ['Description', 'EnquiryStatus']
        }
    },
    components: {
        'overlay-notification': OverlayNotification
    },
    created() {
        this.descriptions = StatusDescriptions;
        this.StatusAvailable = EnquiryStatus;
    },
    computed: {
        filteredDescriptions: function () {
            return _.sortBy(this.descriptions, ["EnquiryStatus"]);
        },
        selectedDescription() {
            if (this.currentDescriptionId) {
                return _.find(this.descriptions, function (status) {
                    return status.Id === this.currentDescriptionId;
                }.bind(this));
            } else {
                return {};
            }
        }
    },
    methods: {
        //Get Enquiry Status
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
        //Open Create Description Modal
        addDescription(){
            $("#AddDescriptionModal").modal({
                show: true
            });
        },
        //Make New Description create http request
        OnCreateDescRequest() {
            addValidator = $("#AddDescriptionForm").validate({
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
                },
                messages: {
                    Description: {
                        required: "Description can't be blank."
                    },
                    EnquiryStatus: {
                        required: "Enquiry Status can't be blank."
                    },
                    Status: {
                        required: "Status can't be blank."
                    }
                },
                submitHandler: function (form, event) {
                    event.preventDefault();
                    $("#AddDescSubmitBtn").trigger('blur');
                    vueInstance.ShowSaveDescLoader = true;
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
                        vueInstance.ShowSaveDescLoader = false;
                        $("#AddDescSubmitBtn").trigger('blur');
                    });
                }
            });
        },
        //Edit Description modal
        editDescription(descId) {
            if (descId) {
                vueInstance.currentDescriptionId = descId;
                $("#EditDescriptionModal").modal({
                    show: true
                });
            } else {
                vueInstance.currentDescriptionId = null;
            }
        },
        //Update Description request
        OnUpdateDescRequest() {
            editValidator = $("#EditDescriptionForm").validate({
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
                    EditEnquiryStatus: {
                        required: true
                    },
                    EditDescription: {
                        required: true,
                        CheckConsecutiveSpaces: true,
                        minlength: 3,
                        maxlength: 255
                    },
                    EditStatus: {
                        required: true
                    }
                },
                messages: {
                    EditDescription: {
                        required: "Description can't be blank."
                    },
                    EditEnquiryStatus: {
                        required: "Enquiry Status can't be blank."
                    },
                    EditStatus: {
                        required: "Status can't be blank."
                    }
                },
                submitHandler: function (form, event) {
                    $("#EdiDescSubmitBtn").trigger('blur');
                    vueInstance.ShowUpdateDescLoader = true;
                    let formData = new FormData(form);
                    let currentEnquiryStatusId = vueInstance.currentDescriptionId;
                    let data = {
                        enquirystatus: $("#EditEnquiryStatus").val(),
                        description: $("#EditDescription").val(),
                        status: ($("#EditDescriptionForm input.input-radio:checked").val() === "Active") ? true : false
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
                        vueInstance.onFail(error, editValidator);
                    })
                    .always(function () {
                        vueInstance.ShowUpdateDescLoader = false;
                        $("#EdiDescSubmitBtn").trigger('blur');
                    });
                }
            });
        },
        //Function to execute on Success Http response
        onSuccess(response, isAddForm = true, data = null) {
            if (response.status === "success") {
                if (isAddForm) {
                    //Post submit add form code goes here...
                    let status = ($("#AddDescriptionForm input.input-radio:checked").val() === "Active") ? true : false;
                    vueInstance.descriptions.push({
                        Id: response.id,
                        EnquiryStatusId: $("#EnquiryStatus").val(),
                        EnquiryStatus: $("#EnquiryStatus option:selected").text(),
                        Description: $("#Description").val(),
                        IsActive: status
                    });
                    $("#AddDescriptionForm").trigger('reset');
                } else {
                    this.selectedDescription.EnquiryStatusId = data.enquirystatus;
                    this.selectedDescription.Description = data.description;
                    this.selectedDescription.EnquiryStatus = this.getStatus(data.enquirystatus);
                    this.selectedDescription.IsActive = data.status;
                }
                vueInstance.populateNotifications(response);
            } else {
                vueInstance.populateNotifications({
                    status: "error",
                    message: AlertData["10077"]
                });
            }
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
        //Populates notifications of the form.
        populateNotifications(response) {
            this.NotificationMessage = response.message;
            this.NotificationIcon = response.status;
            this.FormOverLay = false;
            if (response.status === "success") {
                this.ShowSaveDescLoader = false;
                this.NotificationIcon = "check-circle";
                setTimeout(this.clearOverLayMessage, 3000);

            } else if (response.status === 'error') {
                this.NotificationIcon = "ban";
            } else if (response.status === 'warning') {
                this.NotificationIcon = "warning";
            } else {
                this.ShowSaveDescLoader = false;
                this.NotificationIcon = "ban";
            }
        },
        //Hide Success Message
        clearOverLayMessage() {
            this.FormOverLay = true;
            this.NotificationMessage = "";
            this.NotificationIcon = "";
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
        }
    }
});

$(document).ready(function () {
    //Reset Form on Modal Close
    $('#AddDescriptionModal').on('hidden.bs.modal', function () {
        $("#AddDescriptionForm").trigger('reset');
        addValidator.resetForm();
    });

    $('#EditDescriptionModal').on('hidden.bs.modal', function () {
        vueInstance.currentDescriptionId = null;
        $(".alert").addClass('hidden');
        editValidator.resetForm();
    });

    vueInstance.OnCreateDescRequest();
    vueInstance.OnUpdateDescRequest();
});