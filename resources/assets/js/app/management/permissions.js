let notificationTimeout = 10000, notificationTimeoutID, addPermissionFormValidator, editPermissionFormValidator, vueInstance;
let alertSkeleton = `
    <div class="alert alert-dismissible hidden">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <p class="body"></p>
    </div>
`;
/** include needed packages **/
require('../../bootstrap');

/** Import Vue table package **/
let VueTables = require('vue-tables-2');
Vue.use(VueTables.ClientTable);

var rules = {
    Title: {
        required: true,
        minlength: 3
    },
    Slug: {
        required: true,
        minlength: 3,
        remote: {
            url: '/permissions/checkSlug',
            type: 'POST',
            async: false,
            data: {
                ItemName: function () {
                    return $("#Slug").val();
                }
            },
            statusCode: {
                500: function () {
                    vueInstance.populateNotifications({
                        status: "error",
                        message: AlertData["10077"]
                    }, "alert", "AddPermissionFormNotificationArea");
                }
            },
            success: function (ResponseJSON) {
                vueInstance.PopulateRemoteError(ResponseJSON, "Slug",addPermissionFormValidator);
            }
        }
    },
    Description: {
        minlength: 3,
        maxlength: 255
    },
    Status: {
        required: true
    }
};
var messages = {
    Title: {
        required: "Title can't be blank."
    },
    Slug: {
        required: "Slug can't be blank."
    },
    Description: {
        maxlength: "Maximum 255 characters are allowed in Description."
    },
    Status: {
        required: "Status can't be blank."
    }
};

Vue.component('permission-description', {
    props: ['data', 'index', 'column'],
    template: `<span v-if="data.Description">{{data.Description}}</span><span v-else><small>N/A</small></span>`,

});
Vue.component('permission-status', {
    props: ['data', 'index', 'column'],
    template: `<span  class="label label-success" v-if="data.IsActive">Active</span><span class="label label-danger" v-else>InActive</span>`,

});
Vue.component('permission-action', {
    props: ['data', 'index', 'column'],
    template: `<div><a class="btn btn-custom btn-edit btn-sm mr-rt-3" data-toggle="tooltip" data-original-title="Edit Permission" role="button" @click.prevent="editPermission(data.Id)"><span class="glyphicon glyphicon-pencil btn-edit"></span></a><a class="btn btn-custom btn-edit btn-sm" data-toggle="tooltip" data-original-title="View Permission" role="button" @click.prevent="viewPermission(data.Id)"><span class="glyphicon glyphicon-eye-open btn-edit"></span></a></div>`,
    methods: {
        viewPermission(id) {
            if (id) {
                this.$root.currentPermissionId = id;
                $("#ViewPermissionModal").modal({
                    show: true
                });
            } else {
                this.$root.currentPermissionId = null;
            }
        },
        editPermission(id) {
            if (id) {
                this.$root.currentPermissionId = id;
                $("#EditPermissionModal").modal({
                    show: true
                });
            } else {
                this.$root.currentPermissionId = null;
            }
            this.$emit("editPermission", id);
        }
    },

});

import createPermission from '../../components/management/CreatePermissionModal';
import updatePermission from '../../components/management/UpdatePermissionModal';
import viewPermission from '../../components/management/ViewPermissionModal';
var dataVariables = {
    IDProPermissions: [],
    StorePermissionUrl: StorePermissionRoute,
    UpdatePermissionUrl: UpdatePermissionRoute,
    ShowSavePermissionLoader: true,
    ShowUpdatePermissionLoader: true,
    currentPermissionId: null,
    columns: ['Id', 'Title', 'Slug', 'Description', 'IsActive', 'Action'],
    options: {
        headings: {
            Id: '#',
            IsActive: 'Status'
        },
        texts: {
            filterPlaceholder: "Search...",
            noResults: "No matching records found."
        },
        columnsClasses: {
            'Id': 'permission-id',
            'Title': 'permission-title',
            'Slug': 'permission-slug',
            'Description': 'permission-desc',
            'IsActive': 'permission-status',
            'Action': 'permission-action'
        },

        templates: {
            Description: 'permission-description',
            IsActive: 'permission-status',
            Action: 'permission-action'
        },
        filterable: ['Title', 'Slug', 'Description'],
        sortable: ['Title', 'Slug', 'Description']
    }
};

