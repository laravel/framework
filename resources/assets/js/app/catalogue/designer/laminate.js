/**
 *  
 * Laminates selection list script
 * 
 **/

let DatatableObj;
/** Include needed packages **/
require('../../../bootstrap');
require('magnific-popup');
//Resolve Jquery library conflict
var jquery = require('jquery');
// convert timestamp to local format date and time
let moment = require('moment');

const debounce = require('lodash/debounce');

// Register components
import CombinationNotes from '../../../components/catalogue/customer/ViewNotes';
import OverlayNotification from '../../../components/overlayNotification';

/** Initialize Vue instance **/
const VueInstance = new Vue({
    el: '#DesignerLaminatesCataloguePage',
    data: {
        projects: Projects,
        YetToFinalizedRooms: YetToFinalizedRooms, //Rooms for selected Project
        CreateCatalogueRoute: CreateCatalogueRoute, // Create Laminate Cat. route
        EditCatalogueRoute: EditCatalogueRoute, // Edit Laminate Cat. route
        FinalizeCatalogueRoute: FinalizeCatalogueRoute, // Finalize Selection route
        laminates: [], // Existing all laminates
        ShowSearchLamBody: true, // Flag to toggle search laminates block
        SearchString: "", // Vue model variable to bind entered search value
        CdnUrl: CDNURL, // S3 cloud storage URL
        CurrentLaminate: {}, // Stores selected laminate for full view
        brands: Brands, // Stores laminates brands
        SubBrands: SubBrands, // Stores laminates SubBrands
        catagories: Catagories, // Stores material categories
        finishs: Finishs, // Surface master finish
        Combinationstatus: Status, // Combination's status array
        ComparisonLaminate: {},
        StatusLabels: {1: 'primary', 2: 'info', 3: 'warning', 4: 'success', 5: 'danger'},
        ShowCompareModal: false, // Show Laminate comparison modal pop up
        CombinationsNotes: [],
        ShowNotesModal: false, // Show Combination notes modal pop up
        SelectedCombinationId: '', // Store combination id to delete combination
        ShowDeleteComLoader: false,
        ShowShortListLaminateLoader: false,
        SuccessMessage: "Saved",
        Notes: [],
        FormOverLay: true, // Toggle for notification loader
        NotificationIcon: "",
        NotificationMessage: "", //Message for notification loader
        ProjectId: ProjectId,
        UsersData: UsersData,
        TempCombinationsArray: [],
        ShortlistedLaminates: ShortlistedLams,
        CustomerRoleSlug: Slug,
        CustomerDetails: userDetails,
        ShortlistedSelections: [],
        CompareCatalogueRoute: CompareCatalogueRoute, //Compare Laminate Route
        SwatchImageZipDownloadRoute: SwatchImageZipDownloadRoute, // Download All Swatch Images as Zip
        SwatchImageDownloadRoute: SwatchImageDownloadRoute, // Download single swatch image
         RoomArea: "",
         Notes: [],
         LaminateFormOverlay:false

    },
    components: {
        'combinations-notes-popup': CombinationNotes,
        'overlay-notification': OverlayNotification
    },
    mounted() {
        // Initialize image Pop up
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
        // Get selected Room area id
        getRoomId(event) {
            this.RoomArea = event.target.value;
        },
        // Get laminate for given Design No/ Name/ Search Tags
        getLaminates(requestUrl) {
            let vueRef = this;
            vueRef.LaminateFormOverlay = true;
            $(".alert").addClass('hidden');
            let requestPostData = {
                searchstring: vueRef.SearchString
            };
            axios.post(requestUrl, requestPostData)
            .then(response => {
                if (response.data.status === "success") {
                    vueRef.laminates = response.data.laminates;
                    vueRef.LaminateFormOverlay = false;
                    vueRef.$nextTick(() =>
                        $('[data-toggle="tooltip"]').bstooltip()
                    );
                } else {
                    vueRef.LaminateFormOverlay = false;
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
                vueRef.LaminateFormOverlay = false;
            });
        },
        // Show Search Laminate Section
        showSelectLamSearch() {
            this.ShowSearchLamBody = false;
            // Focus the search box
            this.$nextTick(() => this.$refs.SearchLaminates.focus());
        },
        // Filter Shortlisted combinations for listing
        filterShortListedCombinations(ShortlistedLaminates, UpdateComIdFlag = true) {
            let counter = 1;
            let vm = this;
            let backgroundColorClass = 'white';
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
        // Search Laminates
        searchLaminates() {
           this.ShowSearchLamBody = false;
            $(".alert").addClass('hidden');
            // Focus the search box
            this.$nextTick(() => this.$refs.SearchLaminates.focus());
        },
        getStatus(status, slug) {
            if (status === 1) {
                if (slug === this.CustomerRoleSlug) {
                    return this.Combinationstatus[status];
                }
                return "HECHPE Suggested";
            }
            return this.Combinationstatus[status];
        },
        // Initialize image Pop up 
        imagePopup() {
            this.$nextTick(function () {
                jquery(".image-link").magnificPopup({
                    delegate: 'a',
                    type: 'image'
                });
            });
        },
        // Open laminate full view pop up
        openFullViewPopup(laminateId) {
            let vueRef = this;
            vueRef.LaminateFormOverlay = true;
            vueRef.OverlayMessage = "Fetching Laminate Data";
            let requestUrl = $(".ViewLaminate").attr("data-api-end-point");
            axios.get(requestUrl+'/'+laminateId)
            .then(response => {
                if (response.data.status === "success") {
                    $("#FullViewModal").find(".modal-body").empty();
                    $("#FullViewModal").find(".modal-body").html(response.data.view);
                    $("#FullViewModal").modal("show");
                    vueRef.LaminateFormOverlay = false;
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
                vueRef.LaminateFormOverlay = false;
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
                if (brand) {
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
                    if (catagory) {
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
                    if (finish) {
                        return finish.Name;
                    }
                }
            }
            return '<small>N/A</small>';
        },

        // Populates notifications of the form.
        populateNotifications(response) {
            this.NotificationMessage = response.message;
            this.NotificationIcon = response.status;
            this.FormOverLay = false;
            if (response.status === "success") {
                this.NotificationIcon = "check-circle";
                setTimeout(this.clearOverLayMessage, 3000);
                // Refresh page after 3.5 seconds 
                setTimeout(this.refreshPage, 3500);

            } else if (response.status === 'error') {
                this.NotificationIcon = "ban";
            } else if (response.status === 'warning') {
                this.NotificationIcon = "warning";
            }
        },
        // Shortlist single laminate selection
        ShortlistLaminates(roomArea, id) {
            this.ShortlistedSelections.push(id);
            this.ShowShortListLaminateLoader = true;
            let userDetails = this.CustomerDetails;
            $(".alert").addClass('hidden');
            if (this.ShortlistedSelections.length < 1) {
                this.populateNotifications({
                    status: "warning",
                    message: "Please shortlist at least one laminate."
                });
                return;
            }
            $.ajax({
                url: "/catalogue/laminate/combination/add",
                type: 'POST',
                data: {"Selections": this.ShortlistedSelections, 'laminateId': id, "shortCode": userDetails.shortCode, 'userId': userDetails.userId, 'projectId': this.ProjectId, 'RoomArea': roomArea},
                dataType: 'json'
            })
                    .done(function (response) {
                        if (response.status === "success") {
                            this.ShowShortListLaminateLoader = false;
                            this.populateNotifications(response);
                        } else {
                            this.ShowShortListLaminateLoader = false;
                            this.populateNotifications({
                                status: "error",
                                message: "Something wrong happened. Please try again!"
                            });
                        }
                    }.bind(this))
                    .fail(function () {
                        this.ShowShortListLaminateLoader = false;
                        this.populateNotifications({
                            status: "error",
                            message: "Something wrong happened. Please try again!"
                        });
                    }.bind(this))
                    .always(function () {
                        this.ShowShortListLaminateLoader = false;
                    });
        },
        // Hide Success Message
        clearOverLayMessage() {
            this.FormOverLay = true;
            this.NotificationMessage = "";
            this.NotificationIcon = "";
        },
        // Redirect to Catalogue listing page
        refreshPage() {
            location.reload();
        },
        // Deletes the combination from system
        deleteCombination(id) {
            this.ShowDeleteComLoader = true;
            $(".alert").addClass('hidden');
            $.ajax({
                url: "/catalogue/laminate/delete/" + id,
                type: 'GET',
                dataType: 'json'
            })
                    .done(function (response) {
                        if (response.status === "success") {
                            delete VueInstance.ShortlistedLaminates[id];
                            this.filterShortListedCombinations(VueInstance.ShortlistedLaminates, false);
                            this.ShowDeleteComLoader = false;
                            $("#DeleteCombModal").modal("hide");
                            this.populateNotifications(response);
                        } else {
                            this.ShowDeleteComLoader = false;
                            $("#DeleteCombModal").modal("hide");
                            this.populateNotifications({
                                status: "error",
                                message: "Something wrong happened. Please try again!"
                            });
                        }
                    }.bind(this))
                    .fail(function () {
                        this.ShowDeleteComLoader = false;
                        $("#DeleteCombModal").modal("hide");
                        this.populateNotifications({
                            status: "error",
                            message: "Something wrong happened. Please try again!"
                        });
                    }.bind(this))
                    .always(function () {
                        this.ShowDeleteComLoader = false;
                    });
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
            if (_.isNull(photoPath)) {
                return this.CdnUrl + defaultPhoto;
            }
            let splittedPath = photoPath.split(".");
            let thumbnail = splittedPath[0] + "-160x160." + splittedPath[1];
            return this.CdnUrl + thumbnail.replace('/source/', '/thumbnails/', thumbnail);
        },
        // Initialize popup when user clicks on Full Sheet thumbnail
        initializeGallery(imagesJSON) {
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
        }
    }
});
$(document).ready(function () {

    $("#ProjectSearch, #Project").select2({
        placeholder: 'Select Project'
    });

    // Initialize Rooms Select2
    $("#Rooms").select2({placeholder: 'Please Select a Room'});

    $("#ProjectSearch").on('change', function (event) {
        if ($(this).val() !== "") {
            $("#LaminateFormOverlay").removeClass('hidden');
            window.location = '/catalogue/laminates/' + $('#ProjectSearch option:selected').val().replace(/\"/g, "");
        }
    });
    $("#Project").on('change', function (event) {
        if ($(this).val() !== "") {
            $("#LaminateFormOverlay").removeClass('hidden');
            window.location = '/catalogue/laminates/' + $('#Project option:selected').val().replace(/\"/g, "");
        }
    });
    // Select laminate 
    $(document).on('click', '#SelectLaminate', function (event) {
        event.preventDefault();
        let laminateId = $(this).attr("data-laminateid");
        let roomArea =  $(this).parent("td").prev("td").find("select#Room").val();
        VueInstance.ShortlistLaminates(roomArea, laminateId);
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
    // Initialize magnetic image popup After data loads in dataTable
    VueInstance.imagePopup();
    // Initialize datatables
    initializeDataTable();
    //Initialize tooltip
    $("body").bstooltip({selector: '[data-toggle=tooltip]'});
    
    $('input#SearchLaminates').on('input', debounce(() => {
        if (VueInstance.SearchString.length >= 3) {
            let requestUrl = $("input#SearchLaminates").attr('data-api-end-point');
            VueInstance.getLaminates(requestUrl);
        }
    }, 500));
});

// DataTable initialization
function initializeDataTable() {
    // DataTable initialization
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
                "targets": 2,
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