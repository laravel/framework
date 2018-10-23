/**
 *  
 * Shortlist laminate and add to selection script
 * 
 **/

/** Global variables **/
let ValidatorObj;

/** Include needed packages **/
require('../../../bootstrap');
var jquery = require('jquery');
require('magnific-popup');
require('select2');

// Register components
// Component to select laminates category(Hechpe, Generel, Shortlisted) options
import combinationOptions from '../../../components/catalogue/customer/CombinationOptions';
// Hechpe Suggestions
import laminatesSuggestions from '../../../components/catalogue/customer/LaminatesSuggestions';
// Hechpe laminates selected for combination
import selectedHechpeLaminates from '../../../components/catalogue/customer/SelectedHechpeLams';
// Generel Suggestions
import generelSuggestions from '../../../components/catalogue/customer/GenerelSuggestions';
// Generel search laminates selected for combination
import selectedGenerSearchLaminates from '../../../components/catalogue/customer/SelectedGenLams';
// Shortlisted Suggestions
import shortlistedSuggestions from '../../../components/catalogue/customer/ShortlistedLaminates';
// Shortlisted laminates selected for combination
import selectedShortlistedSuggestions from '../../../components/catalogue/customer/SelectedShortlistedSuggestions';
// Maximum laminates selection limit alert
import maxLaminatesSelectionAlert from '../../../components/catalogue/customer/MaxCombCountReachedAlert';
// Overlay shows Success, Warning, Error messages
import OverlayNotification from '../../../components/overlayNotification';

