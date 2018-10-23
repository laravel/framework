/*
 * Global variables
 */
let NotificationTimeout = 10000, 
    notificationTimeoutID, 
    UpdateValidator, 
    addValidator, 
    vueInstance, 
    Formrules, 
    Formmessages,
    editor,
    editEditor;

/** include needed packages **/
require('../../bootstrap');
require('select2');
var jquery = require('jquery');

/** Import Vue table package **/
let VueTables = require('vue-tables-2');
Vue.use(VueTables.ClientTable);
    
/** initialize Vue instance **/
vueInstance = new Vue({
    el: '#ListFaqs',
    data: { 
        faqs: {},
        selectedFaqData:{},
        categories: Categories,
        Url: Baseurl,
        UrlCdn: CdnUrl,
        SaveLoader: true,
        UpdateLoader: true,
        SelectedFaqIndex: null,
        columns: ['Id', 'FaqCategory', 'Question', 'Answer', 'IsActive', 'Actions'],
        options: {
            headings: {
                Id: 'S.No',
                FaqCategory: 'FAQ Category',
                IsActive: 'Status'
            },
            columnsClasses: {
                'Id': 'text-center text-vertical-align wd-6',
                'FaqCategory': 'text-vertical-align wd-19',
                'Question': 'text-vertical-align wd-27',
                'Answer': 'text-vertical-align wd-30',
                'IsActive': 'text-center text-vertical-align wd-8',
                'Actions': 'text-center text-vertical-align wd-10'
            },
            texts: {
                filterPlaceholder: "Search...",
                noResults: "No matching records found."
            },
            filterable: ['FaqCategory', 'Question', 'Answer'],
            sortable: ['FaqCategory', 'Question', 'Answer']
        }
    },
    mounted() {
       this.faqs = Faqs;
    },
    computed: {
        filteredFaqs: function () {
            return _.sortBy(this.faqs, ["FaqCategory"]);
        }
    },
    methods:{
        
        sliceAnswer(Answer){
            if(Answer){
                if(Answer.length>80){
                    return Answer.slice(0, 80)+'...';
                }else{
                    return Answer;
                }
            }
            return "N/A";
        },
        
        sliceQuestion(Question){
            if(Question){
                if(Question.length>80){
                    return Question.slice(0, 80)+'...';
                }else{
                    return Question;
                }
            }
            return "N/A";
        },
        
        addModal(){
             $("#AddModal").modal({
                show: true
            });
        },
        
        updateModal(faq) {            
            UpdateForm();
            this.selectedFaqData = faq;
            $("#UpdateModal").modal({
                show: true
            });
            this.InitializeEditFaqCategory();
            this.$nextTick(function () {
                editEditor.setValue(faq.Answer, true);
            });
        },
        
        viewModal(index, faq){
            this.selectedFaqData = faq;
            this.SelectedFaqIndex = index;
            $("#ViewModal").modal({
                show: true
            });
        },
        
        onSuccess(data, Form) {
            this.faqs = data; 
            if(Form === "Update"){
                this.selectedFaqData = _.find(this.faqs, function (value) {
                    return value.Id === this.selectedFaqData.Id;
                }.bind(this));
                
            }else{
                $("#addForm").trigger('reset');                
            }
        },
        
        InitializeEditFaqCategory(){
            this.$nextTick(function () {
                jquery('#EditFaqCategory').select2({placeholder: "Select a Category"});
            });
        },
        
        fileName(Path){
            let Name = "N/A";
            if(Path){
                let FName = Path.split("/").pop();
                let Extension = FName.split(".").pop();
                let FileName = FName.split("_");
                FileName.pop();
                Name = FileName.join()+'.'+Extension;
            }
            return Name; 
        }
    }
});

/*
 * Form validation rules.
 */
