/**
 *  
 * Add Laminates Combination script
 * 
 **/

/** Global variables **/
let notificationTimeout = 10000, notificationTimeoutID, ValidatorObj;

/** Include needed packages **/
require('../../../bootstrap');
const debounce = require('lodash/debounce');
require('magnific-popup');
require('select2');

// Resolve JQuery library conflict
var jquery = require('jquery');

// Register components
//Shows User primary info...
import UserInformation from '../../../components/catalogue/designer/userInformation';

import OverlayNotification from '../../../components/overlayNotification';
// Component to select laminates category(Hechpe, Generel, Shortlisted) options
import combinationOptions from '../../../components/catalogue/designer/CombinationOptions';
// Hechpe Suggestions
import laminatesSuggestions from '../../../components/catalogue/customer/LaminatesSuggestions';
// Hechpe laminates selected for combination
import selectedHechpeLaminates from '../../../components/catalogue/customer/SelectedHechpeLams';
// Generel search laminates selected for combination
import selectedGeneralSearchLaminates from '../../../components/catalogue/customer/SelectedGenLams';
// Shortlisted Suggestions
import shortlistedSuggestions from '../../../components/catalogue/customer/ShortlistedLaminates';
// Shortlisted laminates selected for combination
import selectedShortlistedSuggestions from '../../../components/catalogue/customer/SelectedShortlistedSuggestions';
// Maximum laminates selection limit alert
import maxLaminatesSelectionAlert from '../../../components/catalogue/customer/MaxCombCountReachedAlert';