/** Initialize Vue object **/
const VueInstance = new Vue({
    el: '#ShortlistLaminatesPage',
    data: {
        projects: Projects, // Customers projects
        rooms: [],
        laminates: [], // Existing laminates
        brands: Brands,
        CurrentLaminate: CurrentLaminate, // Contains current to be shortlisted laminate
        CdnUrl: CDNURL, // S3 cloud storage URL
        SurfGlossinessVals: [], // Stores surface glossiness values
        ProjectId: ProjectId, // Site project id
        ShowOverlay: false, // Toggle ajax request loader
        OverlayMessage: "",
        HechpeSuggs: [], // Contains laminates suggested by Hechpe
        IsSuggestionsExists: false, // Flag to show no results found message
        PickedOption: "", // Radion button selected option
        SearchResult: [], // Generel laminate search result
        SearchString: "", // Model to store search string
        SelectedLaminates: [], // Stores selected laminates of all options (Hechpe, Shortlised, general)
        ShortlistedSuggestions: [], // Shortlisted laminates
        Combination: [], // Stores shortlisted laminates as combination
        CombinationCount: CombinationCount, // Combination max laminates count
        SubmitShortlistOverlay: false, // Toggle submit combination ajax request loader
        SearchEssentials: [], // Laminates array for autocomplete search
        showSearchFilter: false,
        RoomArea: [RoomArea],
        SwatchImageZipDownloadRoute: SwatchImageZipDownloadRoute,
        SwatchImageDownloadRoute: SwatchImageDownloadRoute,
        MessageFormOverlay: true,
        NotificationIcon: "",
        NotificationMessage:"", //Message for notification loader
        SurfaceCategories: Catagories,
        SurfaceFinishs: Finishs
    },
    components: {
        'combination-options': combinationOptions,
        'laminates-suggestions-list': laminatesSuggestions,
        'selected-laminates': selectedHechpeLaminates,
        'generel-suggestions-list': generelSuggestions,
        'selected-generel-laminates': selectedGenerSearchLaminates,
        'shortlisted-suggestions-list': shortlistedSuggestions,
        'selected-shortlisted-laminates': selectedShortlistedSuggestions,
        'max-laminates-selection-alert': maxLaminatesSelectionAlert,
        'overlay-notification': OverlayNotification
    },
    // Called when Vue object creates
    created() {
       // this.laminates = this.addProperty(Laminates); // Assign existing laminates to vues variable
        this.SurfGlossinessVals = GlossinessValues; // Surface Glossiness system values
        this.ShortlistedSuggestions = this.addProperty(ShortlistedLams);
    },
    // Called when Vue object mounts
    mounted() {
        // Delete current laminate from ShortlistedSuggestions array
        this.HechpeSuggs = HechpeSuggestions;
        this.deleteObjectsFromArray(this.ShortlistedSuggestions, [{LaminateId: this.CurrentLaminate.LaminateId}]);
        if(this.ProjectId != "") {
            this.fetchRooms();
        }
        this.initialiseLaminatesAutoSuggestionFilter();
        this.imagePopup();
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
            return _.uniqBy(this.ShortlistedSuggestions, function (e) {
                return e.LaminateId;
            });
        }
    },
    watch: {
        // Watcher to check radio PickedOption state asyncronously
        PickedOption: function (option) {
            if (option === "HechpeSuggs") {
                if (this.HechpeSuggs.length > 0) {
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
                // Clear Generel suggestions Search values 
                this.clearSearchValues();
            }
            if (option === "PickFromShortlist") {
                if (this.ShortlistedSuggestions.length > 0) {
                    // Check for add button visibility and toggle acc. to combination array(ShortlistedSuggsCombination)
                    for (let i = 0; i < this.ShortlistedSuggestions.length; i++) {
                        this.ShortlistedSuggestions[i].Active = false;
                        for (let j = 0; j < this.Combination.length; j++) {
                            if (this.Combination[j].LaminateId === this.ShortlistedSuggestions[i].LaminateId) {
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
                this.$nextTick(function () {
                    $("#SearchLaminates").focus();
                });
                this.showSearchFilter = true;
            }
        }
    },
    methods: {
        initialiseLaminatesAutoSuggestionFilter()
        {
            $( "#SearchLaminates" ).autocomplete({
                appendTo: '#SearchLaminatesBox', 
                minLength: 1,
                delay: 500,
                focus: function(event, ui) {
                   $(this).val(ui.item.label);
                   return false;
                },
                source: function( request, response ) {
                    $.ajax({
                        url: $("input#SearchLaminates").attr('data-api-end-point'),
                        type: 'post',
                        dataType: "json",
                        data: {
                            "selectedLams": [VueInstance.CurrentLaminate.LaminateId],
                            "searchstring": request.term
                        },
                        success: function( data ) {
                            response( data);
                        }
                    });
                },
                select: function (event, ui) {
                    VueInstance.SearchString = ui.item.label;
                    VueInstance.SearchResult = [];
                    VueInstance.SearchResult.push(ui.item.value);
                    if (VueInstance.SearchResult.length > 0) {
                            VueInstance.SearchResult[0].Active = false;
                            for (let j = 0; j < VueInstance.Combination.length; j++) {
                                if (VueInstance.Combination[j].LaminateId === VueInstance.SearchResult[0].LaminateId) {
                                    VueInstance.SearchResult[0].Active = true;
                                }
                            }
                        VueInstance.imagePopup();
                    }
                    return false;
                }
            })
            .autocomplete('instance')._renderItem = function(ul, item) {
                return VueInstance.getLaminateImage(ul, item);
            };
        },
        getLaminateImage(ul, item)
        {
          return $('<li>')
                    .append('<img class="autocomplete-image" src='+ VueInstance.CdnUrl+JSON.parse(item.value.FullSheetImage)[0].Path + ' alt='+ JSON.parse(item.value.FullSheetImage)[0].UserFileName + '/>')
                    .append('<a>' + item.label + '</a>')
                    .appendTo(ul);  
        },
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
            self.rooms = [];
            if (self.ProjectId !== 'undefined' && self.ProjectId !== '') {
                self.ShowOverlay = true;
                self.OverlayMessage = "Fetching Rooms";
                axios.get('/get/qerooms/' + self.ProjectId)
                .then(function (response) {
                    if (!_.isEmpty(response.data)) {
                        self.rooms = response.data;
                    } else {
                        self.rooms = [];
                    }
                })
                .catch(function (error) {
                    self.onFail(error);
                })
                .then(() => {
                    self.ShowOverlay = false;
                });
            } else {
                self.rooms = [];
            }
        },
        getSelectedRooms() {
            let self = this;
            jquery("#Rooms").on('change', function () {
                $('.ui-tooltip').addClass("hidden");
                let selectedRooms = jquery("#Rooms").val();
                self.RoomArea = selectedRooms;
            });
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
                return obj.LaminateId === LaminateObj.Id;
            });
            // If Object does not exists in array (duplicate object)...
            if (typeof IsObjExists === "undefined") {
                // 1. Update button text to Shortlisted
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
        deleteLamFromCombination(RemoveLamObj) {
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
                // Update button text to Shortlist
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
        // Get laminate for given Design No, Name, Search Tags
        getLaminates(requestUrl) {
            let vueRef = this;
            vueRef.ShowOverlay = true;
            vueRef.OverlayMessage = "";
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
                    vueRef.ShowOverlay = false;
                    vueRef.$nextTick(() =>
                        $('[data-toggle="tooltip"]').bstooltip()
                    );
                } else {
                    vueRef.ShowOverlay = false;
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
                vueRef.ShowOverlay = false;
            });
        },
        // Search Laminates
        searchLaminates() {
            if(this.SearchString.length >= 1) {
                $(".alert").addClass('hidden');
                let requestUrl = $("#SearchLamsBtn").attr('data-api-end-point');
                this.getLaminates(requestUrl);
            }
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
            this.MessageFormOverlay = false;
            if (response.status === "success") {
                this.NotificationIcon = "check-circle";
                $(".notificationOverlay-close").addClass("hidden");
                setTimeout(this.clearOverLayMessage, 3000);
                // Refresh page after 3.5 seconds 
                setTimeout(this.redirectToCataloguePage, 3500);
            } else if (response.status === 'error') {
                this.deleteObjectFromArray(this.Combination, this.CurrentLaminate.LaminateId);
                this.NotificationIcon = "ban";
            } else if (response.status === 'warning') {
                this.NotificationIcon = "warning";
            }
        },
        // Hide Success Message
        clearOverLayMessage() {
            this.MessageFormOverlay = true;
            this.NotificationMessage = "";
            this.NotificationIcon = "";
        },
        // Redirect to Catalogue listing page
        redirectToCataloguePage() {
            window.location = '/catalogues/laminates/' + this.ProjectId;
        },
        // On failed form submission  
        onFail(error) {
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
        initializeFSheetThumbnailsPopup(imagesJSON,cdnUrl) {
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
                    open: function() {                        
                        var mfp = jquery.magnificPopup.instance;
                        var proto = jquery.magnificPopup.proto;
                        var Count = mfp.items.length;
                        if(!mfp.index && Count > 1){
                            mfp.arrowLeft.css('display', 'none');
                        }
                        if(!(mfp.index - (Count-1)) && Count > 1){
                            mfp.arrowRight.css('display', 'none');
                        }
                        // Extend function that moves to next item
                        mfp.next = function() {
                            if(mfp.index < (Count-1)) {
                                proto.next.call(mfp);
                            }
                            if(Count > 1){
                                if(!(mfp.index - (Count-1))){
                                    mfp.arrowRight.css('display', 'none');
                                }
                                if(mfp.index > 0){
                                    mfp.arrowLeft.css('display', 'block');
                                }
                            }
                        };
                        // Extend function that moves back to prev item
                        mfp.prev = function() {
                            if(mfp.index > 0) {
                                proto.prev.call(mfp);
                            }
                            if(Count > 1){
                                if(!mfp.index){
                                    mfp.arrowLeft.css('display', 'none');
                                }
                                if(Count > 1){
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
            this.ShowOverlay = true;
            this.OverlayMessage = "Fetching Data";
            let requestUrl = $("#ViewLaminate").attr("data-api-end-point");
            axios.get(requestUrl+'/'+VueInstance.CurrentLaminate.LaminateId)
            .then((response) => {
                if (response.data.status === "success") {
                    $("#FullViewModal").find(".modal-body").empty();
                    $("#FullViewModal").find(".modal-body").html(response.data.view);
                    $("#FullViewModal").modal("show");
                    return;
                }
                VueInstance.populateNotifications(response.data);
            })
            .catch( (error) => {
                VueInstance.onFail(error);
            })
            .then(() => {
                VueInstance.ShowOverlay = false;
            });
        }
    }
});
// Document ready event
$(document).ready(function () {
    $("input[type=radio][name='SuggestionType']").on('change', function (event) {
        event.preventDefault();
        // Call function to get Hechpe Suggestions
        (VueInstance.HechpeSuggs.length > 0) ? VueInstance.IsSuggestionsExists = false : VueInstance.IsSuggestionsExists = true;
        VueInstance.imagePopup();
    });
    // Initialize validator
    InitializeValidator();
    // Initialize Rooms Select2
    jquery("#Rooms").select2({
        placeholder: 'Select Room',
        language: {
            noResults: function () {
                return "No Rooms found.";
            }
        }
    });
    VueInstance.getSelectedRooms();
    // Cancel button action
    $("#CancelBtn").on("click", (() => {
        // Redirect to combinations listing page
        if(VueInstance.ProjectId !== 'undefined' && VueInstance.ProjectId  !== "") {
            window.location = '/catalogues/laminates/' + VueInstance.ProjectId;
        } else {
            window.location = '/catalogue/laminates/project/select';
        }
    }));
    $('[data-toggle="tooltip"]').bstooltip();
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
            Notes: {
                CheckConsecutiveSpaces: true,
                maxlength: 255
            }
        },
        messages: {
            Project: {
                required: "Project can't be blank."
            },
            Notes: {
                maxlength: "Maximum 255 characters are allowed in Description."
            }
        },
        submitHandler: function (form, event) {
            event.preventDefault();
            $("#SubmitShortlistBtn").trigger('blur');
            VueInstance.SubmitShortlistOverlay = true;
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
                VueInstance.SubmitShortlistOverlay = false;
                $("#SubmitShortlistBtn").trigger('blur');
            });
        }
    });
};