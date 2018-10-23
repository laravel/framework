
/**
 *  
 * Work initiate checklist script
 * 
 **/

/* Global variables */

var formValidator;

/** Include needed packages **/
require('../../bootstrap');
require('magnific-popup');
require('select2');
// Resolve Jquery library conflict
let jquery = require('jquery');

//Success/Failure message overlay
import OverlayNotification from '../../components/overlayNotification';

/** Initialize Vue instance **/
const vueInstance = new Vue({
    el: '#WorkChecklistPage',
    data: {
        User: UserData,
        CheckListPageRoute: CreateCheckListRoute,
        ViewType: "",
        HeaderTitle: "",
        CurrentProject: Project,
        SiteData: SiteData,
        SiteCity: SiteCity,
        FormOverLay: true,
        NotificationIcon: "",
        NotificationMessage: "",
        ShowOverlay: false,
        CustomerUser: CustomerUser, // Site Project customer
        ReportsRoute: CheckistsListRoute,
        IsFormSubmitted: false,
        ProjectShortCode: ProjectShortCode,
        CheckListId: null,
        ChecklistTypes: [],
        ShowFetchOVerlay: false
    },
    created() {
        this.ViewType = ViewType;
        this.CheckListId = CheckListId;
    },
    mounted() {
        this.initializeSelect2();
        (this.ViewType === "Select") ? this.HeaderTitle = "Select a Project" : "";
        (this.CheckListId) ? $("#SubmitData").text("Update") : $("#SubmitData").text("Submit");
        this.createSiteAddressHtml();
        $(document).keyup(function (event) {
            if (event.key === "Escape") {
                vueInstance.clearOverLayMessage();
            }
        });
    },
    components: {
        'overlay-notification': OverlayNotification
    },
    computed: {
        filterUserDetails() {
            if(!_.isEmpty(this.SiteData)) {
                return false;
            }
            return true;
        }
    },
    methods: {
        //Get Site Address
        createSiteAddressHtml() {
            let projectName = this.SiteData.ProjectName;
            let unitNo = this.SiteData.UnitNo;
            let landmark = this.SiteData.SiteAddress;
            let city = this.SiteCity;
            let siteAddress = 
                `<div class="col-md-4 col-md-offset-2">
                    <div class="form-group">
                        <label></label>
                        <div class="site-address-block text-right">
                            <h5><b>`+projectName +`,&nbsp;`+unitNo+`</b></h5> 
                            <h5>`+ (landmark ? landmark + ', ' : '') + city +`</h5>
                        </div>
                    </div>
                </div>`;
            $("#BContactPName").parent().parent().append(siteAddress);
        },
        //Select2 initialisation 
        initializeSelect2() {
            let self = this;
            jquery('#Project').select2({
                placeholder: "Select a Project",
                language: {
                    noResults: function () {
                        return "No Projects found";
                    }
                }
            }).on("change", function () {
                self.onProjectSelect(this.value);
            });
            
            jquery('#CheckedBy').select2({
                placeholder: "Select a User",
                language: {
                    noResults: function () {
                        return "No Users found";
                    }
                }
            });
            
            jquery('#Type').select2({
                placeholder: "Select a Type",
                language: {
                    noResults: function () {
                        return "No Types found";
                    }
                }
            }).on("change", function() {
                self.onTypeSelect(this.value);
            });
        }, 
        //Function to call on Project Select event
        onProjectSelect(projectId) {
            this.ShowFetchOVerlay = true;
            $.ajax({
                url: "/checklist/getChecklistTypes/" + projectId,
                type: 'GET',
                dataType: 'json'
            })
            .done(function (response) {
                if (response.status === "success") {
                    if(response.ChecklistTypes.length === 0) {
                        this.populateOverlayMessage({status: "info", message: "You have created all types of checklists already!"});
                    }
                    this.ChecklistTypes = response.ChecklistTypes;
                } else {
                    this.populateOverlayMessage(response);
                }
            }.bind(this))
            .fail(function () {
                this.populateOverlayMessage({status: "error", message: AlertData["10077"]});
            }.bind(this))
            .always(function () {
                this.ShowFetchOVerlay = false;
            }.bind(this));
        },
        //Function to call on Type Select event
        onTypeSelect(typeId) {
            //Get the checklists
            window.location = this.CheckListPageRoute + "/" + $("#Project").val() + "/" + typeId;
        },
        //Populates Success/Failure messages
        populateOverlayMessage(response) {
            this.NotificationMessage = response.message;
            this.NotificationIcon = response.status;
            this.FormOverLay = false;
            if (response.status === "success") {
                this.NotificationIcon = "check-circle";
            } else if (response.status === 'error') {
                this.NotificationIcon = "ban";
            }
        },
        //Clears Success/Failure messages 
        clearOverLayMessage() {
            this.FormOverLay = true;
            this.NotificationMessage = "";
            this.NotificationIcon = "";
            if (this.IsFormSubmitted && _.isNull(this.CheckListId)) {
                window.location = this.ReportsRoute;
            }
        },
        //Reset event handler
        resetHandler(FormId) {
            $("#" + FormId).off("reset").on('reset', function (event) {
                event.preventDefault();
                if (formValidator) {
                    formValidator.resetForm();
                } else {
                    initializeValidator();
                    formValidator.resetForm();
                }
                if (_.isNull(vueInstance.CheckListId)) {
                    $(':checkbox, :radio').removeAttr('aria-required').prop('checked', false);
                }
                $('#WorkStartTime').timepicker('setTime', '09:00 AM');
                $('#WorkEndTime').timepicker('setTime', '06:00 PM');
            });
        },
        //Show uploaded files below it's uploads
        showUploadedFiles(files, id, titleId) {
            $("#"+id).removeClass("hidden");
            if (!_.isNull(titleId)) {
                $("." + titleId).removeClass("hidden");
            }
            let interiorWorkNoc = '';
            interiorWorkNoc += `<div class="pull-left uploaded-files">`;
            for (let image = 0; image < files.length; image++) {
                if (files[image] !== 'undefined') {
                    interiorWorkNoc += `<span class="label label-warning mr-rt-4">${files[image]}</span>`;
                }
            }
            interiorWorkNoc += `</div>`;
            if (interiorWorkNoc !== '' && interiorWorkNoc !== 'undefined') {
                $("#"+id).append(interiorWorkNoc);
            }
        },
        //Function to move scroll at top
        scrollFunction() {
            if ($(window).scrollTop() > 20 || document.documentElement.scrollTop > 20) {
                $("#ChecklistGoToTop").removeClass("hidden");
            } else {
                $("#ChecklistGoToTop").addClass("hidden");
            }
        }
    }
});