/** Initialize Vue object **/
const VueInstance = new Vue({
    el: '#AddNewSuggestionPage',
    data: {
        projects: Projects, // Customers projects
        RoomArea: [RoomArea], //Rooms for selected Project
        CurrentLaminate: CurrentLaminate, // Contains current to be shortlisted laminate
        CdnUrl: CDNURL, // S3 cloud storage URL
        ProjectId: ProjectId, // Site project id
        HechpeSuggs: [], // Contains laminates suggested by Hechpe
        IsSuggestionsExists: false, // Flag to show no results found message
        PickedOption: "", // Radion button selected option
        SearchResult: [], // Generel laminate search result
        SearchString: "", // Model to store search string
        isGenSuggExists: false,
        ShortlistedSuggestions: [], // Shortlisted laminates
        Combination: [], // Stores shortlisted laminates as combination
        CombinationCount: CombinationCount, // Combination max laminates count
        ShowShortListLaminateLoader: false, // Toggle submit combination ajax request loader
        FormOverLay: true, // Toggle for notification loader
        NotificationIcon: "",
        NotificationMessage: "", //Message for notification loader
        CustomerDetails: projectData, // Stores  Customer details
        CreateCatalogueRoute: CreateCatalogueRoute, //Stores create Catalogue Route
        brands: Brands, //Stores Material Brands
        SearchBtnClick: false,
        SwatchImageZipDownloadRoute: SwatchImageZipDownloadRoute, // Download All Swatch Images as Zip
        SwatchImageDownloadRoute: SwatchImageDownloadRoute, // Download single swatch image
        SurfaceCategories: Categories,
        rooms: [],
        SurfaceFinishs: Finishs,
        showSearchFilter: false

    },
    components: {
        'user-information': UserInformation,
        'combination-options': combinationOptions,
        'laminates-suggestions-list': laminatesSuggestions,
        'selected-laminates': selectedHechpeLaminates,
        'selected-general-laminates': selectedGeneralSearchLaminates,
        'shortlisted-suggestions-list': shortlistedSuggestions,
        'selected-shortlisted-laminates': selectedShortlistedSuggestions,
        'max-laminates-selection-alert': maxLaminatesSelectionAlert,
        'overlay-notification': OverlayNotification
    },
    // Called when Vue object creates
    created() {
        this.ShortlistedSuggestions = this.addProperty(SurfaceMaterialCatalog); // Assign shortlisted suggestion to vues variable
    },
    // Called when Vue object mounts
    mounted() {
        this.HechpeSuggs = HechpeSuggestions;
        // Delete current laminate from ShortlistedSuggestions array
        this.deleteObjectsFromArray(this.ShortlistedSuggestions, [{LaminateId: this.CurrentLaminate.LaminateId}]);
        if (this.ProjectId != "") {
            this.fetchRooms();
        }
    },
    computed: {
        // Filters Hechpe laminates suggestions
        filteredSuggestions() {
            // Remove duplicate objects
            return _.uniqBy(this.HechpeSuggs, function (e) {
                return e.LaminateId;
            });
        },
        // Filters generel laminates suggestions
        fileteredLaminates() {
            // Remove duplicate objects
            $(".button-search").trigger("blur");
            return _.uniqBy(this.SearchResult, function (e) {
                return e.LaminateId;
            });
        },
        // Filters Shortlisted laminates
        fileteredShortlistedSuggestions() {
            let vm = this;
            if (vm.ShortlistedSuggestions.length > 0) {
                // Check for add button visibility and toggle acc. to combination array(ShortlistedSuggsCombination)
                for (let i = 0; i < vm.ShortlistedSuggestions.length; i++) {
                    // Delete current laminate from ShortlistedSuggestions array
                    if (vm.ShortlistedSuggestions[i].LaminateId === this.CurrentLaminate.LaminateId) {
                        vm.ShortlistedSuggestions[i].Active = true;
                    }
                    vm.ShortlistedSuggestions[i].Id = vm.ShortlistedSuggestions[i].LaminateId;
                    vm.ShortlistedSuggestions[i].FullSheetmage = vm.ShortlistedSuggestions[i].FullSheetImage;
                    for (let j = 0; j < vm.Combination.length; j++) {
                        if (vm.Combination[j].LaminateId === vm.ShortlistedSuggestions[i].LaminateId) {
                            vm.ShortlistedSuggestions[i].Active = true;
                        }
                    }
                }
            } else {
                vm.ShortlistedSuggestions = [];
            }
            return _.uniqBy(vm.ShortlistedSuggestions, function (e) {
                return  e.DesignNo || e.SearchTags || e.Id;
            });
        },

    },
    watch: {
        // Watcher to check radio PickedOption state asyncronously
        PickedOption: function (option) {
            if (option === "HechpeSuggs") {
                if (this.HechpeSuggs !== null) {
                    // Check for add button visibility and toggle acc. to combination array(HechpeSuggComb)
                    for (let i = 0; i < this.HechpeSuggs.length; i++) {
                        this.HechpeSuggs[i].Active = false;
                        for (let j = 0; j < this.Combination.length; j++) {
                            if (this.Combination[j].LaminateId === this.HechpeSuggs[i].LaminateId) {
                                this.HechpeSuggs[i].Active = true;
                            }
                        }
                    }
                }
                // Initiailze image pop up
                this.imagePopup();
                // Clear Generel suggestions Search values 
                this.clearSearchValues();
            } else if (option === "PickFromShortlist") {
                if (this.ShortlistedSuggestions.length > 0) {
                    // Check for add button visibility and toggle acc. to combination array(ShortlistedSuggsCombination)
                    for (let i = 0; i < this.ShortlistedSuggestions.length; i++) {
                        this.ShortlistedSuggestions[i].Active = false;
                        for (let j = 0; j < this.Combination.length; j++) {
                            if (this.Combination[j].LaminateId === this.ShortlistedSuggestions[i].Id) {
                                this.ShortlistedSuggestions[i].Active = true;
                            }
                        }
                    }
                }
                // Initiailze image pop up
                this.imagePopup();
                // Clear Generel suggestions Search values
                this.clearSearchValues();
            }
            if (option === "GenSuggs") {
                this.showSearchFilter = true;
            }
        }
    },
    methods: {
        // Get laminate for given Design No/ Name/ Search Tags
        getLaminate(laminates, searchString) {
            if (laminates.length > 0) {
                let result = _.filter(laminates, function (laminate) {
                    return ((laminate.DesignName.toLowerCase().indexOf(searchString.replace(/\s\s+/g, ' ').toLowerCase()) !== -1) || (laminate.DesignNo.toLowerCase().indexOf(searchString.replace(/\s\s+/g, ' ').toLowerCase()) !== -1) || (laminate.SearchTags.replace(',', ' ').toLowerCase().indexOf(searchString.toLowerCase()) !== -1) || (laminate.BrandName.replace(/\s\s+/g, ' ').toLowerCase().indexOf(searchString.toLowerCase()) !== -1));
                }.bind(this));
                return result;
            }
            return {};
        },
        // Compare two arrays & get unique items
        comparer(otherArray) {
            return function (current) {
                return otherArray.filter(function (other) {
                    return other.LaminateId === current.LaminateId;
                }).length === 0;
            };
        },
        // Delete objects from array of objects
        deleteObjectsFromArray(firstArray, secondArray) {
            if (firstArray.length > 0) {
                let onlyInA = firstArray.filter(this.comparer(secondArray));
                let onlyInB = secondArray.filter(this.comparer(firstArray));
                let result = onlyInA;
                this.ShortlistedSuggestions = result;
            }
        },

        // Add new property Active to toggle Add (laminates) button
        addProperty(laminates) {
            if (laminates.length > 0) {
                laminates.forEach(function (obj) {
                    obj.Active = false;
                });
            }
            return laminates;
        },
        // Returns surface glossiness value
        glossinessValue(id) {
            return (_.find(this.SurfGlossinessVals, {'Id': id})).Name;
        },
        // Returns Quick estimate rooms using project
        fetchRooms() {
            let self = this;
            $(".alert").addClass("hidden");
            self.RoomArea = [];
            if (self.ProjectId !== 'undefined' && self.ProjectId !== '') {
                self.ShowOverlay = true;
                self.OverlayMessage = "Fetching Rooms";
                axios.get('/designer/get/qerooms/' + self.ProjectId)
                        .then(function (response) {
                            if (!_.isEmpty(response.data)) {
                                self.RoomArea = response.data;
                            } else {
                                self.RoomArea = [];
                            }
                        })
                        .catch(function (error) {
                            self.onFail(error);
                        })
                        .then(() => {
                            self.ShowOverlay = false;
                        });
            } else {
                self.RoomArea = [];
            }
        },
        fetchProjectDetails(onChange = false) {
            window.location.href = this.CreateCatalogueRoute + '/' + VueInstance.CurrentLaminate.LaminateId + '/' + this.ProjectId

        },
        // Initializes image Pop up 
        imagePopup() {
            this.$nextTick(function () {
                jquery(".image-link").magnificPopup({
                    delegate: 'a',
                    type: 'image'
                });
            });
        },
        // Resets Generel search variables 
        clearSearchValues() {
            // this.GenerelSrchCombinations = [];
            this.SearchResult = [];
            this.SearchString = "";
            this.showSearchFilter = false;
        },
        
        addToCombination(laminateId, laminate, trackingArray) {
            let laminateObj = {
                    "Id": laminateId,
                    "Laminate": laminate,
                    "Suggestions": trackingArray
                };
            this.addLamToCombination(laminateObj);
        },

        // Creates laminates combination 
        addLamToCombination(LaminateObj) {
            // Check Laminates combination max. count
            if (this.Combination.length === (this.CombinationCount - 1)) {
                // If crossed max selection limit show alert
                $("#MaxLamCombCountAlert").modal("show");
                return;
            }
            // Check for array length & object existence in array
            let IsObjExists = _.find(this.Combination, function (obj) {
                return obj.LaminateId === LaminateObj.LaminateId;
            });
            // If Object does not exists in array (duplicate object)...
            if (typeof IsObjExists === "undefined") {
                // 1. Hide the add button
                let laminate = _.find(LaminateObj.Suggestions, function (obj) {
                    return obj.LaminateId === LaminateObj.Id;
                });
                if (typeof laminate !== "undefined") {
                    laminate.Active = true;
                }
                // 2. Push object into array
                this.Combination.push(LaminateObj.Laminate);
            }
        },
        // Removes laminate from selected list (combination)
        deleteLamFromCombination(RemoveLamObj) 
        {            
            let IsObjExists;
            // Check for array length & object existence in array and delete it
            if (this.Combination.length > 0) {
                IsObjExists = this.Combination.splice(_.findIndex(this.Combination, function (item) {
                    return item.LaminateId === RemoveLamObj.Id;
                }), 1);
            }
            // If Object is deleted...
            if (IsObjExists.length > 0) {
                let laminate = _.find(RemoveLamObj.Suggestions, function (obj) {
                    return obj.LaminateId === RemoveLamObj.Id;
                });
                // Show the add button 
                if (typeof laminate !== "undefined") {
                    let objIndex = RemoveLamObj.Suggestions.findIndex((obj => obj.LaminateId === RemoveLamObj.Id));
                    if (objIndex !== -1) {
                        RemoveLamObj.Suggestions[objIndex].Active = false;
                    }
                }
            }
            // Checking Active status of btn for autocomplete search result
            if (this.SearchResult.length > 0) {
                let objIndex = this.SearchResult.findIndex((obj => obj.LaminateId === RemoveLamObj.Id));
                if (objIndex !== -1) {
                    this.SearchResult[objIndex].Active = false;
                }
            }
            $(".ui-tooltip").addClass("hidden");
        },
        // Search Laminates
        searchLaminates() {
            if (this.SearchString.length >= 3) {
                $(".alert").addClass('hidden');
                let requestUrl = $("#SearchLamsBtn").attr('data-api-end-point');
                this.getLaminates(requestUrl);
            }
        },

        // Get laminate for given Design No, Name, Search Tags
        getLaminates(requestUrl) {
            let vueRef = this;
            vueRef.FormOverLay = false;
            vueRef.NotificationMessage = "Fetching Laminates";
            $(".alert").addClass('hidden');
            let requestPostData = {
                "currentLaminateId": vueRef.CurrentLaminate.LaminateId,
                "searchstring": vueRef.SearchString
            };
            axios.post(requestUrl, requestPostData)
                    .then(response => {
                        if (response.data.status === "success") {
                            vueRef.SearchResult = _.values(response.data.laminates);
                            if (vueRef.SearchResult.length > 0) {
                                for (let i = 0; i < vueRef.SearchResult.length; i++) {
                                    vueRef.SearchResult[i].Active = false;
                                    for (let j = 0; j < vueRef.Combination.length; j++) {
                                        if (vueRef.Combination[j].LaminateId === vueRef.SearchResult[i].LaminateId) {
                                            vueRef.SearchResult[i].Active = true;
                                        }
                                    }
                                }
                                vueRef.imagePopup();
                            }
                            vueRef.FormOverLay = true;
                            vueRef.$nextTick(() =>
                                $('[data-toggle="tooltip"]').bstooltip()
                            );
                        } else {
                            vueRef.FormOverLay = true;
                            vueRef.populateNotifications({
                                status: "error",
                                message: "Something wrong happened. Please try again!"
                            }, "ShortListLmainateNotificationArea");
                        }
                    })
                    .catch(error => {
                        vueRef.populateNotifications({
                            status: "error",
                            message: "Something wrong happened. Please try again!"
                        }, "ShortListLmainateNotificationArea");
                    })
                    .catch(() => {
                        vueRef.FormOverLay = true;
                    });
        },
        // Delete object from array of objects
        deleteObjectFromArray(array, deleteId) {
            let index = _.findIndex(array, ["LaminateId", deleteId]);
            if (index !== -1) {
                array.splice(index, 1);
            }
        },
        // Populates notifications of the form.
        populateNotifications(response) {
            this.NotificationMessage = response.message;
            this.NotificationIcon = response.status;
            this.FormOverLay = false;
            if (response.status == "success") {
                this.ShowShortListLaminateLoader = false;
                this.NotificationIcon = "check-circle";
                $(".notification-overlay").addClass("hidden");
                setTimeout(this.clearOverLayMessage, 3000);
                // Refresh page after 3.5 seconds 
                setTimeout(this.refreshPage, 3500);

            } else if (response.status == 'error') {
                this.NotificationIcon = "ban";
            } else {
                this.ShowShortListLaminateLoader = false;
                // If response contains error, delete current laminate from combinations array
                self.deleteObjectFromArray(this.Combination, this.CurrentLaminate.LaminateId);
                this.NotificationIcon = "ban";
            }
        },
        // Hide Success Message
        clearOverLayMessage() {
            this.FormOverLay = true;
            this.NotificationMessage = "";
            this.NotificationIcon = "";
        },
        // Redirect to Catalogue listing page
        refreshPage() {
            // Redirect to combinations listing page
            window.location = '/catalogue/laminates/' + this.ProjectId;
        },

        // On failed form submission  
        onFail(error) {
            this.ShowShortListLaminateLoader = false;
            this.deleteObjectFromArray(this.Combination, this.CurrentLaminate.LaminateId);
            if (error.status === 422) {
                let response = JSON.parse(error.responseText);
                this.populateFormErrors(response.data.errors, ValidatorObj);
            } else {
                this.populateNotifications({
                    status: "error",
                    message: AlertData["10077"]
                });
            }
        },

        // Populates backend validation errors  
        populateFormErrors(errors, formValidator) {
            for (var elementName in errors) {
                var errorObject = {},
                        previousValue = $("#" + elementName).data("previousValue") ? $("#" + elementName).data("previousValue") : {};
                previousValue.valid = false;
                previousValue.message = errors[elementName][0];
                $("#" + elementName).data("previousValue", previousValue);
                errorObject[elementName] = errors[elementName][0];
                formValidator.showErrors(errorObject);
            }
        },
        // Initialize popup when user clicks on Full Sheet thumbnail
        initializeFSheetThumbnailsPopup(imagesJSON, cdnUrl) {
            // Parse JSON and get image path and title
            let thumbnails = JSON.parse(imagesJSON);
            // Create image object which fits for plugin input
            thumbnails.forEach(function (obj) {
                obj.src = cdnUrl + obj.Path;
                obj.title = obj.UserFileName;
            }.bind(this));
            jquery(".image-link").magnificPopup({
                items: thumbnails,
                gallery: {
                    enabled: true
                },
                type: 'image',
                callbacks: {
                    open: function () {
                        var mfp = jquery.magnificPopup.instance;
                        var proto = jquery.magnificPopup.proto;
                        var Count = mfp.items.length;
                        if (!mfp.index && Count > 1) {
                            mfp.arrowLeft.css('display', 'none');
                        }
                        if (!(mfp.index - (Count - 1)) && Count > 1) {
                            mfp.arrowRight.css('display', 'none');
                        }
                        // Extend function that moves to next item
                        mfp.next = function () {
                            if (mfp.index < (Count - 1)) {
                                proto.next.call(mfp);
                            }
                            if (Count > 1) {
                                if (!(mfp.index - (Count - 1))) {
                                    mfp.arrowRight.css('display', 'none');
                                }
                                if (mfp.index > 0) {
                                    mfp.arrowLeft.css('display', 'block');
                                }
                            }
                        };
                        // Extend function that moves back to prev item
                        mfp.prev = function () {
                            if (mfp.index > 0) {
                                proto.prev.call(mfp);
                            }
                            if (Count > 1) {
                                if (!mfp.index) {
                                    mfp.arrowLeft.css('display', 'none');
                                }
                                if (Count > 1) {
                                    mfp.arrowRight.css('display', 'block');
                                }
                            }
                        };
                    },
                    close: function () {
                        $('.ui-tooltip').addClass("hidden");
                    }
                }
            });
        },
        // Open laminate full view pop up
        openLaminateViewPopup() {
            this.FormOverLay = false;
            this.NotificationMessage = "Fetching Laminate View";
            let requestUrl = $("#ViewLaminate").attr("data-api-end-point");
            axios.get(requestUrl + '/' + VueInstance.CurrentLaminate.LaminateId)
                    .then((response) => {
                        if (response.data.status === "success") {
                            $("#FullViewModal").find(".modal-body").empty();
                            $("#FullViewModal").find(".modal-body").html(response.data.view);
                            $("#FullViewModal").modal("show");
                            return;
                        }
                        this.FormOverLay = true;
                        VueInstance.populateNotifications(response.data);
                    })
                    .catch((error) => {
                        VueInstance.onFail(error);
                    })
                    .then(() => {
                        VueInstance.FormOverLay = true;
                    });
        }
    }
});


