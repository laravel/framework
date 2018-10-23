/*
 * Global variables
 */
let NotificationTimeout = 10000, notificationTimeoutID, vueInstance, Formrules, Formmessages, CreateValidator, UpdateValidator;

/** include needed packages **/
require('../../bootstrap');
require('magnific-popup');
var jquery = require('jquery');

/** Import Vue table package **/
let VueTables = require('vue-tables-2');
Vue.use(VueTables.ClientTable);

/* Register vue components for Map, update and view modal pop-up*/
import Add from '../../components/carousel/add';
import Update from '../../components/carousel/Update';
import View from '../../components/carousel/View';
import OverlayNotification from '../../components/overlayNotification';

//Vue Instance
vueInstance = new Vue({
    el: "#carousel",
    data: {
        Data:[],
        URL: UrlCdn,
        FormUrl: CarouselUrl,
        currentCarousel: "",
        SaveLoader: true,
        UpdateLoader: true,
        FormOverLay: true,
        NotificationMessage: "",
        Loader: true,
        LoaderMessage: "",
        NotificationIcon: "",
        columns: ['Id', 'Title', 'Description', 'Source', 'Order','Actions'],
        options: {
            headings: {
                Id: 'S.No'
            },
            columnsClasses: {
                'Id': 'text-center text-vertical-align wd-6',
                'Title': 'text-vertical-align wd-16',
                'Description': 'text-vertical-align wd-43',
                'Source': 'text-center text-vertical-align wd-14',
                'Order': 'text-center text-vertical-align wd-6',
                'Actions': 'text-center text-vertical-align wd-14'
            },
            texts: {
                filterPlaceholder: "Search...",
                noResults: "No matching records found."
            },
            filterable: ['Title', 'Description'],
            sortable: ['Title', 'Description', 'Order']
        }
    },
    components: {
        'add-popup': Add,
        'update-popup': Update,
        'view-popup': View,
        'overlay-notification': OverlayNotification
    },
    created() {
       this.Data =  _.values(Data);
       this.imagePopUP();
    },
    computed: {
        SelectedCarousel: function () {
            if (this.currentCarousel) {
                return _.find(this.Data, function (value) {
                    return value.key === this.currentCarousel;
                }.bind(this));
            } else {
                return {};
            }
        },
        CarouselLength: function(){
            return this.Data.length;
        },
        sortCarouselData: function () {
            return _.sortBy(this.Data, ["Title"]);
        }
    },
    methods: {
        
        imagePopUP(){
            this.$nextTick(function () {
                jquery("a.img-popup").magnificPopup({
                    type: 'image'
                });
            });
        },
        
        /*
         * Display new modal pop-up.
         * 
         * @param   No parameters
         * @return  No return[void]
         */
        add(){
            $("#AddModal").modal({
                show: true
            });
        },
        
        /*
         * Display update modal pop-up.
         * 
         * @param  key
         * @return  No return[void]
         */
        edit(key){
            this.currentCarousel = key;
            $("#UpdateModal").modal({
                show: true
            });
        },
        
        /*
         * Display data modal pop-up.
         * 
         * @param  key
         * @return  No return[void]
         */
        view(key){
            this.currentCarousel = key;
            $("#ViewModal").modal({
                show: true
            });
        },

        /*
         * Display confirmation modal pop-up.
         * 
         * @param  key
         * @return  No return[void]
         */
        deleteCarousel(key){
            this.currentCarousel = key;
            $("#ConfirmationModal").modal({
                show: true
            });
        },
        
        onSuccess(Data, FormName="default"){
            this.Data =  _.values(Data);
            this.imagePopUP();
            if(FormName ==="create"){
                jquery("#AddForm").trigger("reset");
            }
        },
        
        deleteComment(){
            $("#ConfirmationModal").modal("hide");
            this.Loader = false;
            this.LoaderMessage = "Deleting...";
            let SelfRef = this;
            
            let Url = this.FormUrl+"/delete/"+this.SelectedCarousel.key;
            
             axios.get(Url)
            .then(function (response) {
                SelfRef.PopulateNotifications(response.data);
                if(response.data.status==="success"){
                    SelfRef.onSuccess(response.data.data);
                }
            })
            .catch(function (error) {
                SelfRef.PopulateNotifications({
                    status: "error",
                    alertMessage: AlertData["10077"]
                });
            })
            .then(() => {
                this.Loader = true;
            });
        },
        
        /**
        * Clears overlay message.
        * @return  No
        */
        clearOverLayMessage()
        {
           this.FormOverLay = true;
           this.NotificationMessage = "";
           this.NotificationIcon = "";
        },
        
        /**
        * PopulateNotifications - function to populate the notifications of the form.
        * 
        * @param  Response
        * @return  No return(void)
        */
        PopulateNotifications(Response) {
            this.NotificationMessage = Response.message;
            this.NotificationIcon = Response.status;
            this.FormOverLay = false;
            if (Response.status === "success") {
                this.NotificationIcon = "check-circle";
            } else if (Response.status === 'error') {
                this.NotificationIcon = "ban";
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
    if(formname === "Update"){
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
    $("#NotificationArea").children(".alert").fadeOut("slow", function() {
        $(this).addClass('hidden');
    });
    $("#UpdateNotificationArea").children(".alert").fadeOut("slow", function() {
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

/*
 * Form validation rules.
 */
Formrules  = {
    Title: {
        required: true,
        minlength: 3,
        maxlength: 50
    },
    Description: {
        required: true,
        minlength: 3,
        maxlength: 1000
    }
};

/*
 * Form validation messages.
 */
Formmessages = {
    Title: {
        required: "Title can't be blank."
    },
    Description: {
        required: "Description can't be blank."
    },
    Image: {
        required: "Image can't be blank."
    }
};

/**
 * Map Material form validation initialization.
 * 
 * @param   No parameters
 * @return  No return[void]
 */
var CreateForm = function(){
    let CreateRules = {Image:{
        required: true
    }};
    _.merge(CreateRules, Formrules);
    CreateValidator = $("#AddForm").validate({
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
        rules: CreateRules,
        messages: Formmessages,
        submitHandler: function (form, event) {
            event.preventDefault();
            vueInstance.SaveLoader = false;
            ajaxCall(form, CreateValidator, "create");
        }
    });
};

/**
 * Update form validation initialization.
 * 
 * @param   No parameters
 * @return  No return[void]
 */
var UpdateForm = function(){
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
var ajaxCall = function(form, FormVariable, formname = "default"){
            
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
        if(response.status === "success"){
            vueInstance.onSuccess(response.data, formname);
        }
       populateNotifications(response, formname);
    })
    .fail(function (jqXHR) {
        if (jqXHR.status === 422) {
            var responsedata = JSON.parse(jqXHR.responseText);
            populateFormErrors(responsedata.data.errors, FormVariable);
        }
        else if (jqXHR.status === 413) {
            populateNotifications({
                status: "warning",
                message: "Max upload file size allowed 10MB. Check files size and try again."
            });
        }else {
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
   
    CreateForm();
    UpdateForm();
      
    jquery("#AddForm").on('reset', function (event) {
        CreateValidator.resetForm();
    });
    
});