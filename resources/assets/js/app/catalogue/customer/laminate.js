/**
 *  
 * Laminates selection list script
 * 
 **/

let DatatableObj;
require('../../../bootstrap');
require('magnific-popup');
require('select2');
let jquery = require('jquery');
let moment = require('moment');
import CombinationNotes from '../../../components/catalogue/customer/ViewNotes';

// Vue variables object
var modelVariables = {
    ShortlistLaminatesRoute: ShortlistLaminatesRoute, // Shortlist Laminates Route
    ShortlistedLaminates: ShortlistedLams, // Shortlisted laminates by customer
    laminates: [], // Existing all laminates
    ShowSearchLamBody: true, // Flag to toggle search laminates block
    SearchString: "", // Vue model variable to bind entered search value
    CdnUrl: CDNURL, // S3 cloud storage URL
    CurrentLaminate: {}, // Stores selected laminate for full view
    brands: Brands, // Stores laminates brands
    SubBrands: SubBrands, // Stores laminates SubBrands
    catagories: Catagories, // Stores material categories
    finishs: Finishs, // Surface master finish
    status: Status, // Combination's status array
    ComparisonLaminate: {},
    ShowCompareModal: false, // Show Laminate comparison modal pop up
    CombinationsNotes: [],
    ShowNotesModal: false, // Show Combination notes modal pop up
    SelectedCombinationId: '', // Store combination id to delete combination
    ShowDeleteComLoader: false,
    Notes: [],
    UsersData: UsersData,
    FinalizeCombinationRoute: finalizeComRoute,
    TempCombinationsArray: [],
    StatusLabels: {1: 'primary', 2: 'info', 3: 'warning', 4: 'success', 5: 'danger'},
    CustomerRoleSlug: RoleSlug,
    ProjectId: SelectedProject,
    projects: Projects, // Customer's projects
    ShowShortListLaminateLoader: false,
    OverlayMessage: "",
    CompareLaminatesRoute: CompareLaminatesRoute,
    FormOverLay: true,
    SuccessMessage: "Saved",
    SwatchImageZipDownloadRoute: SwatchImageZipDownloadRoute,
    SwatchImageDownloadRoute: SwatchImageDownloadRoute,
    YetToFinalizedRooms: YetToFinalizedRooms,
    RoomArea: ""
};
    