$(document).ready(function () {
    $("#ShortCode").val(VueInstance.CustomerDetails.shortCode);
    $("#SiteDetails").html(VueInstance.CustomerDetails.siteInfo);

    $("input[type=radio][name='SuggestionType']").on('change', function (event) {
        event.preventDefault();
        // Call function to get Hechpe Suggestions
        (VueInstance.HechpeSuggs !== null && VueInstance.HechpeSuggs.length > 0) ? VueInstance.IsSuggestionsExists = false : VueInstance.IsSuggestionsExists = true;
        VueInstance.imagePopup();
    });
    $('input#SearchLaminates').on('input', debounce(() => {
        if (VueInstance.SearchString.length >= 3) {
            let requestUrl = $("input#SearchLaminates").attr('data-api-end-point');
            VueInstance.getLaminates(requestUrl);
        }
    }, 500));
    // Initialize validator
    InitializeValidator();
    // Initialize tooltip
    $("body").bstooltip({selector: '[data-toggle=tooltip]'});

    // Initialize Rooms Select2
    jquery("#Rooms").select2({placeholder: 'Please Select a Room'});

    // Full view popup
    $(document).on('click', '.full-view-popup', function (event) {
        event.preventDefault();
        $("#FullViewModal").modal("show");
    });

    // Cancel button action
    $("#CancelBtn").on("click", (() => {
        // Redirect to combinations listing page
        if (VueInstance.ProjectId !== 'undefined' && VueInstance.ProjectId !== "") {
            window.location = '/catalogue/laminates/' + VueInstance.ProjectId;
        } else {
            window.location = '/catalogue/laminates/select';
        }
    }));
});