$(document).ready(function () {
    initializeValidator();
    vueInstance.resetHandler("CheckListForm");
    $(window).scroll(function(){
        vueInstance.scrollFunction();
    });
    $("#ChecklistGoToTop").on('click', function (event) {
        event.preventDefault();
        $(window).scrollTop(0);
    });
    
    $('#WorkStartTime').timepicker({
        timeFormat: 'h:i A',
        showInputs: false,
        defaultTime: '09:00 AM'
    });
    $('#WorkEndTime').timepicker({
        timeFormat: 'h:i A',
        showInputs: false,
        defaultTime: '6:00 PM'
    });
    $("#WorkEndTime").on('change', function () {
        localStorage.setItem("prevEndTime", this.value);
    });
    if (window.localStorage) {
        localStorage.setItem("prevEndTime", $("#WorkEndTime").val());
    } else {
        console.warning("No localStorage exists in your browser. Please update your browser.");
    }
    $("#NoCForInteriorWorkYes").off("click").on('click', function () {
        $("#NoCPerCopyForIntWork").parent().removeClass("hidden");
        $(".interiorwork-noc-title").removeClass("hidden");
    });
    $("#NoCForInteriorWorkNotApplicable, #NoCForInteriorWorkNo").off("click").on('click', function () {
        $("#NoCPerCopyForIntWork").val("");
        $("#NoCPerCopyForIntWork").parent().addClass("hidden");
        $(".interiorwork-noc-title").addClass("hidden");
    });
    $("#NoCForCivilWorkYes").off("click").on('click', function () {
        $("#NoCPerCopyForCivilWork").parent().removeClass("hidden");
        $(".civilwork-noc-title").removeClass("hidden");
    });
    $("#NoCForCivilWorkNo, #NoCForCivilWorkNotApplicable").off("click").on('click', function () {
        $("#NoCPerCopyForCivilWork").val("");
        $("#NoCPerCopyForCivilWork").parent().addClass("hidden");
        $(".civilwork-noc-title").addClass("hidden");
    });
    $("#GoodUsePermissionYes").off("click").on('click', function () {
        $("#GoodUsePerHeaderTitle").removeClass("hidden");
        $("#GoodsUseContactName, #GoodsUseContactNumber").parent().removeClass("hidden");
    });
    $("#GoodUsePermissionNo").off("click").on('click', function () {
        $("#GoodsUseContactName, #GoodsUseContactNumber").val("");
        $("#GoodUsePerHeaderTitle").addClass("hidden");
        $("#GoodsUseContactName, #GoodsUseContactNumber").parent().addClass("hidden");
    });
    $("#SecPassNeededYes").off("click").on('click', function () {
        $("#ProcToGetPasses").parent().removeClass("hidden");
    });
    $("#SecPassNeededNo, #SecPassNeededNotApplicable").off("click").on('click', function () {
        $("#ProcToGetPasses").val("");
        $("#ProcToGetPasses").parent().addClass("hidden");
    });
    $("#SocietyGuidelinesYes").off("click").on('click', function () {
        $("#SocietyGText, #SocietyGDoc").parent().removeClass("hidden");
    });
    $("#SocietyGuidelinesNo").off("click").on('click', function () {
        $("#SocietyGText, #SocietyGDoc").val("");
        $("#SocietyGText, #SocietyGDoc").parent().addClass("hidden");
    });
    if($("#GoodsUseContactName, #GoodsUseContactNumber").val()) {
        $("#GoodUsePermissionYes").trigger("click");
    }
    if($("#SocietyGText, #SocietyGDoc").val()) {
        $("#SocietyGuidelinesYes").trigger("click");
    }
    if($("#ProcToGetPasses").val()) {
        $("#SecPassNeededYes").trigger("click");
    }
    if(!_.isNull(vueInstance.CheckListId)) {
        let resetBtn = $("#ChecklistResetBtn");
        resetBtn.html("Undo");
        resetBtn.on("click", function() {
            location.reload();
        });
    }
    //Calculating one year ahead time in milli-seconds from current time.
    var currentDate = new Date(),currentTime = currentDate.getTime(),OneYearAhead = new Date(currentTime + 31622400000);
    $("#CheckedDate").datepicker({
        autoclose: true,
        startDate: new Date(),
        format: "dd-M-yyyy",
        endDate: new Date(OneYearAhead)
    });
    //Set Todays date as default date for Checked Date if date is null
    if (!$("#CheckedDate").val()) {
        $("#CheckedDate").datepicker("setDate", new Date());
    }
    // Append Interior work Noc copies after upload element
    if (InteriorWorkNoc.length > 0) {
        vueInstance.showUploadedFiles(InteriorWorkNoc, "InteriorWorkNoc", "interiorwork-noc-title");
    }
    // Append Civil work Noc copies after upload element
    if (CivilWorkNoc.length > 0) {
       vueInstance.showUploadedFiles(CivilWorkNoc, "CivilWorkNoc", "civilwork-noc-title");
    }
    // Append Society guidelines docs after upload element
    if (SocietyGDocs.length > 0) {
       vueInstance.showUploadedFiles(SocietyGDocs, "SocietyGDoc", null);
    }
});

