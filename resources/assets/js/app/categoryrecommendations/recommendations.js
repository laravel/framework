require('../../bootstrap');
require('select2');
const jQuery = require('jquery');
const debounce = require('lodash/debounce');

import OverlayNotification from '../../components/overlayNotification';
import SelectedItems from '../../components/categoryrecommendations/SelectedItems';
import CategoryItems from '../../components/categoryrecommendations/CategoryItems';
import NoteModal from '../../components/categoryrecommendations/NotesModal';

var SelectedItemsDaTable, NoteValidator;

const vueVariables = {
    viewItemRoute: ViewPageRoute,
    selectItemRoute: SelectItemRoute,
    itemsSearchRoute: ItemsSearchRoute,
    finalizeItemRoute: FinalizeItemRoute,
    updateItemStatusRoute: UpdateItemStatusRoute,
    deleteItemRoute: DeleteItemRoute,
    saveNoteRoute: SaveNoteRoute,
    getMaterialRoute: GetMaterialRoute,
    isCustomer: IsCustomer,
    rooms: null,
    categories: null,
    isCategorySelected: false,
    selectedCategory: '',
    dataTableHeaders: [],
    searchString: null,
    items: [],
    selectedItems: [],
    categorySlug: null,
    projectMaterialId: "",
    note: null,
    projectFormOverlay: true,
    overLayMessage: "",
    formOverLay: true,
    notificationIcon: "",
    notificationMessage:"",
    noteLoader: false,
    noteOverLay: true,
    noteNotificationIcon: "",
    noteNotificationMessage:""
};