/**
 * Function initializes Validator.
 * 
 * @return  No
 */
var InitializeValidator = function () {
    ValidatorObj = $("#CreateCombination").validate({
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
            Project: {
                required: true
            },
            Suggestion: {
                CheckConsecutiveSpaces: true,
                maxlength: 255
            },
            Notes: {
                CheckConsecutiveSpaces: true,
                maxlength: 255
            }
        },
        messages: {
            Project: {
                required: "Please select a Project."
            },
            Suggestion: {
                maxlength: "Maximum 255 characters are allowed in Description."
            },
            Notes: {
                maxlength: "Maximum 255 characters are allowed in Description."
            }
        },
        submitHandler: function (form, event) {
            event.preventDefault();
            $("#addSuggestionBtn").trigger('blur');
            VueInstance.ShowShortListLaminateLoader = true;
            $(".alert").addClass('hidden');
            let formData = new FormData(form);
            // Push current laminate into selected laminates
            VueInstance.Combination.push(VueInstance.CurrentLaminate);
            // Append it to form data
            formData.append("Combinations", JSON.stringify(VueInstance.Combination));
            $.ajax({
                url: form.action,
                type: 'POST',
                dataType: 'json',
                data: formData,
                processData: false,
                contentType: false
            })
                    .done(function (response) {
                        VueInstance.populateNotifications(response);
                    })
                    .fail(function (error) {
                        VueInstance.onFail(error);
                    })
                    .always(function () {
                        VueInstance.ShowShortListLaminateLoader = false;
                        $("#addSuggestionBtn").trigger('blur');
                    });
        }
    });
};