var initializeValidator = function () {
    formValidator = $("#CheckListForm").validate({
        ignore: "",
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
            if ($(element).attr('id') === "WorkStartTime") {
                error.appendTo($(element).parent().parent());
            } else {
                error.appendTo($(element).parent());
            }
        },
        rules: {
            BContactPName: {
                CheckConsecutiveSpaces: true,
                minlength: 3,
                ValidateAlphabet: true
            },
            BContactPNumber: {
                number: true,
                CheckConsecutiveSpaces: true,
                minlength: 10,
                maxlength: 10
            },
            HandoverDone: {
                required: true
            },
            NoCForCivilWork: {
                required: true
            },
            NoCForInteriorWork: {
                required: true
            },
            SecDepGiven: {
               required: true
            },
            PowerSupplyAvailble: {
                required: true
            },
            LiftAvForMatMovement: {
                required: true
            },
            GoodUsePermission: {
                required: true
            },
            GoodsUseContactName: {
                required: {
                    depends: function () {
                        if ($('input[name=GoodUsePermission]:checked').val() === "Yes") {
                            return true;
                        } else {
                            return false;
                        }
                    }
                },
                CheckConsecutiveSpaces: true,
                minlength: 3,
                ValidateAlphabet: true
            },
            GoodsUseContactNumber: {
                required: {
                    depends: function () {
                        if ($('input[name=GoodUsePermission]:checked').val() === "Yes") {
                            return true;
                        } else {
                            return false;
                        }
                    }
                },
                number: true,
                CheckConsecutiveSpaces: true,
                minlength: 10,
                maxlength: 10
            },
            ProcToGetPasses: {
                required: {
                    depends: function () {
                        if ($('input[name=SecPassNeeded]:checked').val() === "Yes") {
                            return true;
                        } else {
                            return false;
                        }
                    }
                },
                minlength: 3,
                CheckConsecutiveSpaces: true
            },
            SecPassNeeded: {
                required: true
            },
            NeedToSubIdCard: {
                required: true
            },
            IsWaterAvaialable: {
                required: true
            },
            CanWorkStayAtNight: {
                required: true
            },
            CanWorkPrepareFood: {
                required: true
            },
            WorkersRestRoom: {
                CheckConsecutiveSpaces: true,
                minlength: 3,
                alphaNumericWithSpace: true             
            },
            ParkingLocation: {
                CheckConsecutiveSpaces: true,
                minlength: 3,
                alphaNumericWithSpace: true
            },
            GarbageDisGuidelines: {
                CheckConsecutiveSpaces: true,
                minlength: 3
            },
            SocietyGuidelines: {
                required: true
            },
            WorkStartTime: {
                CompareTime: true
            },
            "NoCPerCopyForIntWork[]": {
                checkMultipleUploadFilesExtensions: true,
                checkPerFileSizeInMultipleFiles: true
            },
            "NoCPerCopyForCivilWork[]": {
                checkMultipleUploadFilesExtensions: true,
                checkPerFileSizeInMultipleFiles: true
            },
            "SocietyGDoc[]": {
                checkMultipleUploadFilesExtensions: true,
                checkPerFileSizeInMultipleFiles: true
            }
        },
        messages: {
            HandoverDone: {
                required: "This field can't be blank."
            },
            NoCForCivilWork: {
                required: "This field can't be blank."
            },
            NoCForInteriorWork: {
                required: "This field can't be blank."
            },
            SecDepGiven: {
                required: "This field can't be blank."
            },
            PowerSupplyAvailble: {
                required: "This field can't be blank."
            },
            LiftAvForMatMovement: {
                required: "This field can't be blank."
            },
            GoodUsePermission: {
                required: "This field can't be blank."
            },
            SecPassNeeded: {
                required: "This field can't be blank."
            },
            NeedToSubIdCard: {
                required: "This field can't be blank."
            },
            IsWaterAvaialable: {
                required: "This field can't be blank."
            },
            CanWorkStayAtNight: {
                required: "This field can't be blank."
            },
            CanWorkPrepareFood: {
                required: "This field can't be blank."
            },
            SocietyGuidelines: {
                required: "This field can't be blank."
            },
            GoodsUseContactName: {
                required: "Please enter Name."
            },
            GoodsUseContactNumber: {
                required: "Please enter Mobile no."
            },
            ProcToGetPasses: {
                required: "Please Specify the process."
            }
        },
        submitHandler: function (form) {
            $("#SubmitData").trigger('blur');
            $(".alert").addClass('hidden');
            vueInstance.ShowOverlay = true;
            var finalFormData = new FormData(form);
            let url = window.location.href;
            finalFormData.append("Customer", vueInstance.CustomerUser);
            finalFormData.append("ProjectShortCode", vueInstance.ProjectShortCode);
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                processData: false,
                contentType: false,
                data: finalFormData
            })
            .done(function (response) {
                if (response.status === "success") {
                    vueInstance.IsFormSubmitted = true;
                    //Clear uploads
                    $("#NoCPerCopyForIntWork,#NoCPerCopyForCivilWork,#SocietyGDoc").val("");
                    vueInstance.populateOverlayMessage(response);
                } else {
                    vueInstance.populateOverlayMessage(response);
                }
            })
            .fail(function (jqXHR) {
                if (jqXHR.status === 200) {
                    vueInstance.populateOverlayMessage({status: "error", message: AlertData["10077"]});
                } else {
                    vueInstance.populateOverlayMessage({status: "error", message: AlertData["10077"]});
                }
            })
            .always(function () {
                vueInstance.ShowOverlay = false;
               $("#SubmitData").trigger('blur');
            });
        }
    });
};