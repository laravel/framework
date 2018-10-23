let notificationTimeout = 10000, notificationTimeoutID, addSubBrandFormValidator, editSubBrandFormValidator, vueInstance;
let alertSkeleton = `
    <div class="alert alert-dismissible hidden">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <p class="body"></p>
    </div>
`;

/** include needed packages **/
require('../../bootstrap');
require('select2');
var jquery = require('jquery');

/** Import Vue table package **/
let VueTables = require('vue-tables-2');
Vue.use(VueTables.ClientTable);

import createSubBrand from '../../components/material/CreateSubBrandModal';
import updateSubBrand from '../../components/material/UpdateSubBrandModal'
import viewSubBrand from '../../components/material/VueSubBrandModal';

let modelVariables = {
    SubBrands: [],
    brands: Brands,
    StoreSubBrandRoute: StoreBrandRoute,
    UpdateBrandRoute: UpdateBrandRoute,
    ShowSaveBrandLoader: true,
    ShowUpdateBrandLoader: true,
    currentSubBrandId: null,
    currentSubBrandIndex: null,
    columns: ['Id', 'Name', 'BrandId', 'Description', 'IsActive', 'Action'],
    options: {
        headings: {
            Id: '#',
            Name: 'Sub Brand Name',
            BrandId: 'Brand Name',
            IsActive: 'Status'
        },
        texts: {
            filterPlaceholder: "Search...",
            noResults: "No matching records found."
        },
        columnsClasses: {
            'Id': 'subbrand-id',
            'Name': 'subbrand-name',
            'Description': 'subbrand-desc',
            'BrandId': 'brand-name',
            'IsActive': 'subbrand-status',
            'Action': 'subbrand-action'
        },
        filterable: ['Name', 'Description'],
        sortable: ['Name', 'Description', 'BrandId']
    }
};
        
