let notificationTimeout = 10000, notificationTimeoutID, DatatableObj, addSurfaceFinishFormValidator, editSurfaceFinishFormValidator, vueInstance;
let alertSkeleton = `
    <div class="alert alert-dismissible hidden">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <p class="body"></p>
    </div>
`;

/** include needed packages **/
require('../../../bootstrap');

// Register components
import OverlayNotification from '../../../components/overlayNotification';

vueInstance = new Vue({
    el: "#SurfaceFinishPage",
    data: {
        SurfaceFinish: [],
        StoreSurfaceFinishRoute: StoreFinishRoute,
        ShowSaveSurfaceFinishLoader: false,
        ShowUpdateSurfaceFinishLoader: false,
        currentSurfaceFinishId: null,
        FormOverLay: true, // Toggle for notification loader
        NotificationIcon: "",
        NotificationMessage: "", //Message for notification loader

    },
    components: {
        'overlay-notification': OverlayNotification
    },
    created() {
        this.SurfaceFinish = SurfaceFinish;
    },
    computed: {
        filteredSurfaceFinish: function () {
            return _.orderBy(this.SurfaceFinish, ["CreatedAt"], ["desc"]);
        },
        selectedSurfaceFinishData: function () {
            if (this.currentSurfaceFinishId) {
                return _.find(this.SurfaceFinish, function (finish) {
                    return finish.Id === this.currentSurfaceFinishId;
                }.bind(this));
            } else {
                return {};
            }
        }
    },
    methods: {
        addSurfaceFinish: function () {
            $("#AddSurfaceFinishModal").modal({
                show: true
            });
        },
        // PopulateRemoteError - function to populate remote object errors of the form.
        PopulateRemoteError(ResponseJSON, ElementName, FormValidator) {
            var CurrentElement = $("#" + ElementName),
                    PreviousElement = FormValidator.previousValue(CurrentElement[0]),
                    Submitted, Valid;
            if (ResponseJSON.status === "success") {
                FormValidator.resetInternals();
                FormValidator.toHide = FormValidator.errorsFor(CurrentElement[0]);
                FormValidator.successList.push(CurrentElement[0]);
                delete FormValidator.invalid[ElementName];
                delete FormValidator.pending[ElementName];
                FormValidator.pendingRequest -= 1;
                FormValidator.showErrors();
                var PreviousValue = $("#" + ElementName).data("previousValue");
                PreviousValue.valid = true;
                $("#" + ElementName).data("previousValue", PreviousValue);
            } else if (ResponseJSON.status === "fail") {
                let ErrorObject = {};
                for (var ErrorIndex in ResponseJSON.data.errors) {
                    ErrorObject[ErrorIndex] = ResponseJSON.data.errors[ErrorIndex][0];
                    FormValidator.invalid[ElementName] = true;
                    delete FormValidator.pending[ElementName];
                    FormValidator.pendingRequest -= 1;
                    FormValidator.showErrors(ErrorObject);
                    var PreviousValue = $("#" + ErrorIndex).data("previousValue");
                    PreviousValue.valid = false;
                    PreviousValue.message = ResponseJSON.data.errors[ErrorIndex][0];
                    $("#" + ErrorIndex).data("previousValue", PreviousValue);
                }
            } else if (ResponseJSON.status === "error") {
                vueInstance.populateNotifications({
                    status: "error",
                    message: ResponseJSON.message
                });
            }

        },
        OnsubmitAddSurfaceFinishForm() {
            addSurfaceFinishFormValidator = $("#AddSurfaceFinishForm").validate({
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
                    ShortCode: {
                        required: true,
                        minlength: 2,
                        maxlength: 50,
                        remote: {
                            url: '/surfacefinish/addcheckcode',
                            type: 'POST',
                            statusCode: {
                                500: function () {
                                    vueInstance.populateNotifications({
                                        status: "error",
                                        message: AlertData["10077"]
                                    });
                                }
                            },
                            success: function (ResponseJSON) {
                                vueInstance.PopulateRemoteError.apply(this, [ResponseJSON, "ShortCode", addSurfaceFinishFormValidator]);
                            }
                        }
                    },
                    Name: {
                        required: true,
                        ValidateAlphabet: true,
                        CheckConsecutiveSpaces: true,
                        minlength: 3,
                        maxlength: 255
                    },
                    Status: {
                        required: true
                    }
                },
                messages: {
                    ShortCode: {
                        required: "Short Code can't be blank."
                    },
                    Name: {
                        required: "Name can't be blank."
                    },
                    Status: {
                        required: "Status can't be blank."
                    }
                },
                submitHandler: function (form, event) {
                    event.preventDefault();
                    $("#AddSurfaceFinishFormSubmitBtn").trigger('blur');
                    vueInstance.ShowSaveSurfaceFinishLoader = true;
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
                                vueInstance.onFail(error, addSurfaceFinishFormValidator);
                            })
                            .always(function () {
                                vueInstance.ShowSaveSurfaceFinishLoader = false;
                                $("#AddSurfaceFinishFormSubmitBtn").trigger('blur');
                            });
                }
            });
        },
        OnsubmitEditSurfaceFinishForm() {
            editSurfaceFinishFormValidator = $("#EditSurfaceFinishForm").validate({
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
                    EditSurfaceFinishName: {
                        required: true,
                        ValidateAlphabet: true,
                        CheckConsecutiveSpaces: true,
                        minlength: 3,
                        maxlength: 255
                    },
                    EditSurfaceFinishShortCode: {
                        required: true,
                        minlength: 2,
                        maxlength: 50,
                    },
                    EditSurfaceFinishStatus: {
                        required: true
                    }
                },
                messages: {
                    EditSurfaceFinishName: {
                        required: "Name can't be blank."
                    },
                    EditSurfaceFinishShortCode: {
                        required: "ShortCode can't be blank."
                    },
                    EditSurfaceFinishStatus: {
                        required: "Status can't be blank."
                    }
                },
                submitHandler: function (form, event) {
                    event.preventDefault();
                    $("#EditSurfaceFinishFormSubmitBtn").trigger('blur');
                    vueInstance.ShowUpdateSurfaceSurfaceFinishLoader = true;
                    let formData = new FormData(form);
                    let currentSurfaceFinishId = vueInstance.currentSurfaceFinishId;
                    let status = ($("#EditSurfaceFinishForm input.input-radio:checked").val() === "Active") ? true : false;
                    $.ajax({
                        url: form.action + "/" + currentSurfaceFinishId,
                        type: 'POST',
                        dataType: 'json',
                        data: formData,
                        processData: false,
                        contentType: false
                    })
                            .done(function (response) {
                                vueInstance.onSuccess(response, false, status);
                            })
                            .fail(function (error) {
                                vueInstance.onFail(error, editSurfaceFinishFormValidator);
                            })
                            .always(function () {
                                vueInstance.ShowUpdateSurfaceSurfaceFinishLoader = false;
                                $("#EditSurfaceFinishFormSubmitBtn").trigger('blur');
                            });
                }
            });
        },
        onSuccess(response, isAddForm = true, FinishStatus = false) {
            if (response.status === "success") {
                if (isAddForm) {
                    // Post submit add form code goes here...
                    let status = ($("#AddSurfaceFinishForm input.input-radio:checked").val() === "Active") ? true : false;
                    vueInstance.SurfaceFinish.push({
                        Id: response.finishId,
                        Name: $("#Name").val(),
                        ShortCode: $("#ShortCode").val(),
                        IsActive: status
                    });
                    $("#AddSurfaceFinishForm").trigger('reset');
                } else {
                    // Get SurfaceFinish
                    let Finish = _.find(vueInstance.SurfaceFinish, function (finish) {
                        return finish.Id === vueInstance.currentSurfaceFinishId;
                    });
                    Finish.Name = $("#EditSurfaceFinishName").val();
                    Finish.ShortCode = $("#EditSurfaceFinishShortCode").val();
                    Finish.IsActive = FinishStatus;
                }
                this.populateNotifications(response);
        }
        },
        // Hide Success Message
        clearOverLayMessage() {
            this.FormOverLay = true;
        },
        onFail(jqXHR, validator) {
            if (jqXHR.status === 422) {
                var response = JSON.parse(jqXHR.responseText);
                vueInstance.populateFormErrors(response.data.errors, validator);
            } else if (jqXHR.status === 500) {
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
        populateFormErrors(errors, formValidator)
        {
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
        // Populates notifications of the form.
        populateNotifications(response, notificationAreaId = "NotificationArea") {
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
        editSurfaceFinish(finishId) {
            if (finishId) {
                vueInstance.currentSurfaceFinishId = finishId;
                $("#EditSurfaceFinishModal").modal({
                    show: true
                });
            } else {
                vueInstance.currentSurfaceFinishId = null;
            }
        },
        viewSurfaceFinish(finishId) {
            if (finishId) {
                vueInstance.currentSurfaceFinishId = finishId;
                $("#ViewSurfaceFinishModal").modal({
                    show: true
                });
            } else {
                vueInstance.currentSurfaceFinishId = null;
            }
        },

    }
});

$(document).ready(function () {

    $("#EditSurfaceFinishShortCode").keyup(function (event) {
        $('#EditSurfaceFinishShortCode').rules('add', {
            remote: {
                async: false,
                url: '/surfacefinish/editcheckcode',
                type: 'POST',
                data: {
                    ShortCode: function () {
                        return $("#EditSurfaceFinishShortCode").val();
                    },
                    FinishId: function () {
                        return vueInstance.currentSurfaceFinishId;
                    }
                },
                statusCode: {
                    500: function () {
                        vueInstance.populateNotifications({
                            status: "error",
                            message: AlertData["10077"]
                        });
                    }
                },
                success: function (ResponseJSON) {
                    $(".alert").addClass('hidden');
                    vueInstance.PopulateRemoteError.apply(this, [ResponseJSON, "EditSurfaceFinishShortCode", editSurfaceFinishFormValidator]);
                }
            }
        });
    });

    //Reset Form on Modal Close
    $('#AddSurfaceFinishModal').on('hidden.bs.modal', function () {
        $("#AddSurfaceFinishForm").trigger('reset');
        addSurfaceFinishFormValidator.resetForm();
        vueInstance.clearOverLayMessage();
    });

    $('#EditSurfaceFinishModal').on('hidden.bs.modal', function () {
        editSurfaceFinishFormValidator.resetForm();
        vueInstance.currentSurfaceFinishId = null;
        vueInstance.clearOverLayMessage();
    });
    $("#ViewSurfaceFinishModal").on('hidden.bs.modal',function(){
        vueInstance.currentSurfaceFinishId = null;
    });

    vueInstance.OnsubmitAddSurfaceFinishForm();
    vueInstance.OnsubmitEditSurfaceFinishForm();
    initializeDataTable();
});

// DataTable initialization
function initializeDataTable() {
    // DataTable initialization
    DatatableObj = $('#SurfaceFinishList').DataTable({
        "columns": [
            {
                "orderable": false,
                "width": "10%"
            },
            {
                "width": "30%"
            },
            {
                "width": "20%"
            },
            {
                "orderable": false,
                "width": "20%"
            },
            {
                "width": "20%"
            },
        ],
        paging: true,
        lengthChange: false,
        searching: true,
        order: [],
        info: true,
        autoWidth: false,
        "oLanguage": {
            "sEmptyTable": "No data available in table"
        },
        "columnDefs": [
            {
                "targets": 0,
                "orderable": false
            },
            {
                "targets": 4,
                "orderable": false
            },
        ],
    });
    $("#SurfaceFinishList_filter input").attr('placeholder', 'Search...').focus();
}