vueInstance = new Vue({
    el: "#PermissionsPage",
    data: dataVariables,
    components: {
        'create-permission-popup': createPermission,
        'update-permission-popup': updatePermission,
        'view-permission-popup': viewPermission
    },
    created() {
        this.IDProPermissions = IDProPermission;
    },
    computed: {
        filteredPermissions: function () {
            return _.sortBy(this.IDProPermissions, [permission => permission.Slug.toLowerCase()], ['desc']);
        },
        selectedPermissionData: function () {
            if (this.currentPermissionId) {
                return _.find(this.IDProPermissions, function (permission) {
                    return permission.Id === this.currentPermissionId;
                }.bind(this));
            } else {
                return {};
            }
        }
    },
    methods: {
        addPermission: function () {
            $("#AddPermissionModal").modal({
                show: true
            });
        },

        submitAddPermissionForm() {
            addPermissionFormValidator = $("#AddPermissionForm").validate({
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
                    $("#AddPermissionFormSubmitBtn").trigger('blur');
                    vueInstance.ShowSavePermissionLoader = false;
                    $(".alert").addClass('hidden');
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
                                vueInstance.onSuccess(response, "AddPermissionFormNotificationArea", true);
                            })
                            .fail(function (error) {
                                vueInstance.onFail(error, "AddPermissionFormNotificationArea", addPermissionFormValidator);
                            })
                            .always(function () {
                                vueInstance.ShowSavePermissionLoader = true;
                                $("#AddPermissionFormSubmitBtn").trigger('blur');
                            });
                }
            });
        },
        submitEditPermissionForm() {
            editPermissionFormValidator = $("#EditPermissionForm").validate({
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
                    EditPermissionTitle: {
                        required: true,
                        minlength: 3
                    },
                    EditPermissionSlug: {
                        required: true,
                        minlength: 3
                    },
                    EditPermissionDescription: {
                        minlength: 3,
                        maxlength: 255
                    },
                    EditPermissionStatus: {
                        required: true
                    }
                },
                messages: {
                    EditPermissionTitle: {
                        required: "Title can't be blank."
                    },
                    EditPermissionSlug: {
                        required: "Slug can't be blank."
                    },
                    EditPermissionDescription: {
                        maxlength: "Maximum 255 characters are allowed in Description."
                    },
                    EditPermissionStatus: {
                        required: "Status can't be blank."
                    }
                },
                submitHandler: function (form, event) {
                    event.preventDefault();
                    $("#EditPermissionFormSubmitBtn").trigger('blur');
                    vueInstance.ShowUpdatePermissionLoader = false;
                    $(".alert").addClass('hidden');
                    let formData = new FormData(form);
                    formData.append("Id", vueInstance.currentPermissionId);
                    let status = ($("#EditPermissionForm input.input-radio:checked").val() === "Active") ? true : false;
                    $.ajax({
                        url: form.action,
                        type: 'POST',
                        dataType: 'json',
                        data: formData,
                        processData: false,
                        contentType: false
                    })
                            .done(function (response) {
                                vueInstance.onSuccess(response, "EditPermissionFormNotificationArea", false, status);
                            })
                            .fail(function (error) {
                                vueInstance.onFail(error, "EditPermissionFormNotificationArea", editPermissionFormValidator);
                            })
                            .always(function () {
                                vueInstance.ShowUpdatePermissionLoader = true;
                                $("#EditPermissionFormSubmitBtn").trigger('blur');
                            });
                }
            });
        },
        onSuccess(response, notificationArea, isAddForm = true, permissionStatus = false) {
            vueInstance.populateNotifications(response, "alert", notificationArea);
            if (response.status === "success") {
                if (isAddForm) {
                    // Post submit add form code goes here...
                    let status = ($("#AddPermissionForm input.input-radio:checked").val() === "Active") ? true : false;
                    vueInstance.IDProPermissions.push({
                        Id: response.data.permission.id,
                        Title: $("#Title").val(),
                        Slug: $("#Slug").val(),
                        Description: $("#Description").val(),
                        IsActive: status
                    });
                    $("#AddPermissionForm").trigger('reset');
                } else {
                    // Get Permission
                    let Permission = _.find(vueInstance.IDProPermissions, function (permission) {
                        return permission.Id === vueInstance.currentPermissionId;
                    });
                    Permission.Title = $("#EditPermissionTitle").val();
                    Permission.Slug = $("#EditPermissionSlug").val();
                    Permission.Description = $("#EditPermissionDescription").val();
                    Permission.IsActive = permissionStatus;
                }
        }
        },
        onFail(jqXHR, notificationArea = "AddPermissionFormNotificationArea", validator) {
            if (jqXHR.status === 422) {
                var response = JSON.parse(jqXHR.responseText);
                vueInstance.populateFormErrors(response.data.errors, validator);
            } else if (jqXHR.status === 500) {
                vueInstance.populateNotifications({
                    status: "error",
                    message: AlertData["10077"]
                }, "alert", notificationArea);
            } else {
                vueInstance.populateNotifications({
                    status: "error",
                    message: AlertData["10077"]
                }, "alert", notificationArea);
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
                    message: AlertData["10077"]
                }, "alert", "AddPermissionFormNotificationArea");
            }
        },
        populateNotifications(response, type, areaId) {
            if (type === "alert") {
                if (response.status === "success") {
                    vueInstance.makeAlertNotification(response.data.message, areaId);
                    vueInstance.setNotificationTimer();
                } else if (response.status === "fail") {
                    vueInstance.makeAlertNotification({
                        type: "danger",
                        body: response.data.message
                    }, areaId);
                } else if (response.status === "error") {
                    vueInstance.makeAlertNotification({
                        type: "danger",
                        body: response.message
                    }, areaId);
                } else {
                    console.error("Unknown response given to populateNotifications()");
                }
            } else {
                console.error("Unknown notification type given to populateNotifications()");
            }
        },
        makeAlertNotification(message, areaId) {
            let notificationArea = $("#" + areaId);
            if (notificationArea.find('.alert').length === 0) {
                notificationArea.html(alertSkeleton);
            }
            let alertDiv = notificationArea.find('.alert');
            if (message.type === "danger") {
                alertDiv.find(".close").remove();
            } else {
                if (alertDiv.find(".close").length === 0) {
                    alertDiv.append('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
                }
            }
            alertDiv.find('.body').html(message.body);
            notificationArea.removeClass('hidden');
            alertDiv.removeAttr('class style').addClass('alert alert-' + message.type);
            vueInstance.setNotificationTimer(areaId);
        },
        setNotificationTimer(areaId) {
            if (notificationTimeoutID) {
                clearTimeout(notificationTimeoutID);
            }
            notificationTimeoutID = setTimeout(function () {
                vueInstance.clearNotifications(areaId);
            }, notificationTimeout);
        },
        clearNotifications(areaId) {
            $(".alert").fadeOut("slow", function () {
                $("#" + areaId).addClass('hidden');
            });
        },
        getUpdatePermissionUrl() {
            return (this.currentPermissionId) ? (this.UpdatePermissionUrl + '/' + this.currentPermissionId) : this.UpdatePermissionUrl;
        }
    }
});