/** Initialize Vue instance **/
const VueInstance = new Vue({
    el: '#CreateLaminatesCataloguePage',
    data: modelVariables,
    components: {
        'combinations-notes-popup': CombinationNotes
    },
    mounted() {
        this.initialiseLaminatesAutoSuggestionFilter();
        this.imagePopup();
        this.filterShortListedCombinations(this.ShortlistedLaminates, true);
    },
    computed: {
        // Remove duplicate objects
        fileteredLaminates() {
            $(".button-search").trigger("blur");
            return this.laminates;
        },
        // Get selected Room area
        selectedRoomarea() {
            return (this.RoomArea) ? ("/" + this.RoomArea) : ""; 
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
                          searchstring: request.term
                        },
                        success: function( data ) {
                          response( data);
                        }
                    });
                },
                select: function (event, ui) {
                    VueInstance.SearchString = ui.item.label;
                    VueInstance.laminates = [];
                    VueInstance.laminates.push(ui.item.value);
                    VueInstance.imagePopup();
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
        // Get selected Room area id
        getRoomId(event) {
            this.RoomArea = event.target.value;
        },
        // Get selection status
        getStatus(status, slug) {
            if (status === 1) {
                if (slug === this.CustomerRoleSlug) {
                    return this.status[status];
                }
                return "HECHPE Suggested";
            }
            return this.status[status];
        },
        // Filter Shortlisted combinations for listing
        filterShortListedCombinations(ShortlistedLaminates, UpdateComIdFlag = true) {
            let counter = 1;
            let backgroundColorClass = 'white';
            let vm = this;
            vm.TempCombinationsArray = [];
            _.forEach(ShortlistedLaminates, function (combination) {
                _.forEach(combination, function (laminate) {
                    if (UpdateComIdFlag) {
                        laminate.CatalgId = laminate.CatalogueId;
                    }
                    laminate.CatalogueId = counter;
                    laminate.BackgroundColor = backgroundColorClass;
                    vm.TempCombinationsArray.push(laminate);
                    vm.Notes.push(laminate);
                });
                counter += 1;
                // Add background color classes to each rowgroup
                backgroundColorClass = backgroundColorClass === "white" ? "lightblue" : "white";
            });
            _.sortBy(vm.TempCombinationsArray, [function (o) {
                return o.CatalogueId;
            }]);
        },
        // Show Search Laminate Section
        showSelectLamSearch() {
            this.ShowSearchLamBody = false;
            $(".alert").addClass('hidden');
            // Focus the search box
            this.$nextTick(() => this.$refs.SearchLaminates.focus());
        },
        // Get laminate for given Design No, Name, Search Tags
        getLaminates(requestUrl) {
            let vueRef = this;
            vueRef.ShowShortListLaminateLoader = true;
            vueRef.OverlayMessage = "";
            $(".alert").addClass('hidden');
            let requestPostData = {
                searchstring: vueRef.SearchString
            };
            axios.post(requestUrl, requestPostData)
            .then(response => {
                if (response.data.status === "success") {
                    vueRef.laminates = response.data.laminates;
                    vueRef.ShowShortListLaminateLoader = false;
                    vueRef.$nextTick(() =>
                        $('[data-toggle="tooltip"]').bstooltip()
                    );
                } else {
                    vueRef.ShowShortListLaminateLoader = false;
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
                vueRef.ShowShortListLaminateLoader = false;
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
        // Initialize image Pop up 
        imagePopup() {
            this.$nextTick(() => 
                jquery(".image-link").magnificPopup({
                    delegate: 'a',
                    type: 'image'
                })
            );
        },
        // Open laminate full view pop up
        openFullViewPopup(laminateId) {
            let vueRef = this;
            vueRef.ShowShortListLaminateLoader = true;
            vueRef.OverlayMessage = "Fetching Laminate Data";
            let requestUrl = $(".full-view-popup").attr("data-api-end-point");
            axios.get(requestUrl+'/'+laminateId)
            .then(response => {
                if (response.data.status === "success") {
                    $("#FullViewModal").find(".modal-body").empty();
                    $("#FullViewModal").find(".modal-body").html(response.data.view);
                    $("#FullViewModal").modal("show");
                    vueRef.ShowShortListLaminateLoader = false;
                    return;
                }
                vueRef.populateNotifications({
                    status: "error",
                    message: "Something wrong happened. Please try again!"
                }, "ShortListLmainateNotificationArea");
            })
            .catch(error => {
                vueRef.populateNotifications({
                    status: "error",
                    message: "Something wrong happened. Please try again!"
                }, "ShortListLmainateNotificationArea");
            })
            .catch(() => {
                vueRef.ShowShortListLaminateLoader = false;
            });
        },
        // Open Selection's notes window
        openNotesPopup(combinationId) {
            let note = _.find(this.Notes, {'CatalgId': combinationId});
            if (note !== "undefined") {
                // Decode the notes if available
                if (note.Notes !== null) {
                    var notes = [];
                    var decodedNotes = JSON.parse(note.Notes);
                    for (let i = 0; i < decodedNotes.length; i++) {
                        let temp = {};
                        temp.Note = decodedNotes[i].Notes;
                        temp.User = this.getUserProfile(decodedNotes[i].CreatedBy);
                        temp.CreatedTime = decodedNotes[i].CreatedAt;
                        notes.push(temp);
                    }
                    this.CombinationsNotes = notes;
                } else {
                    this.CombinationsNotes = [];
                }
                this.ShowNotesModal = true;
                this.$nextTick(function () {
                    $("#ViewNotesModal").modal({show: this.ShowNotesModal});
                });
            }
        },
        // Get brand for provided id 
        getBrand(brandId) {
            if (this.brands.length > 0) {
                let brand = _.find(this.brands, ["Id", brandId]);
                if (!_.isUndefined(brand)) {
                    return brand.Name;
                }
            }
            return '<small>N/A</small>';
        },
        // Get Sub brand for provided id 
        getSubBrand(brandId) {
            if (!_.isEmpty(this.SubBrands)) {
                let brand = this.SubBrands[brandId];
                if (!_.isUndefined(brand)) {
                    return brand;
                }
            }
            return '<small>N/A</small>';
        },
        // Get Surface type for provided id  
        getCategory(categoryId) {
            if (categoryId) {
                if (this.catagories.length > 0) {
                    let catagory = _.find(this.catagories, ["Id", categoryId]);
                    if (catagory !== "undefined") {
                        return catagory.Name;
                    }
                }
            }
            return '<small>N/A</small>';
        },
        // Get Surface finish for provided id  
        getFinish(finishId) {
            if (finishId) {
                if (this.finishs.length > 0) {
                    let finish = _.find(this.finishs, ["Id", finishId]);
                    if (finish !== "undefined") {
                        return finish.Name;
                    }
                }
            }
            return '<small>N/A</small>';
        },
        // Populates notifications of the form.
        populateNotifications(response, notificationAreaId = "NotificationArea") {
            let notificationArea = $("#" + notificationAreaId);
            if (notificationArea.children('.alert').length === 0) {
                notificationArea.html('<div class="alert alert-dismissible hidden"></div>');
            }
            let alertDiv = notificationArea.children('.alert');
            notificationArea.removeClass('hidden');
            if (response.status === "success") {
                alertDiv.removeAttr('class').removeAttr('style').addClass('alert alert-dismissible alert-success').html('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + response.message);
            } else if (response.status === 'warning') {
                alertDiv.removeAttr('class').removeAttr('style').addClass('alert alert-dismissible alert-warning').html('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + response.message);
            } else if (response.status === 'error') {
                alertDiv.removeAttr('class').removeAttr('style').addClass('alert alert-dismissible alert-danger').html('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><i class="fa fa-exclamation-circle" aria-hidden="true"></i>&nbsp;&nbsp;' + response.message);
            }
        },
        // Deletes the combination from system
        deleteCombination(id) {
            this.ShowDeleteComLoader = true;
            $(".alert").addClass('hidden');
            $.ajax({
                url: "/catalogues/laminates/selection/delete/" + id,
                type: 'GET',
                dataType: 'json'
            })
            .done(function (response) {
                if (response.status === "success") {
                    this.callBackFunction(id);
                } else {
                    this.ShowDeleteComLoader = false;
                    this.populateNotifications({
                        status: "error",
                        message: "Something wrong happened. Please try again!"
                    }, "DeleteCombNotificationArea");
                }
            }.bind(this))
            .fail(function () {
                this.ShowDeleteComLoader = false;
                this.populateNotifications({
                    status: "error",
                    message: "Something wrong happened. Please try again!"
                }, "DeleteCombNotificationArea");
            }.bind(this))
            .always(function () {
                this.ShowDeleteComLoader = false;
            }.bind(this));
        },
        callBackFunction(id) {
            delete VueInstance.ShortlistedLaminates[id];
            this.filterShortListedCombinations(VueInstance.ShortlistedLaminates, false);
            this.ShowDeleteComLoader = false;
            $("#DeleteCombModal").modal("hide");
            // Show success message for 3 seconds
            this.SuccessMessage = "Deleted";
            this.FormOverLay = false;
            setTimeout(this.clearOverLayMessage, 3000);
            // Refresh page after 3.5 seconds 
            setTimeout(this.refreshPage, 3500);
        },
        // Shortlist laminate and add it to selection list
        shortlistLaminate(roomArea, id) {
            this.ShowShortListLaminateLoader = true;
            this.OverlayMessage = "Saving Selection";
            $(".alert").addClass('hidden');
            $.ajax({
                url: "/catalogues/laminates/shortlist/" + id,
                type: 'POST',
                data: {"LaminateId": id, "RoomArea": roomArea, "SiteProjectId": this.ProjectId},
                dataType: 'json'
            })
            .done(function (response) {
                if (response.status === "success") {
                    this.addSelectionToList(id);
                } else {
                    this.populateNotifications({
                        status: "error",
                        message: "Something wrong happened. Please try again!"
                    }, "ShortListLmainateNotificationArea");
                }
            }.bind(this))
            .fail(function () {
                this.populateNotifications({
                    status: "error",
                    message: "Something wrong happened. Please try again!"
                }, "ShortListLmainateNotificationArea");
            }.bind(this))
            .always(function () {
                this.ShowShortListLaminateLoader = false;
            }.bind(this));
        },
        // Add New selection to Selection list
        addSelectionToList(id) {
            // Show success message for 3 seconds
            this.SuccessMessage = "Saved"; 
            this.FormOverLay = false;
            setTimeout(this.clearOverLayMessage, 3000);
            // Refresh page after 3.5 seconds 
            setTimeout(this.refreshPage, 3500);
        },
        // Hide Success Message
        clearOverLayMessage() {
            this.FormOverLay = true;
        },
        // Redirect to Catalogue listing page
        refreshPage() {
            location.reload();
        },
        // Get User's Name and Photo path
        getUserProfile(userId) {
            let user = _.find(this.UsersData, {'Id': userId});
            if (user !== "undefined") {
                let info = {};
                info.Name = user.person.FirstName + " " + user.person.LastName;
                info.Photo = this.getUserPhoto(user.person.Photo);
                return info;
            }
            return null;
        },
        // Get User's uploaded photo
        getUserPhoto(photoPath) {
            let defaultPhoto = "public/images/user-160x160.png";
            if(_.isNull(photoPath)) {
                return this.CdnUrl + defaultPhoto;
            }
            let splittedPath = photoPath.split(".");
            let thumbnail = splittedPath[0] + "-160x160." + splittedPath[1];
            return this.CdnUrl + thumbnail.replace('/source/', '/thumbnails/', thumbnail);
        },
        // Initialize popup when user clicks on Full Sheet thumbnail
        initializeFSheetThumbnailsPopup(imagesJSON) {
            let self = this;
            // Parse JSON and get image path and title
            let thumbnails = JSON.parse(imagesJSON);
            // Create image object which fits for plugin input
            thumbnails.forEach(function (obj) {
                obj.src = self.CdnUrl + obj.Path;
                obj.title = obj.UserFileName;
            });
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
        }
    }
});
// Document ready event
$(document).ready(function () {
    // Projects Select2 initialization
    jquery("#ProjectSearch,#Project").select2({
        placeholder: 'Select a Project',
        language: {
            noResults: function () {
                return "No Projects found.";
            }
        }
    });
    jquery("#ProjectSearch").on('change', function () {
        if ($(this).val() !== "") {
            $("#LaminateFormOverlay").removeClass('hidden');
            window.location = '/catalogues/laminates/' + $('#ProjectSearch option:selected').val().replace(/\"/g, "");
        }
    });
    jquery("#Project").on('change', function () {
        if ($(this).val() !== "") {
            $("#LaminateFormOverlay").removeClass('hidden');
            window.location = '/catalogues/laminates/' + $('#Project option:selected').val().replace(/\"/g, "");
        }
    });
    // Delete Combination popup
    $(document).on('click', '#DeleteCombination', function (event) {
        event.preventDefault();
        $(".alert").addClass('hidden');
        let combinationId = $(this).attr("data-combination-id");
        let combination = _.find(VueInstance.filterShortListedCombinations, {'CatalogueId': combinationId});
        if (combination !== "undefined") {
            VueInstance.SelectedCombinationId = combinationId;
            $("#DeleteCombModal").modal("show");
        }
    });
    // Shortlist laminate link
    $(document).on('click', '#ShrortListLaminateLink', function (event) {
        event.preventDefault();
        let laminateId = $(this).attr("data-laminateid");
        let roomArea =  $(this).parent("td").prev("td").find("select#Room").val();
        VueInstance.shortlistLaminate(roomArea, laminateId);
    });
    // Add to Shortlist link
    $(document).on('click', '#AddToShortlist', function (event) {
        event.preventDefault();
        let laminateId = $(this).attr("data-laminateid");
        let roomArea =  $(this).parent("td").prev("td").find("select#Room").val();
        window.location = VueInstance.ShortlistLaminatesRoute + '/' + VueInstance.ProjectId + '/' + laminateId + '/' + roomArea;
    });
     
    // Initialize magnetic image popup After data loads in dataTable
    VueInstance.imagePopup();
    initializeDataTable();
    $('[data-toggle="tooltip"]').bstooltip();
    // Initialize tooltip on each page of table
    $('#ShortlistedCombinations').on('page.dt', function () {
        $('[data-toggle="tooltip"]').bstooltip();
    });
});
// DataTable initialization
function initializeDataTable() {
    DatatableObj = $('#ShortlistedCombinations').DataTable({
        paging: true,
        retrieve: true,
        autoWidth: false,
        pageLength: 10,
        pagingType: "simple_incremental_bootstrap",
        "columnDefs": [
            {
                "targets": 0,
                "orderable": false
            },
            {
                "targets": 1,
                "orderable": false
            },
            {
                "targets": 3,
                "orderable": false
            },
            {
                "targets": 9,
                "orderable": false
            },
            {
                "targets": 10,
                "orderable": false
            },
            {
                "targets": 11,
                "orderable": false
            },
            {
                "targets": 12,
                "orderable": false
            },
            {
                "targets": 13,
                "orderable": false
            }
        ],
        // Attribute to span rows dynamically
        rowsGroup: [
            0, 1, 2, 13
        ]
    });
    $("#ShortlistedCombinations_filter input").attr('placeholder', 'Search...').focus();
}
