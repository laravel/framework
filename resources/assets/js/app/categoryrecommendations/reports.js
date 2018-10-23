require('../../bootstrap');
require('select2');
const jQuery = require('jquery');

var dataTable, validator;

import OverlayNotification from '../../components/overlayNotification';

const vueVariables = {
    rooms: [],
    projectFormOverlay: true,
    isMatAvail: false,
    overLayMessage: "",
    formOverLay: true,
    notificationIcon: "",
    notificationMessage:""
};

const vueInstance = new Vue({
    el: '#RecommendationsReportsPage',
    
    data: vueVariables,
   
    mounted() {
        this.bootstrapSelect2();
        this.getProjectRooms();
        this.getMaterials();
    },
    
    components: {
       'overlay-notification': OverlayNotification
    },
    
    methods: {
        /**
        * Initialise Select2.
        * 
        * @return  No
        */
        bootstrapSelect2()
        {
            jQuery("#Project").select2({
                placeholder: 'Select Project',
                language: {
                    noResults: function () {
                        return "No Projects found.";
                    }
                }
            });
            jQuery("#Room").select2({
                placeholder: 'Select Room',
                language: {
                    noResults: function () {
                        return "No Rooms found.";
                    }
                }
            });
            jQuery("#Category").select2({
                placeholder: 'Select Category',
                language: {
                    noResults: function () {
                        return "No Category found.";
                    }
                }
            });
        },
        
        /**
        * Returns Project rooms.
        * 
        * @return  No
        */
        getProjectRooms() 
        {
            jQuery("#Project").on('change', function (event) {
                if (jQuery(this).val()) {
                    event.preventDefault();
                    vueInstance.projectFormOverlay = false;
                    vueInstance.overLayMessage = "Fetching Project Rooms";
                    vueInstance.rooms = [];
                    let requestUrl = $(this).data('api-end-point');
                    let qeId = $(this).find(':selected').data('quick-estimation-id');
                    axios.get(requestUrl + '/' + qeId)
                    .then(function (response) {
                        vueInstance.rooms = response.data;
                    })
                    .catch(function (error) {
                        vueInstance.onFail(error);
                    })
                    .then(() => {
                        vueInstance.projectFormOverlay = true;
                    });
                }
            });
        },
       
        /**
        * Make request and get materials
        * 
        * @return  No
        */
        getMaterials()
        {
            validator = $("#RecommendationsReportForm").validate({
                ignore: [],
                onkeyup: function (element, event) {
                    if (this.invalid.hasOwnProperty(element.name)) {
                        $(element).valid();
                    }
                },
                errorClass: "help-block text-danger",
                errorElement: "span",
                highlight: function (element, errorClass) {
                    if (element.id === "Project") {
                        $(element).next('span.select2').find(".select2-selection").addClass("select2-selection-error").closest('.form-group').addClass('has-error');
                    } else {
                        $(element).parent().addClass("has-error");
                    }
                },
                unhighlight: function (element, errorClass) {
                    if (element.id === "Project") {
                        $(element).next('span.select2').find(".select2-selection").removeClass("select2-selection-error").closest('.form-group').removeClass('has-error');
                    } else {
                        $(element).parent().removeClass("has-error");
                    }
                },
                errorPlacement: function (error, element) {
                    if (element.id === "Project") {
                        error.insertAfter($(element).next("span.select2"));
                    } else {
                        error.appendTo($(element).parent());
                    }
                },
                rules: {
                    Project: {
                        required: true
                    }
                },
                messages: {
                    Project: {
                        required: "Project can't be blank."
                    }
                },
                submitHandler: function (form) {
                    vueInstance.projectFormOverlay = false;
                    vueInstance.overLayMessage = "Fetching Materials";
                    $("#MaterialsListBox").empty();
                    let formData = new FormData($('form#RecommendationsReportForm')[0]);
                    let requestUrl = form.action;
                    const config = {
                        headers: {
                            'Data-Type': 'html',
                            'Content-Type': 'false', 
                            'Process-Data': 'false'
                        }
                    };
                    axios.post(requestUrl, formData, config)
                    .then(response => {
                        if (response.data.status === "success") {
                            $("#MaterialsListBox").removeClass("hidden");
                            $("#MaterialsListBox").append(response.data.itemsView);
                            vueInstance.isMatAvail = response.data.isData;
                        } else {
                            vueInstance.populateNotifications(response.data);
                        }
                    })
                    .catch(error => {
                        vueInstance.onFail(error);
                    })
                    .then(() => {
                        $("#SearchSubmit").trigger("blur");
                        vueInstance.projectFormOverlay = true;
                    });
                }
            });
        },
        
        /**
        * Reset Note form
        * 
        * @return  No
        */
        resetNoteForm()
        {
            validator.resetForm();
        },
              
        /**
         * Populates notifications of the form.
         *
         * @param  object  response
         * 
         * @return  No
         */
        populateNotifications(response)
        {
            this.notificationMessage = response.message;
            this.notificationIcon = response.status;
            this.formOverLay = false;
            if (response.status === "success") {     
                this.notificationIcon = "check-circle";
            } else {
                this.notificationIcon = "ban";
            }
        },
        
        /**
        * Populates failed request notification message.
        * 
        * @return  No
        */
        onFail(error) 
        {
            this.populateNotifications({
                status: "error",
                message: error.data.message
            });
        },
        
        /**
        * Clears overlay message.
        * 
        * @return  No
        */
        clearOverLayMessage()
        {
           this.formOverLay = true;
           this.notificationMessage = "";
           this.notificationIcon = "";
        }
    }
});

$(document).ready(() => {
    $("#RecommendationsReportForm").on('reset', function() {
        validator.resetForm();
        jQuery('#Project, #Room, #Category').val(null).trigger('change');
    });
});