$(document).ready(function () {
    $("#EditPermissionSlug").keyup(function (event) {
        $('#EditPermissionSlug').rules('add', {
            remote: {
                url: '/permissions/checkSlug',
                type: 'POST',
                data: {
                    Slug: function () {
                        return $("#EditPermissionSlug").val();
                    },
                    Id: function () {
                        return vueInstance.currentPermissionId;
                    }
                },
                statusCode: {
                    500: function () {
                        vueInstance.populateNotifications({
                            status: "error",
                            message: AlertData["10077"]
                        }, "alert", "AddPermissionFormNotificationArea");
                    }
                },
                success: function (ResponseJSON) {
                    vueInstance.PopulateRemoteError(ResponseJSON, "EditPermissionSlug",editPermissionFormValidator);
                }
            }
        });
    });
    vueInstance.submitAddPermissionForm();
    vueInstance.submitEditPermissionForm();
    $("#ViewPermissionModal").on('hidden.bs.modal', function (event) {
        vueInstance.currentPermissionId = null;
    });
    $('#EditPermissionModal').on('hidden.bs.modal', function (event) {
        editPermissionFormValidator.resetForm();
        $(".notification-area").addClass('hidden');
        vueInstance.currentPermissionId = null;
    });
    $("#AddPermissionModal").on('hidden.bs.modal', function (event) {
        $("#AddPermissionForm").trigger('reset');
        addPermissionFormValidator.resetForm();
        $(".notification-area").addClass('hidden');
    });
});