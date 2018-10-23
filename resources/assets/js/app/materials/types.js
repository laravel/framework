
let vueInstance, addTypeFormValidator, editTypeFormValidator;

/** include needed packages **/
require('../../bootstrap');
require('select2');
var jquery = require('jquery');

/** Import Vue table package **/
let VueTables = require('vue-tables-2');
Vue.use(VueTables.ClientTable);

import createType from '../../components/material/CreateMaterialTypeModal';
import updateType from '../../components/material/UpdateMaterialTypeModal';
import OverlayNotification from '../../components/overlayNotification';
import viewType from '../../components/material/ViewMaterialTypeModal';

let modelVariables = {
    FormCategories: Categories,
    MaterialTypes: [],
    CreateTypeRoute: StoreTypesRoute,
    UpdateTypeRoute: UpdateTypeRoute,
    ShowSaveTypeLoader: true,
    ShowUpdateTypeLoader: true,
    CurrentTypeId: null,
    currentTypeIndex: null,
    columns: ['Id', 'Name', 'Description', 'FormCategoryId', 'IsActive', 'Action'],
    options: {
        headings: {
            Id: '#',
            FormCategoryId: 'Category',
            IsActive: 'Status'
        },
        texts: {
            filterPlaceholder: "Search...",
            noResults: "No matching records found."
        },
        columnsClasses: {
            'Id': 'type-id',
            'Name': 'type-name',
            'Description': 'type-desc',
            'FormCategoryId': 'type-category',
            'IsActive': 'type-status',
            'Action': 'type-action'
        },
        filterable: ['Name', 'Description'],
        sortable: ['Name', 'Description', 'FormCategoryId']
    },
    FormOverLay: true,
    UpdateFormOverLay: true,
    NotificationIcon: "",
    NotificationMessage: ""
};
    
