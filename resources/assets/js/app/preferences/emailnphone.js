//Global variables
var PrimaryEmail = UserEmail;
var PrimaryPhone = UserPhone;
var NotificationTimeout;

//include needed packages
require('../../bootstrap');

/**
 * populateNotifications - function to populate the notifications of the form.
 * @param   JSON    ResponseJSON
 * @return  No return[void]
 */
var populateNotifications = function (ResponseJSON) {
    var NotificationArea = $("#NotificationArea");
    if (NotificationArea.children('.alert').length === 0) {
        NotificationArea.html('<div class="alert alert-dismissible hidden"></div>');
    }
    var AlertDiv = NotificationArea.children('.alert');
    if (ResponseJSON.status) {
        AlertDiv.removeAttr('class').removeAttr('style').addClass('alert alert-dismissible alert-' + ResponseJSON.data.alertType).html('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button> &nbsp;' + ResponseJSON.data.alertMessage);
        if (NotificationTimeout) {
            clearTimeout(NotificationTimeout);
        }
        NotificationTimeout = setTimeout(ClearNotificationMessage, 10000);
    } else {
        AlertDiv.removeAttr('class').removeAttr('style').addClass('alert alert-danger').html('<strong><i class="icon fa fa-ban"></i> </strong>' + ResponseJSON.alertMessage);
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
};

$(document).ready(function () {
    
    //Initialize the Vue instance 
    var ContactDetailsSection = new Vue({
        el: '#ContactDetailsSection',
        data: {
            Data: Data, // Values from the controller
            Phone: null,
            Email: null,
            DeletePhoneNumbers: [],
            DeleteEmails: [],
            PhoneFields: PhoneFields,  // Alternative phone numbers limit
            EmailFields: EmailFields, // Alternative emails limit
            Categories: Categories  // Category of email and phone numbers
        },
        created() {
            // Reduce the Phone and Email objects according to the limit
            this.Phone = _.slice(this.Data.Phone, 0, this.PhoneFields);
            this.Email = _.slice(this.Data.Email, 0, this.EmailFields);
        },
        methods: {
            // Adding a new alternative email field by pushing empty values in the object
            addEmailField() {
                if(this.Email.length < this.EmailFields){
                    this.Email.push({
                    "Email": "",
                    "EmailTypeId": "",
                    "Id": "New"
                    });
                    addValidationForEmailField();
                }
            },
            // Adding a new alternative phone field by pushing empty values in the object
            addPhoneField() {
                if(this.Phone.length < this.PhoneFields){
                    this.Phone.push({
                    "Phone": "",
                    "PhoneTypeId": "",
                    "Id": "New"
                    });
                    addValidationForPhoneField();
                }
            },
            // Deleting a email field and pushing that value in Deleted Emails object
            deleteEmailField(Id, key) {
                this.Email.splice(key,1);
                if(Id !== "New"){
                    this.DeleteEmails.push({Id});
                }
            },
            // Deleting a phone field and pushing that value in Deleted phone numbers object
            deletePhoneField(Id, key) {
                this.Phone.splice(key,1);
                if(Id !== "New"){
                    this.DeletePhoneNumbers.push({Id});
                }
            },
            // Values form storage are assigned to vue objects after form submitting success
            updatePhoneNEmail(Values) {
                this.Phone = _.slice(Values.Phone, 0, this.PhoneFields);
                this.Email = _.slice(Values.Email, 0, this.EmailFields);
                this.DeletePhoneNumbers = [];
                this.DeleteEmails = [];
                this.Data = Values;
            }
        },
        computed:{
            // Returns Email object
            filterEmailFields: function () {
                return this.Email;
            },
            // Returns Phone object
            filterPhoneFields: function (){
                return this.Phone;
            } 
       }
    });
    
// Alternative email and phone numbers update form validation.

    var EmailNPhoneForm = $("#EmailNPhoneForm").validate({
        onkeyup: function (element, event) {
            if (this.invalid.hasOwnProperty(element.name)) {
                $(element).valid();
            }
        },
        errorClass: "help-block",
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
        submitHandler: function (form, event) {
            event.preventDefault();
             
            $("#PageLoader").removeClass('hidden');
            $(".alert").addClass('hidden');
           
            var formData = new FormData(form);
            
            //object's of Alternative email and phone numbers for deletion
            var DeleteEmails = ContactDetailsSection.DeleteEmails;
            var DeletePhoneNumbers = ContactDetailsSection.DeletePhoneNumbers;
            formData.append('DeleteEmails', JSON.stringify(DeleteEmails));
            formData.append('DeletePhoneNumbers', JSON.stringify(DeletePhoneNumbers));
            
            $.ajax({
                url: form.action,
                type: 'POST',
                dataType: 'json',
                contentType: false,
                processData: false,
                data: formData
            }).done(function (response) {
                
                populateNotifications(response);
                if(response.status === "success"){
                    //Vue instance function
                    ContactDetailsSection.updatePhoneNEmail(response.data.values);
                }
            
            }).fail(function (jqXHR) {
                
                if (jqXHR.status === 422) {
                
                    var responsedata = JSON.parse(jqXHR.responseText);
                    populateFormErrors(responsedata.data.errors, EmailNPhoneForm);
                
                } else {
                    
                    populateNotifications({
                        status: false,
                        message: AlertData["10077"]
                    });
                }
            }).always(function () {
                
                $("#PageLoader").addClass('hidden');
            
            });
        }
    });
    addValidationForPhoneField();
    addValidationForEmailField();
    
    // validation for dynamic fields of alternative emails
    function addValidationForEmailField() {

        ContactDetailsSection.$nextTick(function () {

            $("div#EmailSection input.alt-email").each(function () {
               $(this).rules("add", {
                    required: true,
                    ValidateEmail: true,
                    uniqueemail: true,
                    notequalto: PrimaryEmail,
                    messages:{
                        required: "Email is required.",
                        notequalto: "Email already exists."
                    }
                });
            });
            $("div#EmailSection select.email-dropdown").each(function () {
                $(this).rules("add", {
                    required: true,
                    messages:{
                        required: "Type is required."
                    }
                });
            });
       });
    };
    
    // validation for dynamic fields of alternative phone numbers.
    function  addValidationForPhoneField() {

        ContactDetailsSection.$nextTick(function () {

            $("div#PhoneSection input.alt-phone").each(function () {
                $(this).rules("add", {
                    required: true,
                    uniquephone: true,
                    rangelength: [10, 10],
                    ValidateMobileNumber: true,
                    notequalto: PrimaryPhone,
                    messages:{
                        required: "Phone is required.",
                        notequalto: "Phone number already exists."
                    }
                });
            });
            $("div#PhoneSection select.phone-dropdown").each(function () {
                $(this).rules("add", {
                    required: true,
                    messages:{
                        required: "Type is required."
                    }
                });
            });
       });
    };

});
/**
 *  checks email input fields have unique values
 *  @param : value - AnyType
 *  @param : element - HTML DOM Element
 *  @return type : Boolean (true or false)
 */
$.validator.addMethod("uniqueemail", function (value, element) {
    var Validate = 'success';
    $('input.alt-email').map(function () {
        if($(this).attr('name') !== element.name && $(this).val() === value){
            var OthEleVal = _.nth(_.split($(this).attr('name'), "_"), -1);
            var SelfEleVal = _.nth(_.split(element.name, "_"), -1);
            if(OthEleVal < SelfEleVal){
                Validate = "error";
                return false;   
            }
        }
   });
   if(Validate === 'error'){
       return false;
   }
   return true;
}, "Email already exists.");
 
/**
 *  checks phone input fields have unique values
 *  @param : value - AnyType
 *  @param : element - HTML DOM Element
 *  @return type : Boolean (true or false)
 */
$.validator.addMethod("uniquephone", function (value, element) {
   var Validate = 'success';
    $('input.alt-phone').map(function () {
        if($(this).attr('name') !== element.name && $(this).val() === value){
            var OthEleVal = _.nth(_.split($(this).attr('name'), "_"), -1);
            var SelfEleVal = _.nth(_.split(element.name, "_"), -1);
            if(OthEleVal < SelfEleVal){
                Validate = "error";
                return false;   
            }
        }
   });
   if(Validate === 'error'){
       return false;
   }
   return true;
}, "Phone number already exists.");
/**
 *  checks value not equal to specific value
 *  @param : value - AnyType
 *  @param : element - HTML DOM Element
 *  @param : param - AnyType
 *  @return type : Boolean (true or false)
 */
$.validator.addMethod("notequalto", function(value, element, param) {
  return this.optional(element) || value != param;
}, "Value already exists.");
/**
 * Populates the laravel validator error's.
 * 
 * @type type
 */
function populateFormErrors(errors, formValidator)
{
   for (var elementName in errors) {
        var errorObject = {},
        previousValue = $("#" + elementName).data("previousValue") ? $("#" + elementName).data("previousValue") : {};
        previousValue.valid = false;
        previousValue.message = errors[elementName][0];
        $("#" + elementName).data("previousValue", previousValue);
        errorObject[elementName] = errors[elementName][0];
        formValidator.showErrors(errorObject);
    }
        
}


