let vueInstance, editEnquiryActionFormValidator;

/** include needed packages **/
require('../../../bootstrap');

let VueTables = require('vue-tables-2');
Vue.use(VueTables.ClientTable);

//Register components
import OverlayNotification from '../../../components/overlayNotification';

vueInstance = new Vue({
    el: "#EnquiryActionsPage",
    data: {
        EnquiryActions: [],
        ActionStatus: ActionStatus, // Action's status array
        Users: Users,
        ViewAllAction: null,
        ShowUpdateEnquiryActionLoader: false,
        ShowDeleteActionLoader: false,
        currentEnquiryActionId: null,
        StatusLabels: {1: 'warning', 2: 'primary', 3: 'inprogress', 4: 'success', 5: 'danger', 6: 'default'},
        FormOverLay: true, // Toggle for notification loader
        NotificationIcon: "",
        NotificationMessage: "", //Message for notification loader
        columns: ['Id', 'EnquiryRefNo', 'EnquiryName', 'Action', 'AssignedTo', 'DueDate', 'Status', 'Operations'],
        options: {
            headings: {
                Id: '#',
                EnquiryRefNo: 'Reference No',
                EnquiryName: 'Enquiry Name',
                AssignedTo: 'Assigned To',
                DueDate: 'Due Date',
                Operations: ''
            },
            texts: {
                filterPlaceholder: "Search...",
                noResults: "No matching records found."
            },
            headingsTooltips: {
                Id: "S.No",
                EnquiryRefNo: "Enquiry Reference Number",
                EnquiryName: "Enquiry Name",
                Action: "Action Description",
                Status: "Action Status"
            },
            columnsClasses: {
                'Id': 'action-id',
                'EnquiryRefNo': 'action-refno pd-4',
                'EnquiryName': 'action-name pd-4',
                'AssignedTo': 'action-assignedTo pd-4',
                'DueDate': 'action-DueDate pd-4',
                'Action': 'action-description pd-4',
                'Status': 'action-status pd-4',
                'Operations': 'action-Operations pd-4'
            },
            filterable: ['EnquiryName', 'Action'],
            sortable: ['EnquiryName', 'Action', 'AssignedTo', 'DueDate']
        }
    },
    components: {
        'overlay-notification': OverlayNotification
    },
    created() {
        this.ViewAllAction = false;
        this.EnquiryActions = _.filter(EnquiryActions, function (action) {
            return (action.Status != 4 && action.Status != 6);
        }.bind(this));
    },
    computed: {
        filteredEnquiryActions: function () {
            return  this.EnquiryActions;
        },
        selectedEnquiryActionData() {
            if (this.currentEnquiryActionId) {
                return _.find(this.EnquiryActions, function (action) {
                    return action.Id === this.currentEnquiryActionId;
                }.bind(this));
            } else {
                return {};
            }
        }
    },
    methods: {
        //Returns Action Status
        getActionStatus(status) {
            return status ? '<span class="label label-' + this.StatusLabels[status] + '">' + this.ActionStatus[status] + '</span>' : '<small>N/A</small>';
        },

        //Update form modal
        editEnquiryAction(enquiryActionId) {
            if (enquiryActionId) {
                vueInstance.currentEnquiryActionId = enquiryActionId;
                $("#EditEnquiryActionModal").modal({
                    show: true
                });
            } else {
                vueInstance.currentEnquiryActionId = null;
            }
        },
        // Delete Enquiry Action
        deleteEnquiryAction(enquiryActionId) {
            if (enquiryActionId) {
                vueInstance.currentEnquiryActionId = enquiryActionId;
                $("#DeleteActionModal").modal({
                    show: true
                });
            } else {
                vueInstance.currentEnquiryActionId = null;
            }
        },
        // Deletes the action from system
        deleteAction(enquiryActionId) {
            this.ShowDeleteActionLoader = true;
            $("#DeleteActionSubmit").trigger('blur');
            $.ajax({
                url: "/enquiries/action/delete/" + enquiryActionId,
                type: 'GET',
                dataType: 'json',
                processData: false,
                contentType: false
            })
                    .done(function (response) {
                        if (response.status === "success") {
                            let ActionIndex = vueInstance.EnquiryActions.map(item => item.Id).indexOf(enquiryActionId);
                            vueInstance.EnquiryActions.splice(ActionIndex, 1);
                            vueInstance.ShowDeleteActionLoader = false;
                            $("#DeleteActionModal").modal("hide");
                            vueInstance.populateNotifications(response);
                            vueInstance.currentEnquiryActionId = null;
                        } else {
                            vueInstance.ShowDeleteActionLoader = false;
                            $("#DeleteActionModal").modal("hide");
                            vueInstance.populateNotifications({
                                status: "error",
                                message: "Something wrong happened. Please try again!"
                            });
                        }
                    }.bind(this))
                    .fail(function () {
                        vueInstance.ShowDeleteActionLoader = false;
                        $("#DeleteActionModal").modal("hide");
                        vueInstance.populateNotifications({
                            status: "error",
                            message: "Something wrong happened. Please try again!"
                        });
                    }.bind(this))
                    .always(function () {
                        vueInstance.ShowDeleteActionLoader = false;
                    });
        },
        // Redirect to Catalogue listing page
        refreshPage() {
            location.reload();
        },
        //Update Status request
        OnSubmitEditEnquiryActionForm() {
            editEnquiryActionFormValidator = $("#EditEnquiryActionForm").validate({
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
                    EditEnquiryActionDescription: {
                        required: true,
                        CheckConsecutiveSpaces: true,
                        minlength: 3
                    },
                },
                messages: {
                    EditEnquiryActionDescription: {
                        required: "Action can't be blank.",
                        minlength: "Minimum 3 characters should be in Action."
                    },
                },
                submitHandler: function (form, event) {
                    $("form").find('input:text').each(function () {
                        $(this).val($.trim($(this).val()));
                    });
                    $("#EditEnquiryActionFormSubmitBtn").trigger('blur');
                    vueInstance.ShowUpdateEnquiryActionLoader = true;
                    let formData = new FormData(form);
                    let currentEnquiryActionId = vueInstance.currentEnquiryActionId;
                    let data = {
                        description: $("#EditEnquiryActionDescription").val(),
                        assignedToId: $("#EditAssignedTo").val(),
                        assignedTo: $("#EditAssignedTo option:selected").text(),
                        status: $("#EditActionStatus").val(),
                        duedate: $("#DueDate").val()
                    };
                    $.ajax({
                        url: form.action + "/" + currentEnquiryActionId,
                        type: 'POST',
                        dataType: 'json',
                        data: formData,
                        processData: false,
                        contentType: false
                    })
                            .done(function (response) {
                                vueInstance.onSuccess(response, data);
                                if ((data.status == 4 || data.status == 6) && vueInstance.ViewAllAction == false) {
                                    let ActionIndex = vueInstance.EnquiryActions.map(item => item.Id).indexOf(currentEnquiryActionId);
                                    vueInstance.EnquiryActions.splice(ActionIndex, 1);
                                    vueInstance.currentEnquiryActionId = null;
                                    $("#EditEnquiryActionModal").modal("hide");
                                }
                            })
                            .fail(function (error) {
                                vueInstance.onFail(error, editEnquiryActionFormValidator);
                            })
                            .always(function () {
                                vueInstance.ShowUpdateEnquiryActionLoader = false;
                                $("#EditEnquiryActionFormSubmitBtn").trigger('blur');
                            });
                }
            });
        },
//        Function to execute on Success Http response
        onSuccess(response, data = null) {
            if (response.status === "success") {

                this.selectedEnquiryActionData.Action = data.description;
                this.selectedEnquiryActionData.AssignedToId = data.assignedToId;
                this.selectedEnquiryActionData.AssignedTo = data.assignedTo;
                this.selectedEnquiryActionData.Status = data.status;
                this.selectedEnquiryActionData.DueDate = data.duedate;
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

        ShowAllEnquiryAction() {
            vueInstance.ViewAllAction = true;
            vueInstance.EnquiryActions = _.filter(EnquiryActions, function (action) {
                return (action.Status < 7);
            }.bind(this));
        }
    }
});

$(document).ready(function () {
    // Define datepicker options for Enquiry, from and to date fields.
    $("#DueDate").datepicker({
        autoclose: true,
        startDate: '0d',
        endDate: '+1y',
        format: "dd-M-yyyy",
        toggleActive: true,
        todayHighlight: true,
        todayBtn: true
    });
    $("#DueDate").change(function () {
        var date = $(this).datepicker("getDate");
//        alert(date);
    });
    $('#EditEnquiryActionModal').on('hidden.bs.modal', function () {
        vueInstance.currentEnquiryActionId = null;
        $(".alert").addClass('hidden');
        editEnquiryActionFormValidator.resetForm();
    });

    vueInstance.OnSubmitEditEnquiryActionForm();
});