const vueInstance = new Vue({
    el: '#RecommendationsPage',
    
    data: vueVariables,
    
    components: {
       'overlay-notification': OverlayNotification,
       'selected-items': SelectedItems,
       'category-items': CategoryItems,
       'note-modal': NoteModal
    },
    
    computed: {
      updatedNote() {
        return this.note;
      }  
    },
   
    mounted() {
        this.bootstrapSelect2();
        this.getProjectRooms();
        this.fetchCategory();
        this.fetchSelectedItems();
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
                event.preventDefault();
                vueInstance.projectFormOverlay = false;
                vueInstance.overLayMessage = "Fetching Project Rooms";
                vueInstance.items = vueInstance.selectedItems = [];
                vueInstance.rooms = vueInstance.categories = null;
                vueInstance.isCategorySelected = false;
                vueInstance.searchString = null;
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
            });
        },
       
        /**
        * Save note
        * 
        * @return  No
        */
        saveNote()
        {
            NoteValidator = $("#AddNoteForm").validate({
                ignore: [],
                onkeyup: function (element, event) {
                    if (this.invalid.hasOwnProperty(element.name)) {
                        $(element).valid();
                    }
                },
                errorClass: "help-block text-danger",
                errorElement: "span",
                highlight: function (element, errorClass) {
                    $(element).parent().addClass("has-error");
                },
                unhighlight: function (element, errorClass) {
                    $(element).parent().removeClass("has-error");
                },
                errorPlacement: function (error, element) {
                        error.appendTo($(element).parent());
                },
                rules: {
                    Note: {
                        required: true,
                        CheckConsecutiveSpaces: true,
                        maxlength: 255
                    }
                },
                messages: {
                    Note: {
                        required: "Note can't be blank.",
                        maxlength: "Maximum 255 characters are allowed in Note."
                    }
                },
                submitHandler: function () {
                    vueInstance.noteLoader = true;
                    let formData = new FormData($('form#RecommendationForm')[0]);
                    formData.append('ProjectMaterialId', vueInstance.projectMaterialId);
                    formData.append("Note", $("#Note").val());
                    let requestUrl = vueInstance.saveNoteRoute;
                    const config = {
                        headers: {
                            'Content-Type': 'false', 
                            'Process-Data': 'false'
                        }
                    };
                    axios.post(requestUrl, formData, config)
                    .then(response => {
                        if(response.data.status === "success") {
                            vueInstance.projectMaterialId = response.data.projectmaterialid;
                            vueInstance.note = $("#Note").val();
                        }
                        vueInstance.populateAddNoteNotifications(response.data);
                    })
                    .catch(error => {
                        vueInstance.onAddNoteFail(error);
                    })
                    .then(() => {
                        vueInstance.noteLoader = false;
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
            NoteValidator.resetForm();
        },
       
        /**
        * Returns material category.
        * 
        * @return  No
        */
        fetchCategory() 
        {
            jQuery("#Room").on('change', function (event) {
                event.preventDefault();
                if ($(this).val()) {
                    if ((_.isNull(vueInstance.categories) || !vueInstance.isCategorySelected)) {
                        vueInstance.getCategory(event.target.id);
                    } else {
                        vueInstance.fetchSelectedItemsCall('Category');
                    }
                }
            });
        },
        
        /**
        * Initiate Server request to get all category.
        * 
        * @params  string  elementId 
        * 
        * @return  No
        */
        getCategory(elementId)
        {
            vueInstance.categories = null;
            vueInstance.projectFormOverlay = false;
            vueInstance.overLayMessage = "Fetching Material Category";
            let requestUrl = $('#'+elementId).data('api-end-point');
            axios.get(requestUrl)
            .then(response => {
                vueInstance.categories = response.data;
            })
            .catch(error => {
                vueInstance.onFail(error);
            })
            .then(() => {
                vueInstance.projectFormOverlay = true;
            });
        },
        
        /**
        * Get Selected, Shortlisted items
        * 
        * @return  No
        */
        fetchSelectedItems()
        {
            jQuery("#Category").on('change', function (event) {
                event.preventDefault();
                if(jQuery(this).val()) {
                    vueInstance.isCategorySelected = true;
                    vueInstance.selectedCategory = jQuery(this).find(':selected').text();
                    vueInstance.fetchSelectedItemsCall('Category');
                }
            });
        },
        
        /**
        * API call to get Selected, Shortlisted items
        * 
        * @return  No
        */
        fetchSelectedItemsCall(element)
        {
            vueInstance.projectFormOverlay = false;
            vueInstance.overLayMessage = "Fetching Previous Items";
            vueInstance.items = [];
            vueInstance.searchString = null;
            let requestUrl = $("#"+element).data('api-end-point');
            vueInstance.categorySlug = $("#"+element).find(':selected').data('category-slug');
            var requestPostData = new FormData($('form#RecommendationForm')[0]);
            requestPostData.append('Slug', vueInstance.categorySlug);
            const config = {
                headers: {
                    'Content-Type': 'false', 
                    'Process-Data': 'false'
                }
            };
            axios.post(requestUrl, requestPostData, config)
            .then(function (response) {
                if(response.data.status === "success") {
                    vueInstance.dataTableHeaders = response.data.headers; 
                    vueInstance.selectedItems = response.data.selecteditems;
                    vueInstance.projectMaterialId = response.data.projectMaterialId;
                    vueInstance.note = response.data.note;
                    $(".search-input").focus();
                } else {
                    vueInstance.populateNotifications(response.data);
                }
            })
            .catch(function (error) {
                vueInstance.onFail(error);
            })
            .then(() => {
                vueInstance.projectFormOverlay = true;
            });
        },
        
        /**
        * Get search input items
        * 
        * @return  No
        */
        fetchItems() 
        {
            vueInstance.projectFormOverlay = false;
            vueInstance.overLayMessage = "";
            let requestUrl = this.itemsSearchRoute;
            let formTemplate = $("#Category").find(':selected').data('formtemplate-id');
            var selectedItemsIds = [];
            if (vueInstance.selectedItems.length > 0) {
                selectedItemsIds = _.map(vueInstance.selectedItems, 'MaterialId');
            }
            let requestPostData = {
                searchstring: vueInstance.searchString, 
                formtemplate: formTemplate,
                selectedItems: selectedItemsIds,
                slug: vueInstance.categorySlug
            };
            axios.post(requestUrl, requestPostData)
            .then(response => {
                vueInstance.dataTableHeaders = response.data.headers;
                vueInstance.items = _.values(response.data.searchresult);
                vueInstance.projectFormOverlay = true;
            })
            .catch(error => {
                vueInstance.onFail(error);
            })
            .catch(() => {
                vueInstance.projectFormOverlay = true;
            });
        },
         
        /**
        * Get Material Quick view
        * 
        * @param {string} slug
        * @param {string} item 
        * 
        * @return  No
        */
        quickView(slug, item)
        {
            vueInstance.projectFormOverlay = false;
            vueInstance.overLayMessage = "Fetching Data";
            axios.get(vueInstance.getMaterialRoute+'/'+slug+'/'+item)
            .then((response) => {
                if (response.data.status === "success") {
                    $("#QuickViewModal").find(".modal-body").empty();
                    $("#QuickViewModal").find(".modal-body").html(response.data.view);
                    $("#QuickViewModal").modal("show");
                    return;
                }
                vueInstance.populateNotifications(response.data);
            })
            .catch( (error) => {
                vueInstance.onFail(error);
            })
            .then(() => {
                vueInstance.projectFormOverlay = true;
            });
        },
        
        /**
        * Recommend/ Select item 
        * 
        * @param  string  item
        * @param  string  requestUrl
        * 
        * @return  No
        */
        recommendItem(item, requestUrl) 
        {
            vueInstance.projectFormOverlay = false;
            vueInstance.overLayMessage = this.isCustomer ?  "Selecting": "Recommending";
            var requestPostData = new FormData($('form#RecommendationForm')[0]);
            requestPostData.append('item', item);
            requestPostData.append('ProjectMaterialId', vueInstance.projectMaterialId);
            requestPostData.append('Note', vueInstance.note);
            const config = {
                headers: {
                    'Content-Type': 'false', 
                    'Process-Data': 'false'
                }
            };
            axios.post(requestUrl, requestPostData, config)
            .then( (response) => {
                if (response.data.status === "success") {
                    let selectedItem = _.find(vueInstance.items, function (obj) {
                        return obj.MaterialId === item;
                    });
                    if (!_.isUndefined(selectedItem)) {
                        let itemIndex = _.findIndex(vueInstance.items, function (currentObject) {
                            return currentObject.MaterialId === selectedItem.MaterialId;
                        });
                        if (itemIndex !== -1) {
                            vueInstance.items.splice(itemIndex, 1);
                            vueInstance.projectMaterialId = response.data.projectmaterialid;
                            selectedItem['CollectionId'] = response.data.collectionid;
                            selectedItem['SelectedBy'] = vueInstance.isCustomer ? "Customer": "Designer";
                            selectedItem['ShortlistedBy'] = null;
                            selectedItem['Status'] = '0';
                            vueInstance.selectedItems.push(selectedItem);
                        }
                    }
                }
                vueInstance.populateNotifications(response.data);
            })
            .catch( (error) => {
                vueInstance.onFail(error);
            })
            .then(() => {
                vueInstance.projectFormOverlay = true;
            });
        },
        
        /**
        * Shortlist/ FinaliZe item 
        
        * @param  string  item
        * @param  string  requestUrl 
        
        * @return  No
        */
        finalizeItem(item, requestUrl) 
        {
            vueInstance.projectFormOverlay = false;
            vueInstance.overLayMessage = this.isCustomer ? "Shortlisting": "Finalizing";
            var requestPostData = new FormData($('form#RecommendationForm')[0]);
            requestPostData.append('item', item);
            requestPostData.append('ProjectMaterialId', vueInstance.projectMaterialId);
            requestPostData.append('Note', vueInstance.note);
            const config = {
                headers: {
                    'Content-Type': 'false', 
                    'Process-Data': 'false'
                }
            };
            axios.post(requestUrl, requestPostData, config)
            .then( (response) => {
                if (response.data.status === "success") {
                    let finalizedItem = _.find(vueInstance.items, function (obj) {
                        return obj.MaterialId === item;
                    });
                    if (!_.isUndefined(finalizedItem)) {
                        let itemIndex = _.findIndex(vueInstance.items, function (currentObject) {
                            return currentObject.MaterialId === finalizedItem.MaterialId;
                        });
                        if (itemIndex !== -1) {
                            vueInstance.items.splice(itemIndex, 1);
                            vueInstance.projectMaterialId = response.data.projectmaterialid;
                            finalizedItem['CollectionId'] = response.data.collectionid;
                            finalizedItem['SelectedBy'] = vueInstance.isCustomer ? "Customer": "Designer";
                            finalizedItem['ShortlistedBy'] = vueInstance.isCustomer ? "Customer": "Designer";
                            finalizedItem['Status'] = '1';
                            vueInstance.selectedItems.push(finalizedItem);
                        }
                    }
                }
                vueInstance.populateNotifications(response.data);
            })
            .catch( (error) => {
                vueInstance.onFail(error);
            })
            .then(() => {
                vueInstance.projectFormOverlay = true;
            });
        },
        
        /**
        * Shortlist/ FinaliZe item 
        
        * @param  string  projectMaterial
        * @param  string  collectionId
        * @param  string  requestUrl 
        * 
        * @return  No
        */
        makeStatusFinalize(projectMaterial, collectionId, requestUrl) 
        {
            vueInstance.projectFormOverlay = false;
            vueInstance.overLayMessage = this.isCustomer ? "Shortlisting": "Finalizing";
            var requestPostData = new FormData($('form#RecommendationForm')[0]);
            requestPostData.append('ProjectMaterialId', projectMaterial);
            requestPostData.append('CollectionId', collectionId);
            const config = {
                headers: {
                    'Content-Type': 'false', 
                    'Process-Data': 'false'
                }
            };
            axios.post(requestUrl, requestPostData, config)
            .then( (response) => {
                if (response.data.status === "success") {
                    let finalizedItem = _.find(vueInstance.selectedItems, function (obj) {
                        return obj.CollectionId === collectionId;
                    });
                    if (!_.isUndefined(finalizedItem)) {
                        vueInstance.$nextTick(() => {
                            finalizedItem['SelectedBy'] = null;
                            finalizedItem['ShortlistedBy'] = vueInstance.isCustomer ? "Customer" : "Designer";
                            finalizedItem['Status'] = '1';
                        });
                    }
                }
                vueInstance.populateNotifications(response.data);
            })
            .catch( (error) => {
                vueInstance.onFail(error);
            })
            .then(() => {
                vueInstance.projectFormOverlay = true;
            });
        },
        
        /**
        * Delete item 
        * 
        * @param  string  requestUrl 
        * @param  string  collectionId 
        * 
        * @return  No
        */
        deleteItem(requestUrl, collectionId) 
        {
            vueInstance.projectFormOverlay = false;
            vueInstance.overLayMessage = "Deleting";
            let requestPostData = { 'CollectionId': collectionId };
            axios.post(requestUrl, requestPostData)
            .then( (response) => {
                if (response.data.status === "success") {
                    let deletedItem = _.find(vueInstance.selectedItems, function (obj) {
                        return obj.CollectionId === collectionId;
                    });
                    if(!_.isUndefined(deletedItem)) {
                        var index = _.findIndex(vueInstance.selectedItems, deletedItem);
                        vueInstance.selectedItems.splice(index, 1);
                        deletedItem['Status'] = '0';
                        vueInstance.items.push(deletedItem);
                    }
                }
                vueInstance.populateNotifications(response.data);
            })
            .catch( (error) => {
                vueInstance.onFail(error);
            })
            .then(() => {
                vueInstance.projectFormOverlay = true;
            });
        },
        
        /**
        * Initialise Data table 
        * 
        * @param  object  tableObj
        * @param  array  orderableDisCols
        * @param  string  tableId
        * 
        * @return  No
        */
        initialiseItemsDataTable(tableObj, orderableDisCols, tableId, disableSearch = true) 
        {
            if ($.fn.DataTable.isDataTable(tableId)) {
                $(tableId).DataTable().destroy();
            }
            
            tableObj = $(tableId).DataTable({
                paging: true,
                searching: disableSearch,
                autoWidth: false,
                lengthChange: false,
                "columnDefs": [
                    {"orderable": false, "targets": orderableDisCols}
                ]
            }); 
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

            } else if (response.status === 'error') {
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
        },
        
        /**
         * Populates notifications of the Add note form.
         *
         * @param  object  response
         * 
         * @return  No
         */
        populateAddNoteNotifications(response)
        {
            this.noteNotificationMessage = response.message;
            this.noteNotificationIcon = response.status;
            this.noteOverLay = false;
            if (response.status === "success") {     
                this.noteNotificationIcon = "check-circle";

            } else if (response.status === 'error') {
                this.noteNotificationIcon = "ban";
            }
        },

        /**
        * Populates failed request notification message.
        * 
        * @return  No
        */
        onAddNoteFail(error) 
        {
            this.populateAddNoteNotifications({
                status: "error",
                message: error.data.message
            });
        },
        
        /**
        * Clears overlay message.
        * 
        * @return  No
        */
        clearAddNoteOverLayMessage()
        {
           this.noteOverLay = true;
           this.noteNotificationMessage = "";
           this.noteNotificationIcon = "";
        }
    }
});

$(document).ready(() => { 
    vueInstance.saveNote();
    $(document).on('click', '.open-note-modal', function (event) {
        event.preventDefault();
        $("#Note").val(vueInstance.note);
        $(".note-modal").modal();
    });
    $('.note-modal').on('hidden.bs.modal', function (event) {
        NoteValidator.resetForm();
        $("#Note").val(vueInstance.note);
    });
    $('input.search-input').on('input', debounce(() => {
        if (vueInstance.searchString.length >= 3) {
            vueInstance.fetchItems();
        }
    }, 500));
    $(document).on('click', '.recommend-material', function (event) {
        event.preventDefault();
        let materialId = $(this).attr("data-material-id");
        let requestUrl = $(this).attr('data-api-end-point');
        vueInstance.recommendItem(materialId, requestUrl);
    });
    $(document).on('click', '.finalize-material', function (event) {
        event.preventDefault();
        let materialId = $(this).attr("data-material-id");
        let requestUrl = $(this).attr('data-api-end-point');
        vueInstance.finalizeItem(materialId, requestUrl);
    });
    $(document).on('click', '.update-material', function (event) {
        event.preventDefault();
        let projectMaterial = vueInstance.projectMaterialId;
        let requestUrl = $(this).attr('data-api-end-point');
        let collectionId = $(this).attr('data-collection-id');
        vueInstance.makeStatusFinalize(projectMaterial, collectionId, requestUrl);
    });
    $(document).on('click', '.delete-material', function (event) {
        event.preventDefault();
        let requestUrl = $(this).attr('data-api-end-point');
        let collectionId = $(this).attr('data-collection-id');
        vueInstance.deleteItem(requestUrl, collectionId);
    });
    $(document).on('click', '.quick-view', function (event) {
        event.preventDefault();
        let materialId = $(this).attr('data-material-id');
        vueInstance.quickView(vueInstance.categorySlug, materialId);
    });
});