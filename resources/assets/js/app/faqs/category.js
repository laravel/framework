/*
 * Global variables
 */
let NotificationTimeout = 10000, notificationTimeoutID, UpdateValidator, addValidator, vueInstance, Formrules, Formmessages;

/** include needed packages **/
require('../../bootstrap');

/** Import Vue table package **/
let VueTables = require('vue-tables-2');
Vue.use(VueTables.ClientTable);


/*
 * Form validation rules.
 */
Formrules = {
    Name: {
        required: true,
        validateText: true
    },
    Status: {
        required: true
    }
};

/*
 * Form validation messages.
 */
Formmessages = {
    Name: {
        required: "Name can't be blank."
    },
    Status: {
        required: "Status is required."
    }
};

/** initialize Vue instance **/
 vueInstance = new Vue({
    el: '#category',
    data: {
        Url: Baseurl,
        categories: {},
        selectedCategoryData: {},
        SaveLoader: true,
        UpdateLoader: true,
        SelectedCategoryIndex: null,
        columns: ['Id', 'Name', 'IsActive', 'Actions'],
        options: {
            headings: {
                Id: 'S.No',
                IsActive: 'Status'
            },
            columnsClasses: {
                'Id': 'text-center text-vertical-align wd-10',
                'Name': 'text-vertical-align wd-40',
                'IsActive': 'text-center text-vertical-align wd-30',
                'Actions': 'text-center text-vertical-align wd-20'
            },
            texts: {
                filterPlaceholder: "Search...",
                noResults: "No matching records found."
            },
            filterable: ['Name'],
            sortable: ['Name']
        }
    },
    created() {
        /** Bind the variables which are defined in controller **/
        this.categories = FaqCategories;
    },
    computed: {
        filteredCategories: function () {
            return _.sortBy(this.categories, ["Name"]);
        }
    },
    methods: {

        addModal() {
            $("#AddModal").modal({
                show: true
            });
        },

        updateModal(Category) {
            UpdateForm();
            this.selectedCategoryData = Category;
            $("#UpdateModal").modal({
                show: true
            });
        },
        
        viewModal(index, Category){
            this.selectedCategoryData = Category;
            this.SelectedCategoryIndex = index;
            $("#ViewModal").modal({
                show: true
            });
        },
        
        onSuccess(data, Form) {
            this.categories = data; 
            if(Form === "Update"){
                this.selectedCategoryData = _.find(this.categories, function (value) {
                    return value.Id === this.selectedCategoryData.Id;
                }.bind(this));
                
            }else{
                $("#addForm").trigger('reset');
            }
        }
    }
});

/**
 * PopulateNotifications - function to populate the notifications of the form.
 * @param   JSON    ResponseJSON
 * @return  No return[void]
 */
var populateNotifications = function (Response, formname = "default") {

    var NotificationArea = $("#NotificationArea");
    if (formname === "Update") {
        NotificationArea = $("#UpdateNotificationArea");
    }

    if (NotificationArea.children('.alert').length === 0) {
        NotificationArea.html('<div class="alert alert-dismissible hidden"></div>');
    }
    var AlertDiv = NotificationArea.children('.alert');
    if (Response.status === "success") {
        AlertDiv.removeAttr('class').removeAttr('style').addClass('alert alert-dismissible alert-success').html('<strong><i class="icon fa fa-check"></i> </strong><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button> &nbsp;' + Response.message);
        if (NotificationTimeout) {
            clearTimeout(NotificationTimeout);
        }
        NotificationTimeout = setTimeout(ClearNotificationMessage, 10000);
    } else {
        AlertDiv.removeAttr('class').removeAttr('style').addClass('alert alert-danger').html('<strong><i class="icon fa fa-ban"></i> </strong>' + Response.message);
}
};

/**
 * ClearNotificationMessage - function to close the notifications after 5sec time.
 * @param   No parameters
 * @return  No return[void]
 */
var ClearNotificationMessage = function () {
    $("#NotificationArea").children(".alert").fadeOut("slow", function () {
        $(this).addClass('hidden');
    });
    $("#UpdateNotificationArea").children(".alert").fadeOut("slow", function () {
        $(this).addClass('hidden');
    });
};

/**
 * Populates the laravel validator error's.
 * 
 * @return  No return(void)
 */
function populateFormErrors(errors, formValidator)
{
    for (let elementName in errors) {
        let errorObject = {},
        previousValue = $("#" + elementName).data("previousValue") ? $("#" + elementName).data("previousValue") : {};
        previousValue.valid = false;
        previousValue.message = errors[elementName][0];
        $("#" + elementName).data("previousValue", previousValue);
        errorObject[elementName] = errors[elementName];
        formValidator.showErrors(errorObject);
    }
}

/**
 * Add form validation initialization.
 * 
 * @param   No parameters
 * @return  No return[void]
 */
var addForm = function () {
    addValidator = $("#addForm").validate({
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
        rules: Formrules,
        messages: Formmessages,
        submitHandler: function (form, event) {
            event.preventDefault();
            vueInstance.SaveLoader = false;
            ajaxCall(form, addValidator);
        }
    });
};

/**
 * Update form validation initialization.
 * 
 * @param   No parameters
 * @return  No return[void]
 */
var UpdateForm = function () {
    UpdateValidator = $("#UpdateForm").validate({
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
        rules: Formrules,
        messages: Formmessages,
        submitHandler: function (form, event) {
            event.preventDefault();
            vueInstance.UpdateLoader = false;
            ajaxCall(form, UpdateValidator, "Update");
        }
    });
};

/**
 * Remote call for form submit.
 * 
 * @return  No return[void]
 */
var ajaxCall = function (form, FormVariable, formname = "default") {

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
        if (response.status === "success") {
            vueInstance.onSuccess(response.data, formname);
        }
        populateNotifications(response, formname);
    })
    .fail(function (jqXHR) {
        if (jqXHR.status === 422) {
            var responsedata = JSON.parse(jqXHR.responseText);
            populateFormErrors(responsedata.data.errors, FormVariable);
        } else if (jqXHR.status === 413) {
            populateNotifications({
                status: "warning",
                message: "Max upload file size allowed 10MB. Check files size and try again."
            });
        } else {
            populateNotifications({
                status: "error",
                message: AlertData["10077"]
            });
        }
    })
    .always(function () {
        vueInstance.UpdateLoader = true;
        vueInstance.SaveLoader = true;
    });
};


$(document).ready(function () {

    addForm();

    $("#AddModal, #UpdateModal").on('hidden.bs.modal', function (event) {
        $("#addForm").trigger('reset');
        $(".alert").addClass('hidden');
    });
    
    $("#UpdateModal, #ViewModal").on('hidden.bs.modal', function (event) {
        vueInstance.selectedCategoryData = {};
        $("#UpdateForm").trigger('reset');
    });
    
    $("#UpdateForm").on('reset', function() {
        UpdateValidator.resetForm();
    });
    
    $("#addForm").on('reset', function () {
       addValidator.resetForm();
    });
});