vueInstance = new Vue({
    el: "#MaterialTypesPage",
    data: modelVariables,
    created() {
      this.MaterialTypes = MaterialTypes;  
    },
    components: {
        'create-type-popup': createType,
        'update-type-popup': updateType,
        'overlay-notification': OverlayNotification,
        'view-type-popup': viewType
    },
    computed: {
        filteredTypes: function () {
            return _.sortBy(this.MaterialTypes, ["Name"]);
        },
        selectedType: function () {
            if (this.CurrentTypeId) {
                return _.find(this.MaterialTypes, function (type) {
                    return type.Id === this.CurrentTypeId;
                }.bind(this));
            } else {
                return {};
            }
        }
    },
    methods: {
        addType() {
            $("#AddTypeModal").modal({
                show: true
            });
        },
        editType(typeId) {
            if (typeId) {
                vueInstance.CurrentTypeId = typeId;
                $("#EditTypeModal").modal({
                    show: true
                });
                this.$nextTick(function () {
                    this.initiailiseSelect2("EditCategory");
                });
            } else {
                vueInstance.CurrentTypeId = null;
            }
        },
        viewType(rowIndex, typeId) {
            if (typeId) {
                vueInstance.CurrentTypeId = typeId;
                vueInstance.currentTypeIndex = rowIndex;
                $("#ViewTypeModal").modal({
                    show: true
                });
            } else {
                vueInstance.CurrentTypeId = null;
            }
        },
        getCategory(categoryId) {
            if (categoryId) {
                if (this.FormCategories.length > 0) {
                    let catagory = _.find(this.FormCategories, ["Id", categoryId]);
                    if (!_.isUndefined(catagory)) {
                        return catagory.Name;
                    }
                }
            }
            return '<small>N/A</small>';
        },
        submitAddTypeForm() {
            addTypeFormValidator = $("#AddTypeForm").validate({
                ignore: [],
                onkeyup: function (element, event) {
                    if (this.invalid.hasOwnProperty(element.name)) {
                        $(element).valid();
                    }
                },
                errorClass: "help-block text-danger",
                errorElement: "span",
                highlight: function (element, errorClass) {
                    if (element.id === "Category") {
                        jquery(element).next('span.select2').find(".select2-selection").addClass("select2-selection-error").closest('.form-group').addClass('has-error');
                    } else {
                        $(element).closest('.form-group').addClass("has-error");
                    }
                },
                unhighlight: function (element, errorClass) {
                    if (element.id === "Category") {
                        jquery(element).next('span.select2').find(".select2-selection").removeClass("select2-selection-error").closest('.form-group').removeClass('has-error');
                    } else {
                        $(element).closest('.form-group').removeClass("has-error");
                    }
                },
                errorPlacement: function (error, element) {
                    if (element.id === "Category") {
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
                    Category: {
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
                    Category: {
                        required: "Category can't be blank."
                    },
                    Status: {
                        required: "Status can't be blank."
                    }
                },
                submitHandler: function (form, event) {
                    event.preventDefault();
                    $("#AddTypeFormSubmitBtn").trigger('blur');
                    vueInstance.ShowSaveTypeLoader = false;
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
                        vueInstance.onSuccess(response, true);
                    })
                    .fail(function (error) {
                        vueInstance.onFail(error, addTypeFormValidator);
                    })
                    .always(function () {
                        vueInstance.ShowSaveTypeLoader = true;
                        $("#AddTypeFormSubmitBtn").trigger('blur');
                    });
                }
            });
        },
        submitEditTypeForm() {
            editTypeFormValidator = $("#EditTypeForm").validate({
                ignore: [],
                onkeyup: function (element, event) {
                    if (this.invalid.hasOwnProperty(element.name)) {
                        $(element).valid();
                    }
                },
                errorClass: "help-block text-danger",
                errorElement: "span",
                highlight: function (element, errorClass) {
                    if (element.id === "EditCategory") {
                        jquery(element).next('span.select2').find(".select2-selection").addClass("select2-selection-error").closest('.form-group').addClass('has-error');
                    } else {
                        $(element).closest('.form-group').addClass("has-error");
                    }
                },
                unhighlight: function (element, errorClass) {
                    if (element.id === "EditCategory") {
                        jquery(element).next('span.select2').find(".select2-selection").removeClass("select2-selection-error").closest('.form-group').removeClass('has-error');
                    } else {
                        $(element).closest('.form-group').removeClass("has-error");
                    }
                },
                errorPlacement: function (error, element) {
                    if (element.id === "EditCategory") {
                        error.insertAfter(jquery(element).next("span.select2"));
                    } else {
                        error.appendTo($(element).parent());
                    }
                },
                rules: {
                    EditType: {
                        required: true,
                        minlength: 3
                    },
                    EditDescription: {
                        CheckConsecutiveSpaces: true,
                        maxlength: 255
                    },
                    EditCategory: {
                        required: true
                    },
                    EditStatus: {
                        required: true
                    }
                },
                messages: {
                    EditType: {
                        required: "Name can't be blank."
                    },
                    EditDescription: {
                        maxlength: "Maximum 255 characters are allowed in Description."
                    },
                    EditCategory: {
                        required: "Category can't be blank."
                    },
                    EditStatus: {
                        required: "Status can't be blank."
                    }
                },
                submitHandler: function (form, event) {
                    event.preventDefault();
                    $("#EditTypeFormSubmitBtn").trigger('blur');
                    vueInstance.ShowUpdateTypeLoader = false;
                    $(".alert").addClass('hidden');
                    let formData = new FormData(form);
                    let status = ($("#EditTypeForm input.input-radio:checked").val() === "Active") ? true : false;
                    $.ajax({
                        url:  form.action,
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
                        vueInstance.onFail(error, editTypeFormValidator, false);
                    })
                    .always(function () {
                        vueInstance.ShowUpdateTypeLoader = true;
                        $("#EditTypeFormSubmitBtn").trigger('blur');
                    });
                }
            });
        },
        onSuccess(response, isAddForm = true, brandStatus = false) {
            this.populateOverlayMessage(response, isAddForm);
            if (response.status === "success") {
                if (isAddForm) {
                    // Post submit add form code goes here...
                    let status = ($("#AddTypeForm input.input-radio:checked").val() === "Active") ? true : false;
                    vueInstance.MaterialTypes.push({
                        Id: response.type.id,
                        Name: $("#Name").val(),
                        Description: $("#Description").val(),
                        FormCategoryId: $("#Category").val(),
                        IsActive: status
                    });
                    $("#AddTypeForm").trigger('reset');
                    jquery('#Category').val(null).trigger('change');
                    addTypeFormValidator.resetForm();
                } else {
                    // Get category
                    let category = _.find(vueInstance.MaterialTypes, function (brand) {
                        return brand.Id === vueInstance.CurrentTypeId;
                    });
                    category.Name = $("#EditType").val();
                    category.Description = $("#EditDescription").val();
                    category.FormCategoryId = $("#EditCategory").val();
                    category.IsActive = brandStatus;
                }
            }
        },
        onFail(jqXHR, validator, isAddForm = true) {
            if (jqXHR.status === 422) {
                var response = JSON.parse(jqXHR.responseText);
                vueInstance.populateFormErrors(response.data.errors, validator);
            } else if (jqXHR.status === 500) {
                this.populateOverlayMessage({status: "error", message: AlertData["10077"]}, isAddForm);
            } else {
                this.populateOverlayMessage({status: "error", message: AlertData["10077"]}, isAddForm);
                console.error(jqXHR.responseText);
            }
        },
        populateOverlayMessage(response, isAddForm = true)
        {
            this.NotificationMessage = response.message;
            this.NotificationIcon = response.status;
            (isAddForm) ? this.FormOverLay = false: this.UpdateFormOverLay = false; 
            if (response.status === "success") {     
                this.NotificationIcon = "check-circle";
            } else if (response.status === 'error') {
                this.NotificationIcon = "ban";
            }
        },
        clearOverLayMessage(isAddForm = true)
        {
            (isAddForm) ? this.FormOverLay = true: this.UpdateFormOverLay = true; 
            this.UpdateFormOverLay = true;
            this.NotificationMessage = "";
            this.NotificationIcon = "";
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
        initiailiseSelect2(elemId) {
            jquery('#'+elemId).select2({
                placeholder: "Select a Category",
                language: {
                    noResults: function () {
                        return "No category found.";
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
    vueInstance.initiailiseSelect2("Category");
    jquery("#Category").on('change', function () {
        $(this).valid();
        $(this).next('span.select2').find(".select2-selection").removeClass("select2-selection-error").closest('.form-group').removeClass('has-error');
    });
    vueInstance.submitAddTypeForm();
    vueInstance.submitEditTypeForm();
    $("#AddTypeModal").on('hidden.bs.modal', function (event) {
        jquery('#Category').val(null).trigger('change');    
        $("#AddTypeForm").trigger('reset');
        addTypeFormValidator.resetForm();
        vueInstance.clearOverLayMessage();
        vueInstance.$nextTick(() => $(".VueTables__search-field input").attr('placeholder', 'Search...').focus());
    });
    $('#EditTypeModal').on('hidden.bs.modal', function (event) {
        editTypeFormValidator.resetForm();
        vueInstance.clearOverLayMessage(false);
        vueInstance.CurrentTypeId = null;
        vueInstance.$nextTick(() => $(".VueTables__search-field input").attr('placeholder', 'Search...').focus());
    });
    $("#ViewTypeModal").on('hidden.bs.modal', function (event) {
        vueInstance.CurrentTypeId = null;
    });
});