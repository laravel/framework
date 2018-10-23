let addValidator, editValidator, vueInstance;

var rules = {
    FormCategory: {
        required: true
    },
    Description: {
        required: true,
        CheckConsecutiveSpaces: true,
        minlength: 3,
        maxlength: 255
    },
    Status: {
        required: true
    }
};

var messages = {
    Description: {
        required: "Use can't be blank."
    },
    FormCategory: {
        required: "Category can't be blank."
    },
    Status: {
        required: "Status can't be blank."
    }
};

var dataVariables = {
    uses: [],
    categories: FormCategories,
    StoreRoute: StoreRoute,
    UpdateRoute: UpdateRoute,
    ShowSaveLoader: false,
    ShowUpdateLoader: false,
    currentColorId: null,
    FormOverLay: true,
    NotificationIcon: "",
    NotificationMessage: "",
    columns: ['Id', 'FormCategoryId', 'Description', 'IsActive', 'Action'],
    options: {
        headings: {
            Id: '#',
            FormCategoryId: 'Form Category',
            IsActive: 'Status'
        },
        texts: {
            filterPlaceholder: "Search...",
            noResults: "No matching records found."
        },
        headingsTooltips: {
            Id: "S.No",
            FormCategoryId: "Form Category",
            Description: "Description",
            IsActive: "Active/InActive Status",
            Action: "Action to be taken"
        },
        columnsClasses: {
            'Id': 'color-id',
            'FormCategoryId': 'color-category',
            'Description': 'color-desc',
            'IsActive': 'color-status',
            'Action': 'color-action'
        },
        filterable: ['Name', 'Description'],
        sortable: ['Name', 'Description', 'FormCategoryId']
    }
};

/* include needed packages */
require('../../../bootstrap');
require('select2');
var jquery = require('jquery');

let VueTables = require('vue-tables-2');
Vue.use(VueTables.ClientTable);

/* Register components */
import CreateUse from '../../../components/referencedata/CreateUse';
import EditUse from '../../../components/referencedata/EditUse';

/* Instantiate Vue */
vueInstance = new Vue({
    el: "#EndUsesPage",
    data: dataVariables,
        components: {
        'create-color': CreateUse,
        'edit-color': EditUse
    },
    created() {
        this.uses = uses;
    },
    mounted() {
        this.initialiseSelect2("FormCategory");
    },
    computed: {
        filteredColors() {
            return this.uses;
        },
        selectedColor() {
            if (this.currentColorId) {
                return _.find(this.uses, function (color) {
                    return color.Id === this.currentColorId;
                }.bind(this));
            } else {
                return {};
            }
        }
    },
    methods: {
         /**
         * Returns form category for given id
         *
         * @param { string } categoryId
         * @returns void
         */
        getCategory(categoryId) {
            if (categoryId) {
                if (this.categories.length > 0) {
                    let category = _.find(this.categories, ["Id", categoryId]);
                    if (!_.isUndefined(category)) {
                        return category.Name;
                    }
                }
            }
            return '<small>N/A</small>';
        },
         /**
         * Initialise select element with Select2 library
         *
         * @param { string } elemId
         * @returns void
         */
        initialiseSelect2(elemId) {
            jquery('#'+elemId).select2({
                placeholder: "Select Category",
                language: {
                    noResults: function () {
                        return "No categories found.";
                    }
                }
            });
        },
        /**
         * Open Create Color Modal
         *
         * @param No
         * @returns void
         */
        addColor() {
            $("#AddColorModal").modal({
                show: true
            });
        },
         /**
         * Make new Color create http request
         *
         * @param No
         * @returns void
         */
        OnCreateColorRequest() {
            addValidator = $("#AddColorForm").validate({
                ignore: [],
                onkeyup: function (element, event) {
                    if (this.invalid.hasOwnProperty(element.name)) {
                        $(element).valid();
                    }
                },
                errorClass: "help-block text-danger",
                errorElement: "span",
                highlight: function (element, errorClass) {
                    if (element.id === "FormCategory") {
                        jquery(element).next('span.select2').find(".select2-selection").addClass("select2-selection-error").closest('.form-group').addClass('has-error');
                    } else {
                        $(element).closest('.form-group').addClass("has-error");
                    }
                },
                unhighlight: function (element, errorClass) {
                    if (element.id === "FormCategory") {
                        jquery(element).next('span.select2').find(".select2-selection").removeClass("select2-selection-error").closest('.form-group').removeClass('has-error');
                    } else {
                        $(element).closest('.form-group').removeClass("has-error");
                    }
                },
                errorPlacement: function (error, element) {
                    if (element.id === "FormCategory") {
                        error.insertAfter(jquery(element).next("span.select2"));
                    } else {
                        error.appendTo($(element).parent());
                    }
                },
                rules: rules,
                messages: messages,
                submitHandler: function (form, event) {
                    event.preventDefault();
                    $("#AddColorSubmitBtn").trigger('blur');
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
                        $("#AddColorSubmitBtn").trigger('blur');
                    });
                }
            });
        },
        /**
         * Opens edit Color modal
         *
         * @param {String} colorId
         * @returns void
         */ 
        editColor(colorId) {
            if (colorId) {
                vueInstance.currentColorId = colorId;
                $("#EditColorModal").modal({
                    show: true
                });
                this.$nextTick(function () {
                    this.initialiseSelect2("EditFormCategory");
                });
            } else {
                vueInstance.currentColorId = null;
            }
        },
        /**
         * Update Range request
         *
         * @param No
         * @returns void
         */
        OnUpdateColorRequest() {
            editValidator = $("#EditColorForm").validate({
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
                    $("#EditColorSubmitBtn").trigger('blur');
                    vueInstance.ShowUpdateLoader = true;
                    let formData = new FormData(form);
                    let data = {
                        name: $("#EditName").val(),
                        description: $("#EditDescription").val(),
                        category: $("#EditFormCategory").val(),
                        status: ($("#EditColorForm input.input-radio:checked").val() === "Active") ? true : false
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
                        $("#EditColorSubmitBtn").trigger('blur');
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
                    let status = ($("#AddColorForm input.input-radio:checked").val() === "Active") ? true : false;
                    vueInstance.uses.push({
                        Id: response.colorId,
                        Name: $("#Name").val(),
                        Description: $("#Description").val(),
                        FormCategoryId: $("#FormCategory").val(),
                        IsActive: status
                    });
                    $("#AddColorForm").trigger('reset');
                    jquery("#FormCategory").val(null).trigger("change");
                    addValidator.resetForm();
                } else {
                    this.selectedColor.Name = data.name;
                    this.selectedColor.Description = data.description;
                    this.selectedColor.FormCategoryId = data.category;
                    this.selectedColor.IsActive = data.status;
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
    jquery("#FormCategory").on('change', function () {
        $(this).valid();
        $(this).next('span.select2').find(".select2-selection").removeClass("select2-selection-error").closest('.form-group').removeClass('has-error');
    });
    //Reset forms on Modal Close
    $('#AddColorModal').on('hidden.bs.modal', function () {
        $("#AddColorForm").trigger('reset');
        jquery("#FormCategory").val(null).trigger("change");
        addValidator.resetForm();
    });
    $('#EditColorModal').on('hidden.bs.modal', function () {
        vueInstance.currentColorId = null;
        $(".alert").addClass('hidden');
        editValidator.resetForm();
    });
    vueInstance.OnCreateColorRequest();
    vueInstance.OnUpdateColorRequest();
});