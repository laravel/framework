/**
 *  
 * Compare and Select laminates/ selections script 
 * 
 **/

/** Include needed packages **/
require('../../../bootstrap');
require('magnific-popup');

//Resolve Jquery library conflict
let jquery = require('jquery');

// Register components
import OverlayNotification from '../../../components/overlayNotification';

/** Initialize Vue object **/
const VueInstance = new Vue({
    el: '#LaminatesComparisonPage',
    data: {
        isFirstShortlisted: "Shortlist",
        isSecondShortlisted: "Shortlist",
        isThirdShortlisted: "Shortlist",
        laminates: [],
        brands: Brands,
        ComparisonLaminate: {},
        ProjectId: ProjectId,
        CdnUrl: CDNURL,
        SearchEssentialsArray: [],
        SearchBoxOneResult: null,
        SearchBoxTwoResult: null,
        SearchBoxThreeResult: null,
        ShowFinalizeCombOverlay: false,
        MessageFormOverlay: true,
        NotificationIcon: "",
        NotificationMessage:""
    },
    created() {
        this.ComparisonLaminate = !_.isNull(CurrentLaminate) ? CurrentLaminate : {};
        this.laminates.push({ 
            "LaminateId": this.ComparisonLaminate.LaminateId,
            "Active": false
        });
    },
    mounted() {
        this.imagePopup();
        this.initialiseFirstAutoSuggestion();
        this.initialiseSecondAutoSuggestion();
        this.initialiseThirdAutoSuggestion();
    },
    components: {
        'overlay-notification': OverlayNotification
    },
    methods: {
        initialiseFirstAutoSuggestion()
        {
            $( "#SearchLamBox1" ).autocomplete({
                appendTo: '#FirstSearchBox', 
                minLength: 1,
                focus: function(event, ui) {
                   $(this).val(ui.item.label);
                   return false;
                },
                source: function( request, response ) {
                    $.ajax({
                        url: $("#SearchLamBox1").attr("data-api-end-point"),
                        type: 'post',
                        dataType: "json",
                        data: {
                          selectedLams: _.map(VueInstance.laminates, 'LaminateId'),
                          searchstring: request.term
                        },
                        success: function( data ) {
                          response( data);
                        }
                    });
                },
                select: function (event, ui) {
                    $(this).val(ui.item.label);
                    VueInstance.SearchBoxOneResult = ui.item.value;
                    VueInstance.laminates.push({
                        "LaminateId": VueInstance.SearchBoxOneResult.LaminateId,
                        "Active": false
                    });
                    VueInstance.imagePopup();
                    return false;
                }
            })
            .autocomplete('instance')._renderItem = function(ul, item) {
                return VueInstance.getLaminateImage(ul, item);
            };
        },
        
        initialiseSecondAutoSuggestion()
        {
            $( "#SearchBox2" ).autocomplete({
                appendTo: '#SecondSearchBox', 
                minLength: 1,
                focus: function(event, ui) {
                   $(this).val(ui.item.label);
                   return false;
                },
                source: function( request, response ) {
                    $.ajax({
                        url: $("#SearchBox2").attr("data-api-end-point"),
                        type: 'post',
                        dataType: "json",
                        data: {
                          selectedLams: _.map(VueInstance.laminates, 'LaminateId'),
                          searchstring: request.term
                        },
                        success: function( data ) {
                          response( data);
                        }
                    });
                },
                select: function (event, ui) {
                    $(this).val(ui.item.label);
                    VueInstance.SearchBoxTwoResult = ui.item.value;
                    VueInstance.laminates.push({
                        "LaminateId": VueInstance.SearchBoxTwoResult.LaminateId,
                        "Active": false
                    });
                    VueInstance.imagePopup();
                    return false;
                }
            })
            .autocomplete('instance')._renderItem = function(ul, item) {
                return VueInstance.getLaminateImage(ul, item);
            };
        },
        
        initialiseThirdAutoSuggestion()
        {
            $( "#SearchBox3" ).autocomplete({
                appendTo: '#ThirdSearchBox', 
                minLength: 1,
                focus: function(event, ui) {
                   $(this).val(ui.item.label);
                   return false;
                },
                source: function( request, response ) {
                    $.ajax({
                        url: $("#SearchBox3").attr("data-api-end-point"),
                        type: 'post',
                        dataType: "json",
                        data: {
                          selectedLams: _.map(VueInstance.laminates, 'LaminateId'),
                          searchstring: request.term
                        },
                        success: function( data ) {
                          response( data);
                        }
                    });
                },
                select: function (event, ui) {
                    $(this).val(ui.item.label);
                    VueInstance.SearchBoxThreeResult = ui.item.value;
                    VueInstance.laminates.push({
                        "LaminateId": VueInstance.SearchBoxThreeResult.LaminateId,
                        "Active": false
                    });
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
        
        // Add new property Active to toggle Add (laminates) button
        addProperty(laminates) {
            if (laminates.length > 0) {
                laminates.forEach(function (obj) {
                    obj.Active = false;
                    obj.IsSearchFound = false;
                });
            }
            return laminates;
        },
        // Delete object from array of objects
        deleteObjectFromArray(array, deleteId) {
            let index = _.findIndex(array, ["LaminateId", deleteId]);
            if (index !== -1) {
                array.splice(index, 1);
            }
        },
        // Selected laminates for comparison
        ComparisonLaminates() {
            let compareString = (this.ComparisonLaminate.DesignName + ((this.SearchBoxOneResult === null) ? "" : "<small> Vs</small> " + this.SearchBoxOneResult.DesignName)
            + ((this.SearchBoxTwoResult === null) ? "" : "<small> Vs</small> " + this.SearchBoxTwoResult.DesignName)
            + ((this.SearchBoxThreeResult === null) ? "" : "<small> Vs</small> " + this.SearchBoxThreeResult.DesignName));
            return compareString;
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
        // Get Brand of laminate
        getBrand($brandId) {
            let brand = _.find(this.brands, {'Id': $brandId});
            if (typeof brand !== "undefined") {
                return brand.Name;
            }
            return '<small>N/A</small>';
        },
        // Pluck Design No, Name, Search Tags from laminates
        pluckSearchEssentialInputs() {
            var laminates = [];
            if (this.laminates.length > 0) {
                for (let lam = 0; lam < this.laminates.length; lam++) {
                    if (!this.laminates[lam].IsSearchFound) {
                        laminates.push(this.laminates[lam].DesignNo);
                        laminates.push(this.laminates[lam].DesignName);
                        laminates.push(this.laminates[lam].SearchTags.replace(',', ' '));
                    }
                }
            }
            return laminates;
        },
        // Get laminate for given Design No/ Name/ Search Tags
        getLaminate(laminates, searchString) {
            if (laminates.length > 0) {
                let result = _.filter(laminates, function (laminate) {
                    return ((laminate.DesignName.toLowerCase().indexOf(searchString.replace(/\s\s+/g, ' ').toLowerCase()) !== -1) || (laminate.DesignNo.toLowerCase().indexOf(searchString.replace(/\s\s+/g, ' ').toLowerCase()) !== -1) || (laminate.SearchTags.replace(',', ' ').toLowerCase().indexOf(searchString.toLowerCase()) !== -1));
                }.bind(this));
                return result[0];
            }
            return null;
        },
        // Pluck only laminate Id's
        pluckLaminateIds() {
            this.ShortlistedSelections = [];
            if (this.laminates.length > 0) {
                for (let lam = 0; lam < this.laminates.length; lam++) {
                    if (this.laminates[lam].Active) {
                        this.ShortlistedSelections.push(this.laminates[lam].LaminateId);
                    }
                }
            }
        },
        // Submit the form
        submitSelections(event) {
            $("#CompareLaminatesFormSubmit").trigger('blur');
            $(".alert").addClass('hidden');
            let selectedLams = _.map(_.filter(this.laminates, (lam) => {
                return lam.Active;
            }), "LaminateId");
            // User has to select at least one laminate from filters
            if (selectedLams.length < 1) {
                this.populateNotifications({
                    status: "warning",
                    message: 'Please select at least one laminate.'
                });
                return;
            }
            this.ShowFinalizeCombOverlay = true;
            $.ajax({
                url: $('#CompareLaminatesForm').attr('action'),
                type: 'POST',
                dataType: 'json',
                data: {"Selections": selectedLams, "SiteProjectId": this.ProjectId}
            })
            .done(function (response) {
                this.populateNotifications(response);
            }.bind(this))
            .fail(function (error) {
                this.onFail(error);
            }.bind(this))
            .always(function () {
                this.ShowFinalizeCombOverlay = false;
                $("#CompareLaminatesFormSubmit").trigger('blur');
            }.bind(this));
        },
        // Populates notifications of the form.
        populateNotifications(response) {
            this.NotificationMessage = response.message;
            this.NotificationIcon = response.status;
            this.MessageFormOverlay = false;
            if (response.status === "success") {
                this.ShowFinalizeCombOverlay = false;
                this.NotificationIcon = "check-circle";
                $(".notificationOverlay-close").addClass("hidden");
                setTimeout(this.clearOverLayMessage, 3000);
                // Refresh page after 3.5 seconds 
                setTimeout(this.redirectToCataloguePage, 3500);
            } else if (response.status === 'error') {
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
            this.populateNotifications({
                status: "error",
                message: AlertData["10077"]
            });
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
// Document's ready event
$(document).ready(() => {
    // Selected laminate button click event
    $(document).on('click', '.shortlist-laminate', function (event) {
        let laminateId = $(this).attr("data-laminate-id");
        let laminate = _.find(VueInstance.laminates, {'LaminateId': laminateId});
        if (!_.isUndefined(laminate)) {
            laminate.Active = true;
        }
        VueInstance.$nextTick(()  => {
            if ($(this).attr("id") === "FirstShortListBtn") {
                VueInstance.isFirstShortlisted = "Shortlisted";
            } else if ($(this).attr("id") === "SecondShortListBtn") {
                VueInstance.isSecondShortlisted = "Shortlisted";
            } else if ($(this).attr("id") === "ThirdShortListBtn") {
                VueInstance.isThirdShortlisted = "Shortlisted";
            }
        });
    });
    // First selected laminate click event
    $(document).on('click', '#SelectedLaminate', function (event) {
        event.preventDefault();
        let laminateId = $(this).attr("data-laminate-id");
        if(VueInstance.ComparisonLaminate.LaminateId === laminateId) {
            VueInstance.ComparisonLaminate.Active = true;
        }
        let laminate = _.find(VueInstance.laminates, {'LaminateId': laminateId});
        if (!_.isUndefined(laminate)) {
            laminate.Active = true;
        }
    });
    // Remove first selected laminate click event
    $(document).on('click', '#RemoveLaminate', function (event) {
        event.preventDefault();
        let laminateId = $(this).attr("data-laminate-id");
        if(VueInstance.ComparisonLaminate.LaminateId === laminateId) {
            VueInstance.ComparisonLaminate.Active = false;
        }
        let laminate = _.find(VueInstance.laminates, {'LaminateId': laminateId});
        if (!_.isUndefined(laminate)) {
            laminate.Active = false;
        }
    });
    // Remove selected laminate button click event
    $(document).on('click', '.remove-laminate', function (event) {
        event.preventDefault();
        let laminateId = $(this).attr("data-laminate-id");
        //let laminate = _.find(VueInstance.laminates, {'LaminateId': laminateId});
        _.remove(VueInstance.laminates, {
            LaminateId: laminateId
        });
        if ($(this).attr("id") === "FirstRemoveBtn") {
            $("#SearchLamBox1").val("").focus();
            VueInstance.SearchBoxOneResult = null;
            VueInstance.isFirstShortlisted = "Shortlist";
        } else if ($(this).attr("id") === "SecondRemoveBtn") {
            $("#SearchBox2").val("").focus();
            VueInstance.SearchBoxTwoResult = null;
            VueInstance.isSecondShortlisted = "Shortlist";
        } else if ($(this).attr("id") === "ThirdRemoveBtn") {
            $("#SearchBox3").val("").focus();
            VueInstance.SearchBoxThreeResult = null; 
            VueInstance.isThirdShortlisted = "Shortlist";
        }
    });
    // On Cancel button click event
    $("#CancelBtn").on("click", (() => {
        // Redirect to combinations listing page
        if (VueInstance.ProjectId !== 'undefined' && VueInstance.ProjectId !== "") {
            window.location = '/catalogues/laminates/' + VueInstance.ProjectId;
        } else {
            window.location = '/catalogue/laminates/project/select';
        }
    }));
});