Formrules = {
    FaqCategory: {
        required: true
    },
    Image:{
        CheckSingleFileExtension: true,
        checkPerFileSizeInMultipleFiles: true
    },
    Question: {
        required: true,
        validateText: true
    },
    Answer: {
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
    FaqCategory: {
        required: "FAQ Category can't be blank."
    },
    Image:{
        CheckSingleFileExtension: 'Invalid Image. Accepted file types: jpg, jpeg, png, bmp.',
        checkPerFileSizeInMultipleFiles: 'Max upload file size is 2MB. Check file size and try again.'
    },
    Question: {
        required: "Question can't be blank."
    },
    Answer: {
        required: "Answer can't be blank."
    },
    Status: {
        required: "Status is required."
    }
};


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
            if ((element.id === "FaqCategory")) {
                $(element).next('span.select2').find(".select2-selection").addClass("select2-selection-error").closest('.form-group').addClass('has-error');
            } else {
                $(element).closest('.form-group').addClass("has-error");
            }
        },
        unhighlight: function (element, errorClass) {
           if ((element.id === "FaqCategory")) {
                $(element).next('span.select2').find(".select2-selection").removeClass("select2-selection-error").closest('.form-group').removeClass('has-error');
            } else {
                $(element).closest('.form-group').removeClass("has-error");
            }
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
    
/*
 * Initialize Add form editor.
 */
    editor = new wysihtml5.Editor('Answer', {
        toolbar: 'toolbar',
        parserRules:  wysihtml5ParserRules,
        stylesheets:  Baseurl+"/css/faqs/editor.css"
    });
    
 /*
  * Initialize Update from editor.
  */
    editEditor = new wysihtml5.Editor('EditAnswer', {
        toolbar: 'edittoolbar',
        parserRules:  wysihtml5ParserRules,
        stylesheets:  Baseurl+"/css/faqs/editor.css"
    });
    
    $("#AddModal").on('hidden.bs.modal', function() {
        $("#addForm").trigger('reset');
        $(".alert").addClass('hidden');
    });
    
    $("#addForm").on('reset', function() {
        addValidator.resetForm();
        jquery("#FaqCategory").val("").trigger('change');
    });
    
    $("#UpdateForm").on('reset', function() {
        UpdateValidator.resetForm();
    });
    
    $("#UpdateModal").on('hidden.bs.modal', function (event) {
        $(".alert").addClass('hidden');
        vueInstance.selectedFaqData = {};
        $("#UpdateForm").trigger('reset');
    });
    
    $("#ViewModal").on('hidden.bs.modal', function (event) {
        vueInstance.selectedFaqData = {};
    });
    
    $("#insertlink").on('click', function(event){
        event.preventDefault();
        let val = $("#createlinktextfield").val();
        editor.composer.commands.exec("createLink", { href: val, target: "_blank", rel: "nofollow", text: "'"+val+"'" });
    });
    
    $("#updateinsertlink").on('click', function(event){
        event.preventDefault();
        let val = $("#updatelinktextfield").val();
        editEditor.composer.commands.exec("createLink", { href: val, target: "_blank", rel: "nofollow", text: "'"+val+"'" });
    });
    
    jquery('#FaqCategory').select2({placeholder: "Select a Category"}); 
});

/**
     *  CheckFileExtension : checking valid file extension before upload
     *  @param : value - AnyType
     *  @param : element - HTML DOM Element
     *  @return type : Boolean (true or false)
     */
    $.validator.addMethod("CheckSingleFileExtension", function (value, element) {
        if (value.length !== 0) {
            var basename = value.split(/[\\/]/).pop(),
                    pos = basename.lastIndexOf(".");
            if (basename === "" || pos < 1) {
                $(element).rules("add", {
                    messages: {
                        CheckFileExtension: AlertData["10086"]
                    }
                });
                return false;
            } else {
                var fileExtension = basename.slice(pos + 1).toLowerCase();
                if (fileExtension === "jpg" || fileExtension === "jpeg" || fileExtension === "png" || fileExtension === "bmp") {
                    return true;
                } else {
                    $(element).rules("add", {
                        messages: {
                            CheckFileExtension: 'Invalid Image. Accepted file types: jpg, jpeg, png, bmp.'
                        }
                    });
                    return false;
                }
            }
        } else {
            return true;
        }
    });