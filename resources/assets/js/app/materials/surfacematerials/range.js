let notificationTimeout = 10000, notificationTimeoutID, DatatableObj, addSurfaceRangeFormValidator, editSurfaceRangeFormValidator, vueInstance;
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
    el: "#SurfaceRangePage",
    data: {
        SurfaceRange: [],
        StoreSurfaceRangeRoute: StoreSurfaceRangeRoute,
        ShowSaveSurfaceRangeLoader: false,
        ShowUpdateSurfaceRangeLoader: false,
        currentSurfaceRangeId: null,
        FormOverLay: true, // Toggle for notification loader
        NotificationIcon: "",
        NotificationMessage: "", //Message for notification loader

    },
    components: {
        'overlay-notification': OverlayNotification
    },
    created() {
        this.SurfaceRange = SurfaceRange;
    },
    computed: {
        filteredSurfaceRange: function () {
            return _.orderBy(this.SurfaceRange, ["CreatedAt"],["desc"]);
        },
        selectedSurfaceRangeData: function () {
            if (this.currentSurfaceRangeId) {
                return _.find(this.SurfaceRange, function (range) {
                    return range.Id === this.currentSurfaceRangeId;
                }.bind(this));
            } else {
                return {};
            }
        }
    },
    methods: {
        addSurfaceRange: function () {
              $("#AddSurfaceRangeModal").modal({
                show: true
            });
        },
        OnsubmitAddSurfaceRangeForm() {
            addSurfaceRangeFormValidator = $("#AddSurfaceRangeForm").validate({
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
                    Name: {
                        required: "SurfaceRange can't be blank."
                    },
                    Status: {
                        required: "Status can't be blank."
                    }
                },
                submitHandler: function (form, event) {
                    event.preventDefault();
                    $("#AddSurfaceRangeFormSubmitBtn").trigger('blur');
                    vueInstance.ShowSaveSurfaceRangeLoader = true;
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
                        vueInstance.onFail(error, addSurfaceRangeFormValidator);
                    })
                    .always(function () {
                        vueInstance.ShowSaveSurfaceRangeLoader = false;
                        $("#AddSurfaceRangeFormSubmitBtn").trigger('blur');
                    });
                }
            });
        },
        OnsubmitEditSurfaceRangeForm() {
            editSurfaceRangeFormValidator = $("#EditSurfaceRangeForm").validate({
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
                    EditSurfaceRangeName: {
                        required: true,
                        ValidateAlphabet: true,
                        CheckConsecutiveSpaces: true,
                        minlength: 3,
                        maxlength: 255
                    },
                    EditSurfaceRangeStatus: {
                        required: true
                    }
                },
                messages: {
                    EditSurfaceRangeName: {
                        required: "SurfaceRange can't be blank."
                    },
                    EditSurfaceRangeStatus: {
                        required: "Status can't be blank."
                    }
                },
                submitHandler: function (form, event) {
                    event.preventDefault();
                    $("#EditSurfaceRangeFormSubmitBtn").trigger('blur');
                    vueInstance.ShowUpdateSurfaceRangeLoader = true;
                    let formData = new FormData(form);
                    let rangeId = vueInstance.currentSurfaceRangeId;
                    let data = {
                        name: $("#EditSurfaceRangeName").val(),
                        status: ($("#EditSurfaceRangeForm input.input-radio:checked").val() === "Active") ? true : false
                    };$.ajax({
                        url:  form.action + "/" + rangeId,
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
                        console.log("fail ");
                        vueInstance.onFail(error, editSurfaceRangeFormValidator);
                    })
                    .always(function () {
                        vueInstance.ShowUpdateSurfaceRangeLoader = false;
                        $("#EditSurfaceRangeFormSubmitBtn").trigger('blur');
                    });
                }
            });
        },
        onSuccess(response, isAddForm = true, data = null) {
                if (isAddForm) {
                // Post submit add form code goes here...
                let status = ($("#AddSurfaceRangeForm input.input-radio:checked").val() === "Active") ? true : false;
                vueInstance.SurfaceRange.push({
                    Id: response.rangeId,
                    Name: $("#Name").val(),
                    IsActive: status
                });
                $("#AddSurfaceRangeForm").trigger('reset');
            } else {
                this.selectedSurfaceRangeData.Name = data.name;
                this.selectedSurfaceRangeData.IsActive = data.status;
            }

            this.populateNotifications(response);
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
                console.error(jqXHR.responseText);
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
        editSurfaceRange(rangeId) {
            if (rangeId) {
                vueInstance.currentSurfaceRangeId = rangeId;
                $("#EditSurfaceRangeModal").modal({
                    show: true
                });
            } else {
                vueInstance.currentSurfaceRangeId = null;
            }
        },
        viewSurfaceRange(rangeId) {
            if (rangeId) {
                vueInstance.currentSurfaceRangeId = rangeId;
                $("#ViewSurfaceRangeModal").modal({
                    show: true
                });
            } else {
                vueInstance.currentSurfaceRangeId = null;
            }
        },
       
    }
});

$(document).ready(function () {
    
    //Reset Form on Modal Close
    $('#AddSurfaceRangeModal').on('hidden.bs.modal', function () {
        $("#AddSurfaceRangeForm").trigger('reset');
        addSurfaceRangeFormValidator.resetForm();
        vueInstance.clearOverLayMessage();
    });
    
    $('#EditSurfaceRangeModal').on('hidden.bs.modal', function () {
        editSurfaceRangeFormValidator.resetForm();
        vueInstance.currentSurfaceRangeId = null;
        vueInstance.clearOverLayMessage();
    });
    $("#ViewSurfaceRangeModal").on('hidden.bs.modal',function(){
        vueInstance.currentSurfaceRangeId = null;
    });
    
    vueInstance.OnsubmitAddSurfaceRangeForm();
    vueInstance.OnsubmitEditSurfaceRangeForm();
    initializeDataTable();
});

// DataTable initialization
function initializeDataTable() {
    // DataTable initialization
    DatatableObj = $('#SurfaceRangeList').DataTable({
         "columns": [
            {
                "orderable": false,
                "width": "8%"
            },
            {
                "width": "62%"
            },
            {
                "orderable": false,
                "width": "15%"
            },
            {
               "width": "15%"
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
                "targets": 3,
                "orderable": false
            },
             
        ],
    });
    $("#SurfaceRangeList_filter input").attr('placeholder', 'Search...').focus();
}