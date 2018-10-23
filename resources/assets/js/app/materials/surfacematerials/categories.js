let notificationTimeout = 10000, notificationTimeoutID,DatatableObj, addCategoryFormValidator, editCategoryFormValidator, vueInstance;
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
    el: "#SurfaceMaterialCategoryPage",
    data: {
        Categories: [],
        StoreSurfaceCategoryRoute: StoreSurfaceCategoryRoute,
        ShowSaveSurfaceCategoryLoader: false,
        ShowUpdateSurfaceCategoryLoader: false,
        currentSurfaceCategoryId: null,
        FormOverLay: true, // Toggle for notification loader
        NotificationIcon: "",
        NotificationMessage: "", //Message for notification loader

    },
    components: {
        'overlay-notification': OverlayNotification
    },
    created() {
        this.Categories = Categories;
    },
    computed: {
        filteredCategories: function () {
            return _.orderBy(this.Categories, ["CreatedAt"],["desc"]);
        },
        selectedCategoryData: function () {
            if (this.currentSurfaceCategoryId) {
                return _.find(this.Categories, function (Category) {
                    return Category.Id === this.currentSurfaceCategoryId;
                }.bind(this));
            } else {
                return {};
            }
        }
    },
    methods: {
        addCategory: function () {
              $("#AddCategoryModal").modal({
                show: true
            });
        },
        OnsubmitAddCategoryForm() {
            addCategoryFormValidator = $("#AddCategoryForm").validate({
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
                        required: "Category can't be blank."
                    },
                    Status: {
                        required: "Status can't be blank."
                    }
                },
                submitHandler: function (form, event) {
                    event.preventDefault();
                    $("#AddCategoryFormSubmitBtn").trigger('blur');
                    vueInstance.ShowSaveSurfaceCategoryLoader = true;
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
                        vueInstance.onFail(error, addCategoryFormValidator);
                    })
                    .always(function () {
                        vueInstance.ShowSaveSurfaceCategoryLoader = false;
                        $("#AddCategoryFormSubmitBtn").trigger('blur');
                    });
                }
            });
        },
        OnsubmitEditCategoryForm() {
            editCategoryFormValidator = $("#EditCategoryForm").validate({
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
                    EditCategoryName: {
                        required: true,
                        ValidateAlphabet: true,
                        CheckConsecutiveSpaces: true,
                        minlength: 3,
                        maxlength: 255
                    },
                    EditCategoryStatus: {
                        required: true
                    }
                },
                messages: {
                    EditCategoryName: {
                        required: "Category can't be blank."
                    },
                    EditCategoryStatus: {
                        required: "Status can't be blank."
                    }
                },
                submitHandler: function (form, event) {
                    event.preventDefault();
                    $("#EditCategoryFormSubmitBtn").trigger('blur');
                    vueInstance.ShowUpdateSurfaceCategoryLoader = true;
                    let formData = new FormData(form);
                    let currentSurfacecategoryId = vueInstance.currentSurfaceCategoryId;
                    let data = {
                        name: $("#EditCategoryName").val(),
                        status: ($("#EditCategoryForm input.input-radio:checked").val() === "Active") ? true : false
                    };
                    $.ajax({
                        url:  form.action + "/" + currentSurfacecategoryId,
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
                        vueInstance.onFail(error, editCategoryFormValidator);
                    })
                    .always(function () {
                        vueInstance.ShowUpdateSurfaceCategoryLoader = false;
                        $("#EditCategoryFormSubmitBtn").trigger('blur');
                    });
                }
            });
        },
        onSuccess(response, isAddForm = true, data = null) {
            if (response.status === "success") {
                if (isAddForm) {
                    // Post submit add form code goes here...
                    let status = ($("#AddCategoryForm input.input-radio:checked").val() === "Active") ? true : false;
                    vueInstance.Categories.push({
                        Id: response.categoryId,
                        Name: $("#Name").val(),
                        IsActive: status
                    });
                    $("#AddCategoryForm").trigger('reset');
                } else {
                    this.selectedCategoryData.Name = data.name;
                    this.selectedCategoryData.IsActive = data.status;
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
        editCategory(categoryId) {
            if (categoryId) {
                vueInstance.currentSurfaceCategoryId = categoryId;
                $("#EditCategoryModal").modal({
                    show: true
                });
            } else {
                vueInstance.currentSurfaceCategoryId = null;
            }
        },
        viewCategory(categoryId) {
            if (categoryId) {
                vueInstance.currentSurfaceCategoryId = categoryId;
                $("#ViewCategoryModal").modal({
                    show: true
                });
            } else {
                vueInstance.currentSurfaceCategoryId = null;
            }
        },
       
    }
});

$(document).ready(function () {
    //Reset Form on Modal Close
    $('#AddCategoryModal').on('hidden.bs.modal', function () {
        $("#AddCategoryForm").trigger('reset');
        addCategoryFormValidator.resetForm();
        vueInstance.clearOverLayMessage();
    });
    
    $('#EditCategoryModal').on('hidden.bs.modal', function () {
        editCategoryFormValidator.resetForm();
        vueInstance.currentSurfaceCategoryId = null;
        vueInstance.clearOverLayMessage();
    });
    $("#ViewCategoryModal").on('hidden.bs.modal',function(){
        vueInstance.currentSurfaceCategoryId = null;
    });
    vueInstance.OnsubmitAddCategoryForm();
    vueInstance.OnsubmitEditCategoryForm();
    initializeDataTable();
});

// DataTable initialization
function initializeDataTable() {
    // DataTable initialization
    DatatableObj = $('#CategoriesList').DataTable({
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
    $("#CategoriesList_filter input").attr('placeholder', 'Search...').focus();
}