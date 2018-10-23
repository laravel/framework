let notificationTimeout = 10000, notificationTimeoutID, addBrandFormValidator, editBrandFormValidator, vueInstance;
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

import createBrand from '../../components/material/CreateBrandModal';
import updateBrand from '../../components/material/UpdateBrandModal';
import viewBrand from '../../components/material/ViewBrandModal'

vueInstance = new Vue({
    el: "#BrandsPage",
    data: {
        brands: [],
        StoreBrandUrl: StoreBrandRoute,
        UpdateBrandUrl: UpdateBrandRoute,
        ShowSaveBrandLoader: true,
        ShowUpdateBrandLoader: true,
        currentBrandId: null,
        currentBrandIndex: null,
        columns: ['Id', 'Name', 'Description', 'IsActive', 'Action'],
        options: {
            headings: {
                Id: '#',
                Name: 'Brand Name',
                IsActive: 'Status'
            },
            texts: {
               filterPlaceholder:"Search...",
               noResults:"No matching records found."
            },
            columnsClasses: {
                'Id': 'brand-id',
                'Name': 'brand-name',
                'Description': 'brand-desc',
                'IsActive': 'brand-status',
                'Action': 'brand-action'
            },
            filterable: ['Name', 'Description'],
            sortable: ['Name', 'Description']
        }
    },
    components: {
        'create-brand-popup': createBrand,
        'update-brand-popup': updateBrand,
        'view-brand-popup': viewBrand
    },
    created() {
        this.brands = Brands;
    },
    computed: {
        filteredBrands: function () {
            return _.sortBy(this.brands, ["Name"]);
        },
        selectedBrandData: function () {
            if (this.currentBrandId) {
                return _.find(this.brands, function (brand) {
                    return brand.Id === this.currentBrandId;
                }.bind(this));
            } else {
                return {};
            }
        }
    },
    methods: {
        addBrand: function () {
            $("#AddBrandModal").modal({
                show: true
            });
        },
        getDescription(description) {
            return (description) ? description : '<small>N/A</small>';
        },
        submitaAddBrandForm() {
            addBrandFormValidator = $("#AddBrandForm").validate({
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
                        minlength: 3
                    },
                    Description: {
                        CheckConsecutiveSpaces: true,
                        maxlength: 255
                    },
                    Status: {
                        required: true
                    }
                },
                messages: {
                    Name: {
                        required: "Brand can't be blank."
                    },
                    Description: {
                        maxlength: "Maximum 255 characters are allowed in Description."
                    },
                    Status: {
                        required: "Status can't be blank."
                    }
                },
                submitHandler: function (form, event) {
                    event.preventDefault();
                    $("#AddBrandFormSubmitBtn").trigger('blur');
                    vueInstance.ShowSaveBrandLoader = false;
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
                        vueInstance.onSuccess(response, "AddBrandFormNotificationArea", true);
                    })
                    .fail(function (error) {
                        vueInstance.onFail(error, "AddBrandFormNotificationArea", addBrandFormValidator);
                    })
                    .always(function () {
                        vueInstance.ShowSaveBrandLoader = true;
                        $("#AddBrandFormSubmitBtn").trigger('blur');
                    });
                }
            });
        },
        submitaEditBrandForm() {
            editBrandFormValidator = $("#EditBrandForm").validate({
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
                    EditBrandName: {
                        required: true,
                        minlength: 3
                    },
                    EditBrandDescription: {
                        CheckConsecutiveSpaces: true,
                        maxlength: 255
                    },
                    EditBrandStatus: {
                        required: true
                    }
                },
                messages: {
                    EditBrandName: {
                        required: "Brand can't be blank."
                    },
                    EditBrandDescription: {
                        maxlength: "Maximum 255 characters are allowed in Description."
                    },
                    EditBrandStatus: {
                        required: "Status can't be blank."
                    }
                },
                submitHandler: function (form, event) {
                    event.preventDefault();
                    $("#EditBrandFormSubmitBtn").trigger('blur');
                    vueInstance.ShowUpdateBrandLoader = false;
                    $(".alert").addClass('hidden');
                    let formData = new FormData(form);
                    let status = ($("#EditBrandForm input.input-radio:checked").val() === "Active") ? true : false;
                    $.ajax({
                        url: form.action,
                        type: 'POST',
                        dataType: 'json',
                        data: formData,
                        processData: false,
                        contentType: false
                    })
                    .done(function (response) {
                        vueInstance.onSuccess(response, "EditBrandFormNotificationArea", false, status);
                    })
                    .fail(function (error) {
                        vueInstance.onFail(error, "EditBrandFormNotificationArea", editBrandFormValidator);
                    })
                    .always(function () {
                        vueInstance.ShowUpdateBrandLoader = true;
                        $("#EditBrandFormSubmitBtn").trigger('blur');
                    });
                }
            });
        },
        onSuccess(response, notificationArea, isAddForm = true, brandStatus = false) {
            vueInstance.populateNotifications(response, "alert", notificationArea);
            if (response.status === "success") {
                if (isAddForm) {
                    // Post submit add form code goes here...
                    let status = ($("#AddBrandForm input.input-radio:checked").val() === "Active") ? true : false;
                    vueInstance.brands.push({
                        Id: response.data.brand.id,
                        Name: $("#Name").val(),
                        Description: $("#Description").val(),
                        IsActive: status
                    });
                    $("#AddBrandForm").trigger('reset');
                } else {
                    // Get brand
                    let brand = _.find(vueInstance.brands, function (brand) {
                        return brand.Id === vueInstance.currentBrandId;
                    });
                    brand.Name = $("#EditBrandName").val();
                    brand.Description = $("#EditBrandDescription").val();
                    brand.IsActive = brandStatus;
                }
            }
        },
        onFail(jqXHR, notificationArea = "AddBrandFormNotificationArea", validator) {
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
        editBrand(brandId) {
            if (brandId) {
                vueInstance.currentBrandId = brandId;
                $("#EditBrandModal").modal({
                    show: true
                });
            } else {
                vueInstance.currentBrandId = null;
            }
        },
        viewBrand(rowIndex, brandId) {
            if (brandId) {
                vueInstance.currentBrandId = brandId;
                vueInstance.currentBrandIndex = rowIndex;
                $("#ViewBrandModal").modal({
                    show: true
                });
            } else {
                vueInstance.currentBrandId = null;
            }
        },
        getUpdateBrandUrl() {
           return (this.currentBrandId) ? (this.UpdateBrandUrl+'/'+this.currentBrandId) : this.UpdateBrandUrl;
        }
    }
});

$(document).ready(function () {
    vueInstance.submitaAddBrandForm();
    vueInstance.submitaEditBrandForm();
    $("#ViewBrandModal").on('hidden.bs.modal', function (event) {
        vueInstance.currentBrandId = null;
    });
    $('#EditBrandModal').on('hidden.bs.modal', function (event) {
        editBrandFormValidator.resetForm();
        $(".notification-area").addClass('hidden');
        vueInstance.currentBrandId = null;
    });
    $("#AddBrandModal").on('hidden.bs.modal', function (event) {
        $("#AddBrandForm").trigger('reset');
        addBrandFormValidator.resetForm();
        $(".notification-area").addClass('hidden');
    });
});