/*
 * Global variables
 */
let NotificationTimeout = 10000, notificationTimeoutID, vueInstance, Formrules, Formmessages, UpdateValidator;

/** include needed packages **/
require('../../bootstrap');
var jquery = require('jquery');
require('select2');

/** Import Vue table package **/
let VueTables = require('vue-tables-2');
Vue.use(VueTables.ClientTable);

/* Register vue components for Map modal pop-up*/
import Map from '../../components/QEItem/Mapping/Map';
import OverlayNotification from '../../components/overlayNotification';

//Vue Instance
vueInstance = new Vue({
    el: "#carousel",
    data: {
        Data:[],
        Url: Url,
        selected: Selected,
        Filter: Filter,
        DetailESTItems: DEItems,
        currentCarousel: "",
        UpdateLoader: true,
        Loader: true,
        LoaderMessage: "",
        currentItem:"",
        FormOverLay: true,
        NotificationMessage: "",
        NotificationIcon: "",
        columns: ['Id', 'Description', 'DEItemsDescription', 'Action'],
        options: {
            headings: {
                Id: 'S.No',
                Description: 'Quick Estimation Item',
                DEItemsDescription: 'Detail Estimation Items'
            },
            columnsClasses: {
                'Id': 'text-center text-vertical-align wd-8',
                'Description': 'text-vertical-align wd-30',
                'DEItemsDescription': 'text-vertical-align wd-52',
                'Action': 'text-center text-vertical-align wd-10'
            },
            texts: {
                filterPlaceholder: "Search...",
                noResults: "No matching records found."
            },
            filterable: ['DEItemsDescription', 'Description'],
            sortable: ['DEItemsDescription', 'Description']
        }
    },
    components: {
        'map-popup': Map,
        'overlay-notification': OverlayNotification
    },
    created() {
       this.Data =  ItemsList;
    },
    computed: {
        SelectedItem: function () {
            if (this.currentItem) {
                return _.find(this.Data, function (value) {
                    return value.Id === this.currentItem;
                }.bind(this));
            } else {
                return {};
            }
        }
    },
    methods: {
        
        onChangeEvent(){
            location.href = Url+'/'+this.selected;
        },
        
        /*
         * Display update modal pop-up.
         * 
         * @param  key
         * @return  No return[void]
         */
        map(Id){
            UpdateValidator.resetForm();
            this.currentItem = Id;
            this.$nextTick(() => {
                initializeSelect2();
            });
             $("#MapModal").modal({
                show: true
            });
        },
        
        onSuccess(List){
            this.Data =  List;
        },
        
        notification(response){
            if(this.selected === "unmapped"){
                $('#MapModal').modal('hide');
                this.currentItem = "";
                this.PopulateNotifications(response);
            }
            if(response.status === "success"){
                this.onSuccess(response.data);
            }
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
var populateNotifications = function (Response) {

    var NotificationArea = $("#UpdateNotificationArea");
        NotificationArea.html('<div class="alert alert-dismissible hidden"></div>');
    var AlertDiv = NotificationArea.children('.alert');
    if (Response.status === "success") {
        AlertDiv.removeAttr('class').removeAttr('style').addClass('alert alert-dismissible alert-success').html('<strong><i class="icon fa fa-check"></i> </strong><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button> &nbsp;' + Response.message);
        if (NotificationTimeout) {
            clearTimeout(NotificationTimeout);
        }
        NotificationTimeout = setTimeout(ClearNotificationMessage, 5000);
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
    "DEItems[]": {
        required: true
    }
};

/*
 * Form validation messages.
 */
Formmessages = {
    "DEItems[]": {
        required: "Detail Estimation Item cannot be blank."
    }
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
            ajaxCall(form, UpdateValidator);
        }
    });
};

/**
 * Remote call for form submit.
 * 
 * @return  No return[void]
 */
var ajaxCall = function(form, FormVariable){
            
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
        vueInstance.notification(response);
        populateNotifications(response);
    })
    .fail(function (jqXHR) {
        if (jqXHR.status === 422) {
            var responsedata = JSON.parse(jqXHR.responseText);
            populateFormErrors(responsedata.data.errors, FormVariable);
        }
        else {
            populateNotifications({
                status: "error",
                message: AlertData["10077"]
            });
        }
    })
    .always(function () {
        vueInstance.UpdateLoader = true;     
    });
};

var initializeSelect2 = () => {
    jquery('#DEItems').select2({placeholder: "Select a Item"});
};

$(document).ready(() => UpdateForm());