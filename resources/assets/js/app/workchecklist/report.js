//Form url's from controller
let formValidator, dataTableObject;

//Include needed packages
require('../../bootstrap');
require('select2');

//Resolve Jquery library conflict
let jquery = require('jquery');

let ViewDropDown = `<span class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="" role="button" aria-haspopup="true" aria-expanded="false">
    <i class="fa fa-caret-square-o-down" aria-hidden="true"></i>
    </a>
    <ul class="dropdown-menu" aria-labelledby="SearchResultsDropdownMenu">`;

$(document).ready(function () {
    const vueInstance = new Vue({
        //Vue root element
        el: '#WorkChecklistReportsPage',
        
        //Vue data variables
        data: {
            showFormOverlay: false
        },
        
        //Vue object life cycle hook
        mounted() {              
            //Select2 Search initialization
            this.initializeSelect2();
            //Open Checklist view modal
            this.openChecklistModal();
        }, 
        
        //Vue methods
        methods: { 
            initializeSelect2() {
                //Select2 dropdown initialization
                jquery("#Project").select2({ placeholder: "Select Project", language: {
                    noResults: function () {
                        return "No Projects found";
                    }
                }});
                jquery("#User").select2({ placeholder: "Select User", language: {
                    noResults: function () {
                        return "No Users found";
                    }
                }});
            },
            openChecklistModal() {
                $(document).on('click', '.view-checklist-link', function (event) {
                    event.preventDefault();
                    $("#ChecklistViewModal").modal("show");
                    $("#ChecklistViewModal .modal-content").html('<div class="modal-loader"><div class="large loader"></div><div class="loader-text">Fetching View</div></div>');
                    $.ajax({
                        url: this.href,
                        type: 'GET',
                        dataType: 'html'
                    })
                    .done(function (response) {
                        $("#ChecklistViewModal .modal-content").html(response);
                    })
                    .fail(function () {
                        $("#ChecklistViewModal .modal-content").html('<div class="alert alert-error"><div>' + AlertData[10077] + '</div></div>');
                    });
                });
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

                } else if (ResponseJSON.status === 'info') {
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
                var serializedArray = $("#WorkChecklistsSearchForm").serializeArray(), count = 0;
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
                dataTableObject = $("#ChecklistReport").DataTable({
                    destroy: true,
                    processing: true,
                    serverSide: true,
                    ajax: {
                        "url":  PostUrl,
                        "type": "POST",
                        "data": form,
                        "dataSrc": function (response) {
                            vueInstance.showFormOverlay = false;
                            if (response.recordsTotal < 1) {
                                if (response.draw < 2) {
                                    $("#ChecklistBox").addClass('hidden');
                                    vueInstance.PopulateNotifications({
                                        status: "info",
                                        alertMessage: "No Checklists found."
                                    });
                                }
                            } else {
                                $("#ChecklistBox").removeClass('hidden');
                            }
                            return response.data;
                        }
                    },
                    paging: true,
                    lengthChange: false,
                    searching: true,
                    order: [],
                    info: true,
                    autoWidth: false,
                    oLanguage: {
                        sEmptyTable: "No Checklists found."
                    },

                    "columns": [
                        {
                            "data": "SNo",
                            "orderable": false
                        },
                        {
                            "data": "Project",
                            "orderable": false
                        },
                        {
                            "data": "ChecklistType",
                            "orderable": false
                        },
                        {
                            "data": "CreatedBy",
                            "orderable": false
                        },
                        {
                            "data": "UpdatedBy",
                            "orderable": false
                        },
                        {
                            "data": "CreatedOn",
                            "orderable": false
                        },
                        {
                            "data": "UpdatedOn",
                            "orderable": false
                        },
                        {
                            "data": null,
                            "orderable": false,
                            "render": function (data, type, full, meta) {
                                var ReturnData = ViewDropDown;
                                if (data.EditUrl) {
                                    ReturnData = ReturnData + `<li><a href="${data.EditUrl}"><i class="fa fa-pencil" aria-hidden="true">
                                        </i> Edit Checklist</a></li>`;
                                }
                                ReturnData = ReturnData + `<li><a href="${data.ViewUrl}" class="view-checklist-link"><i class="fa fa-eye" aria-hidden="true">
                                    </i> View Checklist</a></li>`;
                                return ReturnData;
                            }
                        }
                    ]
                });
            }
        }
    });    
    
    //Define form validation for Checklists search
    formValidator = $("#WorkChecklistsSearchForm").validate({
        submitHandler: function (form, event) {
            event.preventDefault();
            $("#NotificationArea").html("");
            $("#SubmitBtn").trigger('blur');
            
            // Check whether form is empty.
            if (vueInstance.isFormEmpty()) {
                $("#ChecklistBox").addClass('hidden');
                vueInstance.PopulateNotifications({
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
                
               vueInstance.initialiseDataTable(formData);
            }
        }
    });

    //Reset form on form reset event.
    $("#WorkChecklistsSearchForm").on('reset', function() {
        formValidator.resetForm();
        jquery('#Project, #User').val(null).trigger('change');
        $("#ChecklistBox").addClass('hidden');
    });
});