vueInstance = new Vue({
    el: "#SubBrandsPage",
    data: modelVariables,
    components: {
        'create-subbrand-popup': createSubBrand,
        'update-subbrand-popup': updateSubBrand,
        'view-brand-popup': viewSubBrand
    },
    created() {
        this.SubBrands = SubBrands;
    },
    computed: {
        filteredSubBrands: function () {
            return _.sortBy(this.SubBrands, ["Name"]);
        },
        selectedBrandData: function () {
            if (this.currentSubBrandId) {
                return _.find(this.SubBrands, function (brand) {
                    return brand.Id === this.currentSubBrandId;
                }.bind(this));
            } else {
                return {};
            }
        }
    },
    methods: {
        addSubBrand: function () {
            $("#AddSubBrandModal").modal({
                show: true
            });
        },
        getBrand(brandId) {
            if (brandId) {
                if (this.brands.length > 0) {
                    let brand = _.find(this.brands, ["Id", brandId]);
                    if (!_.isUndefined(brand)) {
                        return brand.Name;
                    }
                }
            }
            return '<small>N/A</small>';
        },
        submitAddSubBrandForm() {
            addSubBrandFormValidator = $("#AddSubBrandForm").validate({
                ignore: [],
                onkeyup: function (element, event) {
                    if (this.invalid.hasOwnProperty(element.name)) {
                        $(element).valid();
                    }
                },
                errorClass: "help-block text-danger",
                errorElement: "span",
                highlight: function (element, errorClass) {
                    if (element.id === "Brand") {
                        jquery(element).next('span.select2').find(".select2-selection").addClass("select2-selection-error").closest('.form-group').addClass('has-error');
                    } else {
                        $(element).closest('.form-group').addClass("has-error");
                    }
                },
                unhighlight: function (element, errorClass) {
                    if (element.id === "Brand") {
                        jquery(element).next('span.select2').find(".select2-selection").removeClass("select2-selection-error").closest('.form-group').removeClass('has-error');
                    } else {
                        $(element).closest('.form-group').removeClass("has-error");
                    }
                },
                errorPlacement: function (error, element) {
                    if (element.id === "Brand") {
                        error.insertAfter(jquery(element).next("span.select2"));
                    } else {
                        error.appendTo($(element).parent());
                    }
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
                    Brand: {
                        required: true
                    },
                    Status: {
                        required: true
                    }
                },
                messages: {
                    Name: {
                        required: "Name can't be blank."
                    },
                    Description: {
                        maxlength: "Maximum 255 characters are allowed in Description."
                    },
                    Brand: {
                        required: "Brand can't be blank."
                    },
                    Status: {
                        required: "Status can't be blank."
                    }
                },
                submitHandler: function (form, event) {
                    event.preventDefault();
                    $("#AddSubBrandFormSubmitBtn").trigger('blur');
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
                        vueInstance.onSuccess(response, "AddSubBrandFormNotificationArea", true);
                    })
                    .fail(function (error) {
                        vueInstance.onFail(error, "AddSubBrandFormNotificationArea", addSubBrandFormValidator);
                    })
                    .always(function () {
                        vueInstance.ShowSaveBrandLoader = true;
                        $("#AddSubBrandFormSubmitBtn").trigger('blur');
                    });
                }
            });
        },
        submitEditBrandForm() {
            editSubBrandFormValidator = $("#EditBrandForm").validate({
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
                    EditBrand: {
                        required: true
                    },
                    EditBrandStatus: {
                        required: true
                    }
                },
                messages: {
                    EditBrandName: {
                        required: "Name can't be blank."
                    },
                    EditBrandDescription: {
                        maxlength: "Maximum 255 characters are allowed in Description."
                    },
                    EditBrand: {
                        required: "Brand can't be blank."
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
                        url:  form.action,
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
                        vueInstance.onFail(error, "EditBrandFormNotificationArea", editSubBrandFormValidator);
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
                    let status = ($("#AddSubBrandForm input.input-radio:checked").val() === "Active") ? true : false;
                    vueInstance.SubBrands.push({
                        Id: response.data.subrand.id,
                        Name: $("#Name").val(),
                        Description: $("#Description").val(),
                        BrandId: $("#Brand").val(),
                        IsActive: status
                    });
                    $("#AddSubBrandForm").trigger('reset');
                    jquery('#Brand').val(null).trigger('change');
                    addSubBrandFormValidator.resetForm();
                } else {
                    // Get brand
                    let brand = _.find(vueInstance.SubBrands, function (brand) {
                        return brand.Id === vueInstance.currentSubBrandId;
                    });
                    brand.Name = $("#EditBrandName").val();
                    brand.Description = $("#EditBrandDescription").val();
                    brand.BrandId = $("#EditBrand").val();
                    brand.IsActive = brandStatus;
                }
            }
        },
        onFail(jqXHR, notificationArea = "AddSubBrandFormNotificationArea", validator) {
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
                vueInstance.currentSubBrandId = brandId;
                $("#EditSubBrandModal").modal({
                    show: true
                });
                this.$nextTick(function () {
                    this.initiailiseSelect2("EditBrand");
                });
            } else {
                vueInstance.currentSubBrandId = null;
            }
        },
        viewBrand(rowIndex, brandId) {
            if (brandId) {
                vueInstance.currentSubBrandId = brandId;
                vueInstance.currentSubBrandIndex = rowIndex;
                $("#ViewBrandModal").modal({
                    show: true
                });
            } else {
                vueInstance.currentSubBrandId = null;
            }
        },
        initiailiseSelect2(elemId) {
            jquery('#'+elemId).select2({
                placeholder: "Select a Brand",
                language: {
                    noResults: function () {
                        return "No brands found.";
                    }
                }
            }).next("span.select2").css({
                display: 'block',
                width: '100%'
            });
        }
    }
});

$(document).ready(function () {
    vueInstance.initiailiseSelect2("Brand");
    vueInstance.submitAddSubBrandForm();
    vueInstance.submitEditBrandForm();
    jquery("#Brand").on('change', function () {
        $(this).valid();
        $(this).next('span.select2').find(".select2-selection").removeClass("select2-selection-error").closest('.form-group').removeClass('has-error');
    });
    $("#ViewBrandModal").on('hidden.bs.modal', function (event) {
        vueInstance.currentSubBrandId = null;
    });
    $('#EditSubBrandModal').on('hidden.bs.modal', function (event) {
        editSubBrandFormValidator.resetForm();
        $(".notification-area").addClass('hidden');
        vueInstance.currentSubBrandId = null;
    });
    $("#AddSubBrandModal").on('hidden.bs.modal', function (event) {
        jquery('#Brand').val(null).trigger('change');
        $("#AddSubBrandForm").trigger('reset');
        addSubBrandFormValidator.resetForm();
        $(".notification-area").addClass('hidden');
        vueInstance.$nextTick(() => $(".VueTables__search-field input").attr('placeholder', 'Search...').focus());
    });
});