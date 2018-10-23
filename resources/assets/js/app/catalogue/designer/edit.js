/**
 *  
 * Finalise selection script
 * 
 **/

/** Global variables **/
let notificationTimeout = 10000, notificationTimeoutID, ValidatorObj;


/** Include needed packages **/
require('../../../bootstrap');
var jquery = require('jquery');
const debounce = require('lodash/debounce');
require('magnific-popup');
require('select2');

// Register Components
//Shows User primary info...
import UserInformation from '../../../components/catalogue/designer/userInformation';
// Component to select laminates category(Hechpe, Generel, Shortlisted) options
import combinationOptions from '../../../components/catalogue/designer/CombinationOptions';

import OverlayNotification from '../../../components/overlayNotification';
// Selected Combination
import selectedCombinations from '../../../components/catalogue/customer/SelectedCombination';
// Maximum laminates selection limit alert
import maxLaminatesSelectionAlert from '../../../components/catalogue/customer/MaxCombCountReachedAlert';
// Shortlisted Suggestions
import shortlistedSuggestions from '../../../components/catalogue/customer/ShortListedLaminatesComponent';


/** Initialize Vue object **/
const VueInstance = new Vue({
    el: '#EditSMCatalogPage',
    data: {
        SelectedCombLaminates: [], // Stores selected laminates for combination
        ShortlistedCombinations: [], // Stores shortlisted laminates by customer
        Laminates: [], // Stores sytem laminates
        CdnUrl: CDNURL, // S3 Storage url
        projects: Projects, // Stores site projects
        ProjectId: "", // Stores selected project id from project dropdown
        ShowOverlay: false, // Ajax loader flag
        rooms: [], // Stores rooms for selected site project
        PickedOption: "", // Radion button selected option
        CombinationCount: CombinationCount, // Max laminates selection limit per combination
        SearchString: "", // Model to store search string
        SearchResult: [], // Generel laminate search result
        RoomId: "", // Stores Combination's Room Id
        Notes: null, // Stores added notes
        Suggestion: null, // Stores Suggestion
        ShowEditCombOverlay: false, // Form submit ajax loader flag
        FormOverLay: true, // Toggle for notification loader
        NotificationIcon: "",
        NotificationMessage: "", //Message for notification loader
        OverlayMessage: "",
        CustomerDetails: projectData.user, // Stores  Customer details
        HechpeSuggestions: [],
        showSearchFilter: false,
        brands: Brands,
        SurfaceCategories: Catagories,
        SurfaceFinishs: Finishs

    },
    components: {
        'user-information': UserInformation,
        'combination-options': combinationOptions,  
        'selected-combination-laminates': selectedCombinations,
        'max-laminates-selection-alert': maxLaminatesSelectionAlert,
        'shortlisted-suggestions-list': shortlistedSuggestions,
        'overlay-notification': OverlayNotification
    },
    // Called when Vue object creates
    created() {
//        this.Laminates = this.addProperty(Laminates); // Assign existing laminates to vue variable
        this.SelectedCombLaminates = this.addProperty(SelectedCombination, true);
        this.HechpeSuggestions = HechpeSuggestions;
        this.ShortlistedCombinations = this.addProperty(ShortListedLaminates);
        this.ProjectId = this.SelectedCombLaminates[0].SiteProjectId;
        this.Notes = this.SelectedCombLaminates[0].Notes !== null ? JSON.parse(this.SelectedCombLaminates[0].Notes) : null;
        this.Suggestion = this.SelectedCombLaminates[0].Suggestion !== null ? JSON.parse(this.SelectedCombLaminates[0].Suggestion) : null;
        this.RoomId = this.SelectedCombLaminates[0].RoomAreaId !== null ? this.SelectedCombLaminates[0].RoomAreaId : null;
    },
    // Called when Vue object mounts
    mounted() {
        this.fetchRooms();
        this.fetchShortlistCombination();
        this.InitializeValidator();
        this.imagePopup();
    },
    computed: {
        // Filters Shortlisted laminates
        fileteredShortlistedLaminates() {
            return _.uniqBy(this.ShortlistedCombinations, function (e) {
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
        // Filters Hechpe laminates Suggestions
        fileteredHechpeLaminates() {
            return _.uniqBy(this.HechpeSuggestions, function (e) {
                return e.LaminateId;
            });
        },
    },
    watch: {
        // Watcher to check radio PickedOption state asyncronously
        PickedOption: function (option) {
            if (option === "HechpeSuggs") {
                if (this.HechpeSuggestions.length > 0) {
                    // Check for add button visibility and toggle acc. to combination array(HechpeSuggComb)
                    for (let i = 0; i < this.HechpeSuggestions.length; i++) {
                        this.HechpeSuggestions[i].Active = false;
                        for (let j = 0; j < this.SelectedCombLaminates.length; j++) {
                            if (this.SelectedCombLaminates[j].LaminateId === this.HechpeSuggestions[i].LaminateId) {
                                this.HechpeSuggestions[i].Active = true;
                            }
                        }
                    }
                }
                // Clear Generel suggestions Search values 
                this.clearSearchValues();
            }
            if (option === "PickFromShortlist") {
                if (this.ShortlistedCombinations.length > 0) {
                    // Check for add button visibility and toggle acc. to combination array(ShortlistedSuggsCombination)
                    for (let i = 0; i < this.ShortlistedCombinations.length; i++) {
                        this.ShortlistedCombinations[i].Active = false;
                        for (let j = 0; j < this.SelectedCombLaminates.length; j++) {
                            if (this.SelectedCombLaminates[j].LaminateId === this.ShortlistedCombinations[i].LaminateId) {
                                this.ShortlistedCombinations[i].Active = true;
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
                if (!_.isEmpty(result[0])) {
                    this.SearchResult = result;
                    for (let i = 0; i < this.SearchResult.length; i++) {
                        this.SearchResult[i].Active = false;
                        for (let j = 0; j < this.SelectedCombLaminates.length; j++) {
                            if (this.SelectedCombLaminates[j].LaminateId === this.SearchResult[i].LaminateId) {
                                this.SearchResult[i].Active = true;
                            }
                        }
                    }
                }
            }
            return {};
        },
        // Resets Generel search variables 
        clearSearchValues() {
            this.SearchResult = [];
            this.SearchString = "";
            this.showSearchFilter = false;
        },
        // Add new property Active to toggle Add (laminates) button
        addProperty(laminates, makeTrue = false) {
            if (laminates.length > 0) {
                laminates.forEach(function (obj) {
                    if (makeTrue) {
                        obj.Active = true;
                    } else {
                        obj.Active = false;
                    }
                });
            }
            return laminates;
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
        // Returns Quick estimate rooms using project
        fetchRooms(onChange = false) {
            let self = this;
            if (onChange) {
                self.RoomId = null;
            }
            $(".alert").addClass("hidden");
            if (this.ProjectId !== 'undefined' && this.ProjectId !== '') {
                this.ShowOverlay = true;
                this.OverlayMessage = "Fetching Rooms";
                axios.get('/designer/get/qerooms/' + this.ProjectId)
                        .then(function (response) {
                            if (!_.isEmpty(response.data)) {
                                self.rooms = [];
                                self.rooms = response.data;
                            } else {
                                self.rooms = [];
                            }
                        })
                        .catch(function (error) {
                            self.onFail(error);
                        })
                        .then(() => {
                            this.ShowOverlay = false;
                        });
            } else {
                self.rooms = [];
        }
        },
        fetchShortlistCombination(onChange = false) {
            let self = this;
            if (onChange) {
                self.ShortlistedCombinations = null;
            }
            $(".alert").addClass("hidden");
            if (this.ProjectId !== 'undefined' && this.ProjectId !== '') {
                this.ShowOverlay = true;
                this.OverlayMessage = "Fetching Laminates";
                axios.get('/get/shortlistlams/' + this.ProjectId)
                        .then(function (response) {
                            let ShortlistedLams = response.data.data.SurfaceMaterialCatalog;
                            if (!_.isEmpty(ShortlistedLams)) {
                                self.ShortlistedCombinations = ShortlistedLams;
                                for (let i = 0; i < self.ShortlistedCombinations.length; i++) {
                                    self.ShortlistedCombinations[i].Active = false;
                                    for (let j = 0; j < self.SelectedCombLaminates.length; j++) {
                                        if (self.SelectedCombLaminates[j].LaminateId === self.ShortlistedCombinations[i].LaminateId) {
                                            self.ShortlistedCombinations[i].Active = true;
                                        }
                                    }
                                }
                            } else {
                                self.ShortlistedCombinations = [];
                            }
                            $("#siteInfo").removeClass("hidden");
                            self.CustomerDetails = response.data.data.user;
                            $("#ShortCode").val(response.data.data.shortCode);
                            $("#SiteDetails").html(response.data.data.siteInfo);
                        })
                        .catch(function (error) {
                            self.onFail(error);
                        })
                        .then(() => {
                            this.ShowOverlay = false;
                        });
            } else {
                self.ShortlistedCombinations = [];
        }
        },

        addToCombination(laminateId, laminate, trackingArray) {
            let laminateObj = {
                "Id": laminateId,
                "Laminate": laminate,
                "Combinations": trackingArray
            };
            this.addLamToCombination(laminateObj);
        },

        addLamToCombination(LaminateObj) {
            // Check Laminates combination max. count
            if (this.SelectedCombLaminates.length === (this.CombinationCount)) {
                // Show alert if exceeds max lamintes selection limit
                $("#MaxLamCombCountAlert").modal("show");
                return;
            }
            // Check for array length & object existence in array
            let IsObjExists = _.find(this.SelectedCombLaminates, function (obj) {
                return obj.LaminateId === LaminateObj.Id;
            });
            // If Object does not exists in array (duplicate object)...
            if (typeof IsObjExists === "undefined") {
                // 1. Hide the add button
                let laminate = _.find(LaminateObj.Combinations, function (obj) {
                    return obj.LaminateId === LaminateObj.Id;
                });
                if (typeof laminate !== "undefined") {
                    laminate.Active = true;
                }
                // 2. Push object into array
                this.SelectedCombLaminates.push(LaminateObj.Laminate);
            }
        },
        // Removes laminate from selected list (combination)
        deleteLamFromCombination(RemoveLamObj) {
            let IsObjExists;
            // Check for array length & object existence in array and delete it
            if (this.SelectedCombLaminates.length > 0) {
                IsObjExists = this.SelectedCombLaminates.splice(_.findIndex(this.SelectedCombLaminates, function (item) {
                    return item.LaminateId === RemoveLamObj.Id;
                }), 1);
            }
            // If Object is deleted...
            if (IsObjExists.length > 0) {
                let Sugglaminate = _.find(RemoveLamObj.HechpeSuggCombinations, function (obj) {
                    return obj.LaminateId === RemoveLamObj.Id;
                });
                // If object is present make active flag false (Update button text to Shortlist) 
                if (typeof Sugglaminate !== "undefined") {
                    let objIndex = RemoveLamObj.HechpeSuggCombinations.findIndex((obj => obj.LaminateId === RemoveLamObj.Id));
                    if (objIndex !== -1) {
                        RemoveLamObj.HechpeSuggCombinations[objIndex].Active = false;
                    }
                }
                let shortlistedlaminate = _.find(RemoveLamObj.ShortlistedCombinations, function (obj) {
                    return obj.LaminateId === RemoveLamObj.Id;
                });
                // If object is present make active flag false (show the add button) 
                if (typeof shortlistedlaminate !== "undefined") {
                    let objIndex = RemoveLamObj.ShortlistedCombinations.findIndex((obj => obj.LaminateId === RemoveLamObj.Id));
                    if (objIndex !== -1) {
                        RemoveLamObj.ShortlistedCombinations[objIndex].Active = false;
                    }
                }
                let laminate1 = _.find(RemoveLamObj.GenerelSearchcombinations, function (obj) {
                    return obj.LaminateId === RemoveLamObj.Id;
                });
                // If object is present make active flag false (show the add button) 
                if (typeof laminate1 !== "undefined") {
                    let objIndex = RemoveLamObj.GenerelSearchcombinations.findIndex((obj => obj.LaminateId === RemoveLamObj.Id));
                    if (objIndex !== -1) {
                        RemoveLamObj.GenerelSearchcombinations[objIndex].Active = false;
                    }
                }
            }
            $(".ui-tooltip").addClass("hidden");
        },
        initializeAutoCompleteSearch() {
            this.$nextTick(function () {
                this.$refs.SearchLaminates.focus();
                // Initialize autocomplete search
                $("#SearchLaminates").autocomplete({
                    appendTo: '#SearchLaminatesBox',
                    source: this.SearchEssentials
                });
                // Initialize autocomplete select event
                $("#SearchLaminates").on('autocompleteselect', function (e, ui) {
                    this.SearchString = ui.item.value;
                    this.SearchResult = [];
                    this.getLaminate(this.Laminates, ui.item.value);
                    this.imagePopup();
                }.bind(this));
            });
        },
        // Get laminate for given Design No, Name, Search Tags
        getLaminates(requestUrl) {
            let vueRef = this;
            vueRef.ShowOverlay = true;
            vueRef.OverlayMessage = "Fetching Laminates";
            $(".alert").addClass('hidden');
            let requestPostData = {
                "searchstring": vueRef.SearchString
            };
            axios.post(requestUrl, requestPostData)
                    .then(response => {
                        if (response.data.status === "success") {
                            vueRef.SearchResult = _.values(response.data.laminates);
                            if (vueRef.SearchResult.length > 0) {
                                for (let i = 0; i < vueRef.SearchResult.length; i++) {
                                    vueRef.SearchResult[i].Active = false;
                                    for (let j = 0; j < vueRef.SelectedCombLaminates.length; j++) {
                                        if (vueRef.SelectedCombLaminates[j].LaminateId === vueRef.SearchResult[i].LaminateId) {
                                            vueRef.SearchResult[i].Active = true;
                                        }
                                    }
                                }
                                vueRef.imagePopup();
                            }
                            vueRef.ShowOverlay = false;
                        } else {
                            vueRef.ShowOverlay = false;
                            vueRef.populateNotifications({
                                status: "error",
                                message: "Something wrong happened. Please try again!"
                            });
                        }
                    })
                    .catch(error => {
                        vueRef.populateNotifications({
                            status: "error",
                            message: "Something wrong happened. Please try again!"
                        });
                    })
                    .catch(() => {
                        vueRef.ShowOverlay = false;
                    });
        },
        // Search Laminates
        searchLaminates() {
            if (this.SearchString.length >= 3) {
                $(".alert").addClass('hidden');
                let requestUrl = $("#SearchLamsBtn").attr('data-api-end-point');
                this.getLaminates(requestUrl);
            }
        },
        getBrand(brandId) {
            let brand = _.find(this.brands, {'Id': brandId});
            if (typeof brand !== "undefined") {
                return brand.Name;
            }
            return 'N/A';
        },
        // Populates notifications of the form.
        populateNotifications(response) {
            this.NotificationMessage = response.message;
            this.NotificationIcon = response.status;
            this.FormOverLay = false;
            if (response.status == "success") {
                this.ShowEditCombOverlay = false;
                this.NotificationIcon = "check-circle";
                $(".notificationOverlay-close").addClass("hidden");
                setTimeout(this.clearOverLayMessage, 3000);
                // Refresh page after 3.5 seconds 
                setTimeout(this.refreshPage, 3500);

            } else if (response.status == 'error') {
                this.NotificationIcon = "ban";
            } else if (response.status == 'warning') {
                this.NotificationIcon = "warning";
            } else {
                this.ShowEditCombOverlay = false;
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
            this.ShowEditCombOverlay = false;
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
        // Clears form element values
        resetForm() {
            jquery("#Project,#Rooms").val(null).trigger('change');
            $("#NotificationArea").addClass("hidden");
            this.clearSearchValues();
            ValidatorObj.resetForm();
        },
        // Populates backend validation errors  
        populateFormErrors(errors, formValidator) {
            for (let elementName in errors) {
                let errorObject = {},
                        previousValue = $("#" + elementName).data("previousValue") ? $("#" + elementName).data("previousValue") : {};
                previousValue.valid = false;
                previousValue.message = errors[elementName][0];
                $("#" + elementName).data("previousValue", previousValue);
                errorObject[elementName] = errors[elementName][0];
                formValidator.showErrors(errorObject);
            }
        },
        // Initialize form validator 
        InitializeValidator() {
            ValidatorObj = $("#EditCombination").validate({
                ignore: [],
                rules: {
                    Project: {
                        required: true
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
                    Notes: {
                        maxlength: "Maximum 255 characters are allowed in Description."
                    }
                },
                submitHandler: function (form, event) {
                    event.preventDefault();
                    $("#EditCombSubmitBtn").trigger('blur');
                    $(".alert").addClass('hidden');
                    // User has to select at least one laminate from filters
                    if (VueInstance.SelectedCombLaminates.length < 1) {
                        VueInstance.populateNotifications({
                            status: "warning",
                            message: 'Please select at least one laminate'
                        });
                        return;
                    }

                    VueInstance.ShowEditCombOverlay = true;
                    let formData = new FormData(form);
                    var currentUrl = window.location.href.split("/");
                    //Post URL
                    let postUrl = form.action + "/" + currentUrl.reverse()[0].replace(/\"/g, "");
                    // Append it to form data
                    formData.append("Combinations", JSON.stringify(VueInstance.SelectedCombLaminates));
                    $.ajax({
                        url: postUrl,
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
                                VueInstance.ShowEditCombOverlay = false;
                                $("#EditCombSubmitBtn").trigger('blur');
                            });
                }
            });
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
        // Get Surface type for provided id  
        getCategory(categoryId) {
            if (categoryId) {
                if (this.SurfaceCategories.length > 0) {
                    let catagory = _.find(this.SurfaceCategories, ["Id", categoryId]);
                    if (catagory !== "undefined") {
                        return catagory.Name;
                    }
                    return categoryId;
                }
            }
            return '<small>N/A</small>';
        },
        // Get Surface finish for provided id  
        getFinish(finishId) {
            if (finishId) {
                let isNumber = parseInt(finishId);
                if (!isNaN(isNumber)) {
                    if (this.SurfaceFinishs.length > 0) {
                        let finish = _.find(this.SurfaceFinishs, ["Id", finishId]);
                        if (finish !== "undefined") {
                            return finish.Name;
                        }
                    }
                }
                return finishId;
            }
            return '<small>N/A</small>';
        }
    },
});
$(document).ready(function () {
    // Initialize tooltip
    $("body").bstooltip({selector: '[data-toggle=tooltip]'});
    $("input[type=radio][name='SuggestionType']").on('change', function (event) {
        event.preventDefault();
        VueInstance.imagePopup();
    });
    $('input#SearchLaminates').on('input', debounce(() => {
        if (VueInstance.SearchString.length >= 3) {
            let requestUrl = $('input#SearchLaminates').attr('data-api-end-point');
            VueInstance.getLaminates(requestUrl);
        }
    }, 500));
    $("#CancelBtn").on("click", (() => {
        // Redirect to combinations listing page
        if (VueInstance.ProjectId !== 'undefined' && VueInstance.ProjectId !== "") {
            window.location = '/catalogue/laminates/' + VueInstance.ProjectId;
        } else {
            window.location = '/catalogue/laminates/select';
        }
    }));
});

