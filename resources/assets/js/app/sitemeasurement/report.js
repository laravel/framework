//Form url's from controller
let formValidator, dataTableObject;

//Include needed packages
require('../../bootstrap');
require('select2');

//Resolve Jquery library conflict
let jquery = require('jquery');

$(document).ready(function () {
    const VueInstance = new Vue({
        //Vue root element
        el: '#SMReportsPage',
        
        //Vue data variables
        data: {
            showFormOverlay: false
        },
        
        //Vue object life cycle hook
        mounted() {              
            //Select2 Search initialization
            this.initializeSelect2();
        }, 
        
        //Vue methods
        methods: { 
            initializeSelect2() {
                //Select2 dropdown initialization
                jquery("#Project").select2({ placeholder: "Select Project"});
                jquery("#CreatedBy").select2({ placeholder: "Select User"});
            },
            
            PopulateNotifications(ResponseJSON) {   
                var NotificationArea = $("#NotificationArea");
                if (NotificationArea.children('.alert').length === 0) {
                    NotificationArea.html('<div class="alert alert-dismissible hidden"></div>');
                }
                var AlertDiv = NotificationArea.children('.alert');
                if (ResponseJSON.status === "success") {
                    AlertDiv.removeAttr('class').removeAttr('style').addClass('alert alert-dismissible alert-success').html('<strong><i class="icon fa fa-check"></i> Success : </strong><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + ResponseJSON.alertMessage);
                } else if (ResponseJSON.status === 'warning') {
                    AlertDiv.removeAttr('class').removeAttr('style').addClass('alert alert-dismissible alert-warning').html('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + ResponseJSON.alertMessage);

                } else if (ResponseJSON.status === 'error') {
                    AlertDiv.removeAttr('class').removeAttr('style').addClass('alert alert-dismissible alert-danger').html('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + ResponseJSON.alertMessage);

                }else if (ResponseJSON.status === 'info') {
                    AlertDiv.removeAttr('class').removeAttr('style').addClass('alert alert-dismissible alert-info').html('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + ResponseJSON.alertMessage);
                }
                setTimeout(this.clearNotificationMessage, 5000);
            },

            clearNotificationMessage() {
                $("#NotificationArea").children(".alert").fadeOut("slow", function () {
                    $(this).addClass('hidden');
                });
            },

            isFormEmpty() {
                var serializedArray = $("#SiteMeasrSearchForm").serializeArray(), count = 0;
                serializedArray.map(function (input) {
                    if (input.value.replace(/\s/g, "").length === 0) {
                        count++;
                    }
                });
                if (count === serializedArray.length) {
                    return true;
                } else {
                    return false;
                }
            },

            initialiseDataTable(form){
                this.showFormOverlay = true;
                dataTableObject = $("#SiteMeasurementReport").DataTable({
                    destroy: true,
                    processing: true,
                    serverSide: true,
                    ajax: {
                        "url":  PostUrl,
                        "type": "POST",
                        "data": form,
                        "dataSrc": function (response) {
                            VueInstance.showFormOverlay = false;
                            if (response.recordsTotal < 1) {
                                $("#SiteMeasureListBox").addClass('hidden');
                                VueInstance.PopulateNotifications({
                                status:"info",
                                alertMessage:"No Site Measurements found."
                                });
                            } else {
                                $("#SiteMeasureListBox").removeClass('hidden');
                            }
                            return response.data;
                        }
                    },
                    paging: true,
                    lengthChange: false,
                    searching: false,
                    order: [],
                    info: true,
                    autoWidth: false,
                    oLanguage: {
                        sEmptyTable: "No Site Measurements found."
                    },

                    "columns": [
                        {
                            "data": "SNo",
                            "width": "6%",
                            "orderable": false,
                            "className": "text-vertical-align text-center"
                        },
                        {
                            "data": "Project",
                            "width": "20%",
                            "orderable": false,
                            "className": "text-vertical-align text-center"
                        },
                        {
                            "data": "Description",
                            "width": "22%",
                            "orderable": false,
                            "className": "text-vertical-align text-center"
                        },
                        {
                            "data": "Rooms",
                            "width": "22%",
                            "orderable": false,
                            "className": "text-vertical-align text-center"
                        },
                        {
                            "data": "Status",
                            "width": "20%",
                            "orderable": false,
                            "className": "text-vertical-align text-center"
                        },
                        {
                            "data": null,
                            "width": "10%",
                            "className": "text-vertical-align text-center",
                            "orderable": false,
                            "render": function (data, type, full, meta) {
                                var ReturnData = "";
                                if(data.Url.ShowEdit === "Yes"){
                                    ReturnData = ReturnData + `<a href="${data.Url.Edit}" title="Edit Measurement"><i class="fa fa-pencil text-black mr-rt-4" aria-hidden="true">
                                        </i></a>`;
                                }
                                ReturnData = ReturnData + `<a href="${data.Url.View}" title="View Measurement"><i class="fa fa-eye text-black mr-rt-4" aria-hidden="true">
                                        </i></a>`;
                                if(data.Url.ShowRoomCals === "Yes"){
                                    ReturnData = ReturnData + `<a href="${data.Url.RoomCalsUrl}" title="View Calculations"><i class="fa fa-fw fa-calculator text-black" aria-hidden="true">
                                        </i></a>`;
                                }
                                return ReturnData;
                            }
                        }
                    ]
                });
            }
        }
    });    
    
    //Define form validation for Site measurement search
    formValidator = $("#SiteMeasrSearchForm").validate({
        submitHandler: function (form, event) {
            event.preventDefault();
            $("#NotificationArea").html("");
            $("#SiteMeasureSubmit").trigger('blur');
            
            // Check whether form is empty.
            if (VueInstance.isFormEmpty()) {
                $("#SiteMeasureListBox").addClass('hidden');
                VueInstance.PopulateNotifications({
                    status:"warning",
                    alertMessage:"No search term given to start searching."
                });
            } else {
                var serializedArray = $(form).serializeArray(), formData = [];

                serializedArray.map(function (element, index) {
                    if (element.value.length > 0) {
                        formData[element.name] = element.value;
                    }
                });
                
               VueInstance.initialiseDataTable(formData);
            }
        }
    });

    //Reset form on form reset event.
    $("#SiteMeasrSearchForm").on('reset', function() {
        formValidator.resetForm();
        jquery('#Project, #CreatedBy').val(null).trigger('change');
        $("#SiteMeasureListBox").addClass('hidden');
    });
});