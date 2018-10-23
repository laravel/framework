/*
 * Global variables
 */
let editSiteMFormValidator, addRoomFormValidator, editRoomFormValidator, addAcFormValidator, editAcFormValidator, addFireSpFormValidator, editFireSpFormValidator, notificationTimeout = 10000, notificationTimeoutID;
var oldWindowObjArray = [], oldDoorsObjArray = [], oldFurnituresObjArray = [], currentRoomMeasurement = [];      
//Counter variables keeps track of Add/Remove Notes Categ. Blocks
var notesCatgIncrementCounter = 0, editnotesCatgIncrementCounter = 1;

//Include needed packages
require('../../bootstrap');
require('select2');
require('magnific-popup');

//Resolve Jquery library conflict
var jquery = require('jquery');

//Register Vue components
//Shows User primary info...
import UserInformation from '../../components/siteMeasurement/userInformation';
//Shows SM Scanned Copy upload
import ScannedcopyUpload from '../../components/siteMeasurement/editScannedCopies';
//Shows Manual Checklist Copy upload
import ChecklistUpload from '../../components/siteMeasurement/editChecklistCopies';
//Shows Site Photos And Videos upload
import SiteAttachments from '../../components/siteMeasurement/editSiteAttachments';
//Shows Add Room popup Room pictures upload
import RoomAttachments from '../../components/siteMeasurement/roomAttachments';
//Shows Edit Room popup Room pictures upload
import EditRoomAttachments from '../../components/siteMeasurement/editRoomAttachments';
//Shows Room Notes popup
import RoomNotes from '../../components/siteMeasurement/roomNotes';
//Success/Failure message overlay
import OverlayNotification from '../../components/overlayNotification';
//Shows list of Ac Attachments
import AcAttachments from '../../components/siteMeasurement/AcAttachments';
//Shows Edit Ac Attachments
import EditAcAttachments from '../../components/siteMeasurement/EditAcAttachments';

//Variables used in Vue application
let vueVariables = {
    CdnUrl: CDNUrl,
    description: description, // Stores site measurement description
    totalSiteAttachments: [], // flag keep track of total  site measurement uploaded files
    newSiteAttachments: [], // flag to keep track of newly added  site measurement files
    deletedSiteAttachments: [], // flag to keep track of  site measurement deleted files
    oldSiteAttachments: [],
    showSiteAttachmentsBlock: false, // flag which toggles  site measurement file list block
    selectedRoomArea: '', // Selected Room area in add room modal
    isRoomBlockHide: true,
    windowQuantity: 1,
    doorQuantity: 1,
    defaultWindowQuantity: 1, // Default windows to be selected
    defaultDoorQuantity: 1,
    totalRoomAttachments: [], // Stores uploaded rooms pics in add room modal
    showRoomsAttachmentsBlock: false, // toggle room attachment file list in add room modal
    newRoomAttachments: [], // array flag to keep track of newly uploaded room files in add room modal
    noteCategories: notecategories, // stores total note categories available
    notesCategoryblocks: [notesCatgIncrementCounter], // array flag to add and remove note categories in add room modal
    roomId: '',
    rooms: rooms, // array flag to store all RoomAreas
    roomSpecifications: '', // array flag to store room spec
    currentRoomArea: '', // stores room area name to be edit
    editRoomWindows: [], // array flag to store room  doors
    windowquanity: windowsQuanity, // no of windows to show in window selector
    doorsquantity: doorsQuantity, // no of doors to show in window selector
    editRoomDoors: [], // array flag to store room  doors
    showEditRoomAttachmentsBlock: false, // toggle
    totalEditRoomAttachments: [], // array flag to keep track of total room files in edit room modal
    oldEditRoomAttachments: [],
    deletedEditRoomAttachments: [], // array flag to keep track of deleted room files in edit room modal
    newEditRoomAttachments: [], // array flag to keep track of new room files in edit room modal
    windowSelected: 1, // default window number selected if total windows are empty in Edit Room modal
    doorSelected: 1, // default door number selected if total doors are empty in Edit Room modal
    roomMeasurementNotes: [], // array flag to keep track of room measurments notes in edit room modal
    editRoomId: '', // stores to be edit room id
    roomsViewData: [], // stores all created room measurements for view purpose     
    editRoomNoteCategories: [],
    showEditRoomNotesCategoryBlock: true,
    QERooms: QERooms, // Stores quick estimate rooms
    enquiryProject: projectData.user, // Stores enquiry info with Customer details
    totalScannedCopies: [],
    newScannedCopies: [],
    deleteScannedCopies: [],
    oldScannedCopies: [],
    shouldRenderScannedCopies: false,
    totalChecklistCopies: [],
    newChecklistCopies: [],
    deleteChecklistCopies: [],
    oldChecklistCopies: [],
    shouldRenderChecklistCopies: false,
    FormOverLay: true,
    NotificationIcon: "",
    NotificationMessage: "",
    defaultFurnitureQnty: 1,
    furnituresquantity: furnituresQuantity, // No of furnitures to add for room 
    totalSelectedFurnitures: 1,
    furnituresSelected: 1, // default no of furnitures selected if no furnitures selected in Edit Room modal
    editRoomFurnitures: [], // array flag to store room furnitures
    IsPowerPointAvailable: false,
    IsDrainagePointAvailable: false,
    IsCoreCuttingAvailable: false,
    IsCopperCuttingAvailable: false,
    FireSprinklers: [],
    EditPageFireSprinklers: [], // Stores added fire Sprinklers for edit fire sp pop up
    RoomMeasurementId: "",
    TotalAcAttachments: [], // Stores uploaded AC attachments in add AC modal
    ShowAcAttachmentsBlock: false, // Toggle AC attachment file list in add AC modal
    EditAcData: {},
    ShowEditAcAttachmentsBlock: false,
    TotalEditAcAttachments: [], // Stores edit Ac total old and uploaded attachments
    OldEditAcAttachments: [], // Stores edit Ac old attachments
    DeletedEditAcAttachments: [], // Stores edit Ac deleted attachments
    NewEditAcAttachments: [], // Stores edit Ac new uploaded attachments
    DesignItems: DesignItems // Stores Design items
};
    
//Initialize Vue instance
const ViewModelInstance = new Vue({
    
    //Vue root element
    el: '#EditSiteMeasurementPage',
    
    //Vue data variables 
    data: vueVariables,
    
    //Vue components
    components: {
        
        'user-information': UserInformation,
        'scannedcopy-upload': ScannedcopyUpload,
        'checklist-upload': ChecklistUpload,
        'site-attachments': SiteAttachments,
        'room-attachments': RoomAttachments,
        'edit-room-attachments': EditRoomAttachments,
        'room-notes': RoomNotes,
        'overlay-notification': OverlayNotification,
        'ac-attachments': AcAttachments,
        'edit-ac-attachments': EditAcAttachments
    },
        
   //Vue life cycle hook     
    mounted() {

        //Projects Select2 initialization
        this.initializeProjectsSelect2();
        this.oldSiteAttachments = siteUploadedFiles["SMPhotos"];
        this.oldScannedCopies = siteUploadedFiles["SMScannedCopies"];
        this.oldChecklistCopies = siteUploadedFiles["SMChecklistCopies"];
        this.roomsViewData = roomsViewData;
        this.toggleAcCheckboxFlag(this.roomsViewData);
        //Initialize Site Photos array
        this.assignExistingFilestoVueVariable(this.totalSiteAttachments, siteUploadedFiles["SMPhotos"], "SitePhotos");
        //Initialize SM Scanning Photos array
        this.assignExistingFilestoVueVariable(this.totalScannedCopies, siteUploadedFiles["SMScannedCopies"], "ScannedCopy");
        //Initialize SM CheckList Photos array
        this.assignExistingFilestoVueVariable(this.totalChecklistCopies, siteUploadedFiles["SMChecklistCopies"], "ChecklistCopy");
        $("#siteInfo").removeClass("hidden");
        $("#SiteDetails").html(projectData.siteInfo);
    },
    
   //Vue computed properties 
    computed: {

        //return true if room measurements not found
        roomsNotFoundMessageHide: function () {
            let selectedCount = this.roomsViewData.length;
            if (selectedCount === 0) {
                return false;
            } else {
                return true;
            }
        },

        //toggles remove note category icon based on room notes
        isEditRoomNoteRemoveIconHide: function () {
            let selectedCount = this.roomMeasurementNotes.length;
            if (selectedCount === 0) {
                return true;
            } else {
                return false;
            }
        },

        //toggles add note category icon based on note category count
        isAddNoteIconHide: function() {
            let selectedCount = this.noteCategories.length;
            if (selectedCount > 1) {
                return false;
            } else {
                return true;
            }
        },

        showEditRoomAddNoteIcon: function() {
            let selectedCount = this.editRoomNoteCategories.length;
            if (selectedCount > 1) {
                return false;
            } else {
                return true;
            }
        },

        //filters note categories
        filteredNoteCategories: function () {
            return _.sortBy(this.noteCategories, ["Name"]);
        },
        
        //Get Fire Sprinklers available
        fireSprinklers() {
            
            if (this.EditPageFireSprinklers) {
                return this.EditPageFireSprinklers;
            }
            return null;
        },
        
        //Filter Ac data
        filteredAcData() {
            
            if(!_.isEmpty(this.EditAcData)) {
                return this.EditAcData;
            } else {
                return {
                    "WallDirection": "",
                    "CopperCutting": {
                        "IsAvailable": false,
                        "PFC": "",
                        "PFLW": ""
                    },
                    "CoreCutting": {
                        "IsAvailable": false,
                        "PFC": "",
                        "PFLW": ""
                    },
                    "PowerPoint": {
                        "IsAvailable": false,
                        "PFC": "",
                        "PFLW": ""
                    },
                    "DrainagePoint": {
                        "IsAvailable": false,
                        "PFC": "",
                        "PFLW": ""
                    },
                    "OutdoorUnit": {
                        "Notes": "",
                        "Attachments": ""
                    }
                };
            }
        }
    },
    
    //Vue Watchers
    watch: {
        //Power Point fields validation
        IsPowerPointAvailable: function (isAvailable) {
            if (isAvailable === true) {
                
               this.addValidationToAcFields("power-point", "PowerPointPFC", "PowerPointPFL");
            } else {
                
                this.removeValidationOfAcFields("power-point", "PowerPointPFC", "PowerPointPFL");
            }
        },
        
        //Drainage Point fields validation
        IsDrainagePointAvailable: function (isAvailable) {            
            if (isAvailable === true) {
                
               this.addValidationToAcFields("drainage-point", "DrainagePointPFC", "DrainagePointPFL",);
            } else {
                
                this.removeValidationOfAcFields("drainage-point", "DrainagePointPFC", "DrainagePointPFL");
            }
        },
        
        //Core Cutting fields validation
        IsCoreCuttingAvailable: function (isAvailable) {           
            if (isAvailable === true) {
                
               this.addValidationToAcFields("core-cutting", "CoreCuttingPFC", "CoreCuttingPFL");
            } else {
                
                this.removeValidationOfAcFields("core-cutting", "CoreCuttingPFC", "CoreCuttingPFL");
            }
        },
        
        //Copper Cutting fields validation
        IsCopperCuttingAvailable: function (isAvailable) {           
            if (isAvailable === true) {
                
               this.addValidationToAcFields("copper-cutting", "CopperCuttingPFC", "CopperCuttingPFL");
            } else {
                
                this.removeValidationOfAcFields("copper-cutting", "CopperCuttingPFC", "CopperCuttingPFL");
            }
        }
    },
    
    //Vue methods 
    methods: {
        //Get Design items
        getDesignItem(itemId) {
            if (itemId) {
                if (this.DesignItems.length > 0) {
                    let item = _.find(this.DesignItems, ["Id", itemId]);
                    if (!_.isUndefined(item)) {
                        return item.Name+':';
                    }
                }
                return "<small>N/A:</small>";
            }
            return "<small>N/A:</small>";
        },
        //Open Add Ac Pop up
        openAddAcModal(roomMeasurement) {
            
            if (roomMeasurement) {
                this.RoomMeasurementId = roomMeasurement;
                $("#AddAcModal").modal({"show": true});
            }
        },
        
        //Open Edit Ac Pop up
        openEditAcModal(roomMeasurement) {

            if (roomMeasurement) {
                this.RoomMeasurementId = roomMeasurement;
                let measurement = (_.find(this.roomsViewData, ["id", roomMeasurement]));
                if (!_.isUndefined(measurement)) {
                    this.EditAcData = measurement.acspecifications;
                    if (!_.isNull(this.EditAcData)) {
                        //Get room's old attachments
                        this.OldEditAcAttachments = this.getAcAttachments();
                        this.assignExistingFilestoVueVariable(this.TotalEditAcAttachments, this.OldEditAcAttachments, "EditAcPhotos");
                        $("#EditAcModal").modal({"show": true});
                        this.initializePowerPointCheckbox();
                        this.initializeDrainagePointCheckbox();
                        this.initializeCoreCuttingCheckbox();
                        this.initializeCopperPipingCheckbox();
                    }
                }
            }
        },
        
        //Get Ac attachments
        getAcAttachments() {

            let imageClass = "image";
            let self = this;
            if (typeof this.EditAcData.Attachments === 'string') {
                this.EditAcData.Attachments = JSON.parse(this.EditAcData.Attachments);
                this.EditAcData.Attachments.forEach(function (obj) {
                    obj.title = obj.UserFileName;
                    let ext = obj.UserFileName.split('.');
                    let extension = ext[1].toLowerCase();
                    let extensionsExist = ["png", "jpeg", "jpg", "bmp"];
                    if (!jQuery.inArray(extension, extensionsExist)) {
                        imageClass = 'iframe';
                    }
                    obj.type = imageClass;
                    obj.src = self.CdnUrl + obj.Path;
                    let parts = obj.Path.split("/");
                    obj.renamedFileName = parts[parts.length - 1];
                    delete obj.Path;
                    delete obj.UserFileName;
                });
            }
            return this.EditAcData.Attachments;
        },
        
        //Toggle IsAvailable checkbox field of Ac items
        toggleAcCheckboxFlag(roomsData) {
            
            if (roomsData.length > 0) {
                roomsData.forEach(function (obj) {
                    if (obj.acspecifications) {
                        obj.acspecifications.CopperCutting.IsAvailable = (obj.acspecifications.CopperCutting.IsAvailable) ? true : false;
                        obj.acspecifications.CoreCutting.IsAvailable = (obj.acspecifications.CoreCutting.IsAvailable) ? true : false;
                        obj.acspecifications.PowerPoint.IsAvailable = (obj.acspecifications.PowerPoint.IsAvailable) ? true : false;
                        obj.acspecifications.DrainagePoint.IsAvailable = (obj.acspecifications.DrainagePoint.IsAvailable) ? true : false;
                    }
                });
            }
        },
        
        //Add validation to AC fields
        addValidationToAcFields(CommonClassVar, PFC, PFL) {

            this.$nextTick(function () {

                $("#" + PFC).rules("add", {
                    required: true,
                    messages: {
                        required: "PFC can't be blank."
                    },
                    number: true,
                    min: 1,
                    maxlength: 6
                });
                $("#" + PFL).rules("add", {
                    required: true,
                    messages: {
                        required: "PFLW can't be blank."
                    },
                    number: true,
                    min: 1,
                    maxlength: 6
                });
            });
        },

        //Remove validation from AC fields
        removeValidationOfAcFields(CommonClassVar, PFC, PFL) {

            $("." + CommonClassVar).val("");
            $(`input[id="${PFC}"`).rules("remove");
            $(`input[id="${PFC}"`).valid();
            $(`input[id="${PFL}"`).rules("remove");
            $(`input[id="${PFL}"`).valid();
        },
   
        //Open Fire Sprinkler Pop up
        openAddFireSpModal(roomMeasurement) {

            if (roomMeasurement) {
                this.RoomMeasurementId = roomMeasurement;
                let measurement = _.find(this.roomsViewData, ["id", roomMeasurement]);
                if (!_.isUndefined(measurement)) {
                    if (this.FireSprinklers.length === 0) {
                        this.addFireSprinkler();
                    }
                    $("#AddFireSpModal").modal({"show": true});
                    this.addValidationToFireSFields();
                }
            }
        },
        
        //Add new Fire Sprinkler
        addFireSprinkler() {

            if (this.FireSprinklers.length < FireSprinklersLimit) {
                this.FireSprinklers.push({
                    "FireSpLocDir": "",
                    "FireSpPFC": "",
                    "FireSpPFL": "",
                    "FireSpAttachment": ""
                });
                this.addValidationToFireSFields();
            }
        },
                 
        //Remove Fire S. from list
        removeFireSprinkler(sprinlker) {

            if (this.FireSprinklers.length > 1) {
                this.FireSprinklers.splice(sprinlker, 1);
                var uploadElement = document.querySelectorAll(`input[id="FireSpAttachment_${sprinlker}"]`);
                for (let file = 0; file < uploadElement.length; file++) {

                    uploadElement[file].value = '';
                }
            }
        },
        
        //Add validation to Fire S. fields
        addValidationToFireSFields() {

            this.$nextTick(function () {

                $("div#AddFireSpModal select.firesp-direction").each(function () {

                    $(this).rules("add", {

                        required: true,
                        messages: {
                            required: "Wall Direction can't be blank."
                        }
                    });
                });

                $("div#AddFireSpModal input.firesp-pfc").each(function () {

                    $(this).rules("add", {

                        required: true,
                        messages: {
                            required: "PFC can't be blank."
                        },
                        number: true,
                        min: 1,
                        maxlength: 6
                    });
                });

                $("div#AddFireSpModal input.firesp-pfl").each(function () {

                    $(this).rules("add", {

                        required: true,
                        messages: {
                            required: "PFLW can't be blank."
                        },
                        number: true,
                        min: 1,
                        maxlength: 6
                    });
                });

                $("div#AddFireSpModal input.fire-sp-file").each(function () {

                    $(this).rules("add", {

                        required: true,
                        messages: {
                            required: "Attachments can't be blank."
                        },
                        CheckFileExtension: true,
                        checkMultipleFilesSize: true,
                        checkPerFileSizeInMultipleFiles: true
                    });
                });
            });
        },
        
        //Open Edit Fire Sprinkler Pop up
        openEditFireSpModal(roomMeasurement) {

            if (roomMeasurement) {
                this.RoomMeasurementId = roomMeasurement;
                let measurement = _.find(this.roomsViewData, ["id", roomMeasurement]);
                if (!_.isUndefined(measurement)) {
                    this.EditPageFireSprinklers = measurement.firespspecifications;
                    $("#EditFireSpModal").modal({"show": true});
                    this.addValidationToEditFireSFields();
                }
            }
        },
        
        //Add new Fire Sprinkler
        addNewFireSprinklers() {

            if (this.EditPageFireSprinklers.length < FireSprinklersLimit) {
                this.EditPageFireSprinklers.push({
                    "WallDirection": "",
                    "PFC": "",
                    "PFLW": "",
                    "Attachments": ""
                });
                this.addValidationToEditFireSFields();
            }
        },
        
        //Add validation to Fire S. fields
        addValidationToEditFireSFields() {

            this.$nextTick(function () {

                $("div#EditFireSpModal select.edit-firesp-direction").each(function () {

                    $(this).rules("add", {

                        required: true,
                        messages: {
                            required: "Wall Direction can't be blank."
                        }
                    });
                });

                $("div#EditFireSpModal input.edit-firesp-pfc").each(function () {

                    $(this).rules("add", {

                        required: true,
                        messages: {
                            required: "PFC can't be blank."
                        },
                        number: true,
                        min: 1,
                        maxlength: 6
                    });
                });

                $("div#EditFireSpModal input.edit-firesp-pfl").each(function () {

                    $(this).rules("add", {

                        required: true,
                        messages: {
                            required: "PFLW can't be blank."
                        }, 
                        number: true,
                        min: 1,
                        maxlength: 6
                    });
                });

                $("div#EditFireSpModal input.edit-fire-sp-file").each(function () {

                    let uploadValue = $(this).data("value");  
                    $(this).rules("add", {

                        required: function() {
                            if(!uploadValue) {
                               return true;
                            }
                            return false;
                        },
                        messages: {
                            required: "Attachments can't be blank."
                        },
                        CheckFileExtension: true,
                        checkMultipleFilesSize: true,
                        checkPerFileSizeInMultipleFiles: true
                    });
                });
            });
        },
        
        //Delete Fire Sprinkler
        deleteFireSprinkler(key) {

            if (this.EditPageFireSprinklers.length > 0) {
                if (this.EditPageFireSprinklers.length === 1) {
                    //Make tracking array empty and push one dummy object if user deletes all sprinklers
                    let measurement = _.find(this.roomsViewData, ["id", this.RoomMeasurementId]);
                    if (!_.isUndefined(measurement)) {
                        measurement.firespspecifications = "";
                    }
                    this.EditPageFireSprinklers = [];

                    this.EditPageFireSprinklers.push({
                        "WallDirection": "",
                        "PFC": "",
                        "PFLW": "",
                        "Attachments": ""
                    });
                    $(".tooltip").css("display", "none");
                    return;
                }
                this.EditPageFireSprinklers.splice(key, 1);
                var uploadElement = document.querySelectorAll(`input[id="EditFireSpAttachment_${key}"]`);
                for (let file = 0; file < uploadElement.length; file++) {

                    uploadElement[file].value = '';
                }
                $(".tooltip").css("display", "none");
            }
        },
        
        //Check condition to toggle delete fire sp icon
        hideDeleteFireSpIcon(sprinkler) {
          
            if(sprinkler.WallDirection === '' && sprinkler.PFC === '' && sprinkler.PFLW === '' && sprinkler.Attachments === '' && this.EditPageFireSprinklers.length === 1) {
                return false;
            }
            return true;
        },
             
       //On Change event of file upload 
        onUploadChange(event, totalCopies, newScannedCopies) {
            
            //Hide file list block, if upload is invalid
            let id = event.target.id;
            
            let validator = editSiteMFormValidator;
                        
            let objProperty = "fileName";
            
            if(id === "UploadEditRoomPics") {
                
                validator = editRoomFormValidator;
            }
            
            if (id === "EditWallDirectionAttachments") {

                validator = editAcFormValidator;
            }
            
            if (!validator.element("#"+event.target.id)) {
                
                if(id === "EditSMCopy") {
                    
                    this.shouldRenderScannedCopies = false;
                } else if(id === "EditChecklistCopy") {
                        
                    this.shouldRenderChecklistCopies = false;
                } else if(id === "UploadFiles") {
                        
                    this.showSiteAttachmentsBlock = false;
                } else if(id === "UploadEditRoomPics") {
                        
                    this.showEditRoomAttachmentsBlock = false;
                } else if (id === "EditWallDirectionAttachments") {
                    
                    this.ShowEditAcAttachmentsBlock = false;
                }
                return false;
            } 
            
            var files = event.target.files;
            
            let filesLength = files.length;
            
            //Hide file list block based on total files available
            //SM Scanned Copy
            if(id === "EditSMCopy") {
                
                this.shouldRenderScannedCopies = (filesLength > 0 || totalCopies.length > 0);
                
            //Manual Checklist Copy
            } else if(id === "EditChecklistCopy") {
                        
                this.shouldRenderChecklistCopies = (filesLength > 0 || totalCopies.length > 0);
            
            //Site Photos    
            } else if(id === "UploadFiles") {
                        
                this.showSiteAttachmentsBlock = (filesLength > 0 || totalCopies.length > 0);
                
            //Edit Room upload Photos    
            } else if(id === "UploadEditRoomPics") {
                
                this.showEditRoomAttachmentsBlock = (filesLength > 0 || totalCopies.length > 0);
      
                objProperty = "title";
            } if (id === "EditWallDirectionAttachments") {
                
                this.ShowEditAcAttachmentsBlock = (filesLength > 0 || totalCopies.length > 0);
      
                objProperty = "title";
            }
            
            for (let i = 0; i < filesLength; i++) {

                var renamedFileName = Math.random().toString(36).slice(2);
                
                //Push attachment name and renamed name to totalCopies array
                totalCopies.push({
                    //ES6 JavaScript feature to define dynamic Key
                    [objProperty]: files[i].name,
                    "renamedFileName": renamedFileName
                });
                
                //Push attachments to new attachments array    
                newScannedCopies.push({
                    "fileName": files[i].name,
                    "renamedFileName": renamedFileName,
                    "fileObject": files[i]
                });
            }
        },
        
        //Deletes uploaded files from list
        deleteFiles(deletedObject) {
            
            let totalFiles = deletedObject.totalCopies;
            let uploadElement = deletedObject.uploadElement;
            let attachment = deletedObject.renamedFile;
            let newFiles = deletedObject.newUploadedcopies;
            let oldFiles = deletedObject.oldCopies;
            let deletedFiles = deletedObject.deletedCopies;
            
            for (let i = totalFiles.length - 1; i >= 0; i--) {

                if (totalFiles[i].renamedFileName === attachment) {

                    //Check deleted file is exists in old attachments array if exists add it to deleted array
                    if (typeof oldFiles !== 'undefined' && oldFiles.length > 0) {

                        if (this.isFileExistsInArray(totalFiles[i], oldFiles)) {

                            this.pushAttachmentsToDeletedArray(totalFiles[i].renamedFileName, oldFiles, deletedFiles);
                        }
                    }
                    
                    //If exists in new files array delete it from new files array
                    if (this.isFileExistsInArray(totalFiles[i], newFiles)) {

                        this.removeAttachmentFromNewAttachmentsArray(totalFiles[i], newFiles);
                    }
                    totalFiles.splice(i, 1);
                }
            }
            
            //Hide Specific file list block
            if(uploadElement === "EditSMCopy") {
                
                this.shouldRenderScannedCopies = totalFiles.length > 0;
            } else if(uploadElement === "EditChecklistCopy") {
                
                this.shouldRenderChecklistCopies = totalFiles.length > 0;
            } else if(uploadElement === "UploadFiles") {
                
                this.showSiteAttachmentsBlock = totalFiles.length > 0;
            } else if(uploadElement === "UploadEditRoomPics") {
                        
                this.showEditRoomAttachmentsBlock = totalFiles.length > 0;
            } else if (uploadElement === "EditWallDirectionAttachments") {
                
                this.ShowEditAcAttachmentsBlock = totalFiles.length > 0;
            }
            
            //Clear Upload field, if user deletes all selected files from list
            ViewModelInstance.$nextTick(function () {

                if (totalFiles.length === 0) {

                   this.clearFileUpload(uploadElement);
                }
            });
        },
        
        //Clears files from upload element
        clearFileUpload(elementId) {
            
            var $el = $('#'+elementId);
            $el.wrap('<form>').closest('form').get(0).reset();
            $el.unwrap();
        },
       
        //On Change event of Add Room
        onRoomUploadChange(elementEvent, totalCopies) {

            let id = elementEvent.target.id;

            //Hides files if element is invalid
            if (!$("#"+id).valid()) {

                if(id === "UploadRoomPics") {

                    this.showRoomsAttachmentsBlock = false;
                } else if(id === "WallDirectionAttachments") {

                    this.ShowAcAttachmentsBlock = false;
                }
                return false;
            }

            var files = elementEvent.target.files;

            let fileArray = Array.from(files);

            let totalUploadedFiles = fileArray.length;

            this.showRoomFiles(id, totalUploadedFiles, totalCopies);

            this.storeRoomFiles(fileArray, totalCopies);
        },
            
        //Shows Room files on upload
        showRoomFiles(id, uploadedFiles, totalStoredFiles) {

            if(id === "UploadRoomPics") {

               this.showRoomsAttachmentsBlock = (uploadedFiles > 0 || totalStoredFiles.length > 0); 
            } else if(id === "WallDirectionAttachments") {

               this.ShowAcAttachmentsBlock = (uploadedFiles > 0 || totalStoredFiles.length > 0); 
            }            
        },
       
        //Stores files into array variable, if file doesn't exists in array
        storeRoomFiles(filesArray, totalFiles) {

            for (let j = 0; j < filesArray.length; j++) {

                if (this.isFileNameExistsInObject(filesArray[j].name, totalFiles)) {

                    totalFiles.push(filesArray[j]);
                }
            }
        },
        
        //Deletes uploaded Room files from list
        deleteRoomFile(elementObject) {

            if (elementObject.UploadElement === "UploadRoomPics") {

                var totalFiles = this.totalRoomAttachments;

                totalFiles.splice(elementObject.FileIndex, 1);

                this.showRoomsAttachmentsBlock = totalFiles.length > 0;
            } else if (elementObject.UploadElement === "WallDirectionAttachments") {

                var totalFiles = this.TotalAcAttachments;

                totalFiles.splice(elementObject.FileIndex, 1);

                this.ShowAcAttachmentsBlock = totalFiles.length > 0;
            }

            if (totalFiles.length === 0) {

                this.clearFileUpload(elementObject.UploadElement);
            }
        },
 
        //Check whether file name exists in objects array or not
        isFileExistsInArray(attachment, oldAttachmentsArray) {
            
            let files = _.find(oldAttachmentsArray, ["renamedFileName", attachment.renamedFileName]);
            if (files) {
                
                return true;
            }
            return false;
        },

        //Push removed file to deleted array
        pushAttachmentsToDeletedArray(renamedFilename, oldFilesArray, deletedFileArray) {
            
            let fileIndex = _.findIndex(oldFilesArray, ["renamedFileName", renamedFilename]);
            
            if (fileIndex !== -1) {
                
                deletedFileArray.push(oldFilesArray[fileIndex].renamedFileName);
            }         
        },

        //Remove attachment from new attachments array
        removeAttachmentFromNewAttachmentsArray(attachment, newFilesArray) {

            let index = _.findIndex(newFilesArray, ["renamedFileName", attachment.renamedFileName]);
            
            newFilesArray.splice(index, 1);
        },

        //Assign already uploaded files to Vue Variable
        assignExistingFilestoVueVariable(totalAttachmentsArray, files, uploadType) {

            for (let file = 0; file < files.length; file++) {

                totalAttachmentsArray.push(files[file]);
            }
            
            this.showlist(totalAttachmentsArray, uploadType);   
        },

        //Show file list block
        showlist(attachments, uploadType) {
            
            if(uploadType === "SitePhotos") {
                
                return (this.showSiteAttachmentsBlock = attachments.length > 0); 
            } else if(uploadType === "ScannedCopy") {
                
                return (this.shouldRenderScannedCopies = attachments.length > 0); 
            } else if(uploadType === "ChecklistCopy") {
                
                return (this.shouldRenderChecklistCopies = attachments.length > 0); 
            } else if(uploadType === "EditRoomPhotos") {
                
                return (this.showEditRoomAttachmentsBlock = attachments.length > 0); 
            } else if(uploadType === "EditAcPhotos") {
                
                return (this.ShowEditAcAttachmentsBlock = attachments.length > 0); 
            }
        },
        
        //Add more note categories in Edit Room modal
        addMoreNotes() {

            let maxNotesBlock = this.editRoomNoteCategories.length;
            
            if (this.roomMeasurementNotes.length >= 1) {
                
                //take notes tracking array length in counter 
                editnotesCatgIncrementCounter = this.roomMeasurementNotes.length;
            }
            
            //add dummy object into notes tracking array
            if (editnotesCatgIncrementCounter < maxNotesBlock) {

                this.roomMeasurementNotes.push({
                    "Id" : "",
                    "Name": "",
                    "description": "",//Make new note description empty
                });
                editnotesCatgIncrementCounter++;
            }         
            
            //Validate elements in Edit Room
            EditRoomNotesElementValidationRules(this.roomMeasurementNotes.length);
        },

        //Remove edit room notes block
        removeEditRoomNotes() {
            //when user removes all note's blocks, we  need to keep one block so add one dummy object to array
            if(this.roomMeasurementNotes.length === 1) {
                //make note's blocks tracking array empty 
                this.roomMeasurementNotes = [];
                //make each CategoryElementId in editRoomNoteCategories array 1
                 _.each(this.editRoomNoteCategories, function(item){ item.CategoryElementId = 1;});
                 _.sortBy(this.editRoomNoteCategories, ["Name"]);
                 //push dummy object for showing at least one note's block
                this.roomMeasurementNotes.push({
                    "Id" : "",
                    "Name": "",
                    "description": ""
                });
                //set first select category box to null and remove validation error messages
                this.removeNotesValidationRules();
                return;
            }
            //when user removes one block at a time
            if (this.roomMeasurementNotes.length > 1) {
                //remove one block
                this.roomMeasurementNotes.pop();
                let totalNotesAvailable = this.roomMeasurementNotes.length;
                //update CategoryElementId to 1 in editRoomNoteCategories
                let elementIndex = _.findIndex(this.editRoomNoteCategories, function (item) {

                    return item.CategoryElementId == 'EditNoteCategory[' + [totalNotesAvailable] + ']';
                });
                if (elementIndex !== -1) {

                    this.editRoomNoteCategories[elementIndex].CategoryElementId = 1;
                    _.sortBy(this.editRoomNoteCategories, ["Name"]);
                }
                editnotesCatgIncrementCounter--;
            }
        },
        
        //remove first note error messages and values
        removeNotesValidationRules() {
 
            $("input[id='EditNoteDescription[0]'").val("");
            $("[id='EditNoteCategory[0]'").rules("remove");
            $("[id='EditNoteCategory[0]'").parent().removeClass('has-error');
            $("input[id='EditNoteDescription[0]'").rules("remove");
            $("input[id='EditNoteDescription[0]'").valid();
            //clear note's upload attachment element
            var notesUploadElements = document.querySelectorAll('input[id^="EditUploadNoteAttachmnt_"]');
            for (let note = 0; note < notesUploadElements.length; note++) {

                notesUploadElements[note].value = '';
            }
            $("#EditUploadNoteAttachmnt_0").valid();
        },

        //store new room's attachments
        storeNewRoomAttachments(picsArray, newFilesArray) {

            for (let j = 0; j < picsArray.length; j++) {

                if (this.isFileNameExistsInObject(picsArray[j].name, newFilesArray)) {

                    newFilesArray.push(picsArray[j]);
                }
            }
        },

        //check object property value exists in objects array
        isValueExistsInObject(Name, array) {

            if (array.length > 0) {
                
                let files = _.find(array, ["name", Name]);
                if (files) {

                    return true;
                }
                return false;
            }
            return false;
        },

        //Add Room modal pop up
        addRoomModal() {

            $("#AddRoomModal").modal({
                show: true
            });
            addRoomNotesElementValidationRules(false);
        },

        //check whether file name exists in object array or not
        isFileNameExistsInObject(name, list) {

            if (list.length > 0) {

               let files = _.find(list, ["name", name]);
                if (files) {

                    return false;
                }
                return true;
            }
            return true;
        },   

        //add more note categories in add room modal
        addNotesBlock() {

            let maxNotesBlock = this.noteCategories.length - 1; 
            (notesCatgIncrementCounter === 0) ? notesCatgIncrementCounter++ : notesCatgIncrementCounter;
            if (notesCatgIncrementCounter <= maxNotesBlock) {

                this.notesCategoryblocks.push(notesCatgIncrementCounter);
                notesCatgIncrementCounter++;
            }
            
            //Add Room Notes block element validation rules
            addRoomNotesElementValidationRules(true);
        },
        
        //Remove note category block in add room modal
        removeNotesBlock() {
                        
            //set CategoryElementId for all note categories to 1 if all notes blocks are removed
            if (this.notesCategoryblocks.length === 1) {

                _.each(this.noteCategories, function(item){ item.CategoryElementId = 1;});
                this.removeEditRoomNoteValidationRules();
            }
            
            if (this.notesCategoryblocks.length > 1) {

                let deletedIndex  = this.notesCategoryblocks.pop();

                //make CategoryElementId as 1 so that we can enable deleted note category in other note category array
                let elementIndex = _.findIndex(this.noteCategories, function (item) {

                    return item.CategoryElementId == 'NoteCategory[' + [deletedIndex] + ']';
                });
                if(elementIndex !== -1) {

                   this.noteCategories[elementIndex].CategoryElementId = 1;
                }
                notesCatgIncrementCounter--;
            }
        },
        
        //Remove first note error messages and values
        removeEditRoomNoteValidationRules() {
           
            $("[id='NoteCategory[0]'").rules("remove");
            $("[id='NoteCategory[0]'").val(null);
            $("[id='NoteCategory[0]'").parent().removeClass('has-error');
            $("input[id='NoteDescription[0]'").rules("remove");
            $("input[id='NoteDescription[0]'").val("");
            $("input[id='NoteDescription[0]'").valid();
            notesCatgIncrementCounter = 0;
            this.notesCategoryblocks = [];
            this.notesCategoryblocks.push(notesCatgIncrementCounter);
            //clear all notes uploads elements
            var notesUploadElements = document.querySelectorAll('input[id^="UploadNoteAttachmnts"]');
            for (let note = 0; note < notesUploadElements.length; note++) {

                notesUploadElements[note].value = '';
            }
            $("#UploadNoteAttachmnts_0").valid();
        },
        
        //Close add room form
        closeAddRoomModal() {

            $('#AddRoomModal').modal('toggle');
        },

        //Clear add room form
        clearAddRoomForm() {

            addRoomFormValidator.resetForm();
            jquery("#Room").val(null).trigger('change');
            _.each(this.noteCategories, function(item){ item.CategoryElementId = 1;});
            this.totalRoomAttachments = [];
            this.defaultWindowQuantity = 1;
            this.defaultDoorQuantity = 1;
            this.windowQuantity = 1;
            this.doorQuantity = 1;
            this.defaultFurnitureQnty = 1;
            this.totalSelectedFurnitures = 1;
            this.isRoomBlockHide = true;
            this.selectedRoomArea = '';
            this.notesCategoryblocks = [];
            notesCatgIncrementCounter = 0;
            this.notesCategoryblocks.push(notesCatgIncrementCounter);
            //clear all notes uploads elements
            var notesUploadElements = document.querySelectorAll('input[id^="UploadNoteAttachmnts"]');
            for (let note = 0; note < notesUploadElements.length; note++) {

                notesUploadElements[note].value = '';
            }
        },

        //Get room deletion confirmation from user
        deleteRoomConfirmation(deleteRoomClickEvent) {

            this.RoomMeasurementId = deleteRoomClickEvent.target.id;
            $("#DeleteRoomModal").modal({show:true});
        },

        //Issue room delete request
        deleteRoom() {

            $("#DeleteRoomFormOverlay").removeClass('hidden').find(".loader-text").html("Deleting Room");
            var currentUrl = window.location.href.split("/");
            let siteId = currentUrl.reverse()[1];
            let self = this;
            //make ajax request to delete a room
            $.ajax({
                url: $("#DeleteRoomModal").data("deleteroom-url") + '/' + siteId + '/' + this.RoomMeasurementId,
                type: 'GET',
                dataType: 'json'
            })
            .done(function (deletedRoomObj) {

                if (deletedRoomObj.status === "Room deleted") {

                    $("#DeleteRoomModal").modal("hide");

                    //remove deleted room from rooms array of objects for view section          
                    ViewModelInstance.roomsViewData.splice(_.findIndex(ViewModelInstance.roomsViewData, function (item) {

                        return item.id === deletedRoomObj.deletedroomeasurement.roomId;
                    }.bind(this)), 1);                         

                    //add deleted room to room area select box array based on Room's group
                    if (deletedRoomObj.deletedroomeasurement.deletedFromGroup === "QERoomsGroup") {

                        ViewModelInstance.QERooms.push(deletedRoomObj.deletedroomarea);
                        ViewModelInstance.QERooms = _.sortBy(ViewModelInstance.QERooms, 'Name');

                    } else {

                        ViewModelInstance.rooms.push(deletedRoomObj.deletedroomarea);
                        ViewModelInstance.rooms = _.sortBy(ViewModelInstance.rooms, 'Name');
                    }
                    InitializeRoomSelector();                            
                }  

            }.bind(this))
            .fail(function () {

                self.populateNotifications({
                    status: "error",
                    message: AlertData["10077"]
                }, "DeleteRoomNotificationArea");

                console.log("error");
            })
            .always(function () {

                $("#DeleteRoomFormOverlay").addClass('hidden');
            });

        },

        //function to get get room details and open in modal
        editRoomMeasurement(clickEvent) {

            let roomMeasurementId = clickEvent.target.id; 
            //Get current room details and store into respective objects
            currentRoomMeasurement = (_.find(this.roomsViewData, ["id", roomMeasurementId]));
            this.roomSpecifications = (_.find(this.roomsViewData, ["id", roomMeasurementId]));
            this.editRoomWindows = this.roomSpecifications.windows.windows;
            this.windowSelected = parseInt(this.roomSpecifications.windows.quantity);
            this.editRoomDoors = this.roomSpecifications.doors.doors;
            this.doorSelected = parseInt(this.roomSpecifications.doors.quantity);
            this.furnituresSelected = !_.isEmpty(this.roomSpecifications.furnitures) ? parseInt(this.roomSpecifications.furnitures.quantity) : 0;
            this.editRoomFurnitures = !_.isEmpty(this.roomSpecifications.furnitures) ? this.roomSpecifications.furnitures.furnitures : [];
            this.roomMeasurementNotes = currentRoomMeasurement.roomnotes.filter(function (x) {

                return (x.Name !== '' );
            });
            this.editRoomNoteCategories = this.roomSpecifications.allroomnotes;
            this.currentRoomArea = this.roomSpecifications.roomarea;
            this.editRoomId = roomMeasurementId;
            //Get room's old attachments
            this.oldEditRoomAttachments = this.roomSpecifications.roomattachments;
            //Get old room's windows and doors              
            oldWindowObjArray = this.roomSpecifications.windows.windows;
            oldDoorsObjArray = this.roomSpecifications.doors.doors;
            oldFurnituresObjArray = !_.isEmpty(this.roomSpecifications.furnitures) ? this.roomSpecifications.furnitures.furnitures : [];
            this.assignExistingFilestoVueVariable(this.totalEditRoomAttachments, this.oldEditRoomAttachments, "EditRoomPhotos");
            $("#EditRoomModal").modal({show:true});
            editRoomParamInputBoxesInitialization(true);
            //Validate edit /description and room notes upload element
            EditRoomNotesElementValidationRules(this.roomMeasurementNotes.length);
       },
        
        //Power Point fields validation
        initializePowerPointCheckbox() {

            if (this.filteredAcData.PowerPoint.IsAvailable) {

                this.addValidationToAcFields("edit-power-point", "EditPowerPointPFC", "EditPowerPointPFL");
            } else {
                
                this.removeValidationOfAcFields("edit-power-point", "EditPowerPointPFC", "EditPowerPointPFL");
                this.resetPowerPointValues();
            }
        },
   
        //Drainage Point fields validation
        initializeDrainagePointCheckbox() {

            if (this.filteredAcData.DrainagePoint.IsAvailable) {

                this.addValidationToAcFields("edit-drainage-point", "EditDrainagePointPFC", "EditDrainagePointPFL");
            } else {

                this.removeValidationOfAcFields("edit-drainage-point", "EditDrainagePointPFC", "EditDrainagePointPFL");
                this.resetDrainagePointtValues();
            }
        },
        
        //Core Cutting fields validation
        initializeCoreCuttingCheckbox() {

            if (this.filteredAcData.CoreCutting.IsAvailable) {

                this.addValidationToAcFields("edit-core-cutting", "EditCoreCuttingPFC", "EditCoreCuttingPFL");
            } else {

                this.removeValidationOfAcFields("edit-core-cutting", "EditCoreCuttingPFC", "EditCoreCuttingPFL");
                this.resetCoreCuttingValues();
            }
        },

        //Copper Cutting fields validation
        initializeCopperPipingCheckbox() {

            if (this.filteredAcData.CopperCutting.IsAvailable) {

                this.addValidationToAcFields("edit-copper-cutting", "EditCopperCuttingPFC", "EditCopperCuttingPFL");
            } else {

                this.removeValidationOfAcFields("edit-copper-cutting", "EditCopperCuttingPFC", "EditCopperCuttingPFL");
                this.resetCopperCuttingValues();
            }
        },
        
        resetPowerPointValues() {

            this.EditAcData.PowerPoint.IsAvailable = false;
            this.EditAcData.PowerPoint.PFC = this.EditAcData.PowerPoint.PFLW = "";
        },
        
        resetDrainagePointtValues() {

            this.EditAcData.DrainagePoint.IsAvailable = false;
            this.EditAcData.DrainagePoint.PFC = this.EditAcData.DrainagePoint.PFLW = "";
        },
        
        resetCoreCuttingValues() {

            this.EditAcData.CoreCutting.IsAvailable = false;
            this.EditAcData.CoreCutting.PFC = this.EditAcData.CoreCutting.PFLW = "";
        },

        resetCopperCuttingValues() {

            this.EditAcData.CopperCutting.IsAvailable = false;
            this.EditAcData.CopperCutting.PFC = this.EditAcData.CopperCutting.PFLW = "";
        },
        
        //Close the edit room modal on cancel button
        closeEditRoomModal() {
            
            //revert all elements to original values
            $("#EditRoomModal").modal("hide");
            this.removeNotesValidationRules();
            editRoomFormValidator.resetForm();
            $("#UploadEditRoomPics").val("");
            //clear all notes uploads elements
            var notesUploadElements = document.querySelectorAll('input[id^="EditUploadNoteAttachmnt_"]');

            for (let note = 0; note < notesUploadElements.length; note++) {

                notesUploadElements[note].value = '';
            }
            this.totalEditRoomAttachments = [];
            //reset no of windows/doors, notes array to original
            currentRoomMeasurement.windows.windows = currentRoomMeasurement.windows.windows.filter(function (x) {

                return (x.name !== (undefined || null || ''));
            });
            currentRoomMeasurement.doors.doors = currentRoomMeasurement.doors.doors.filter(function (x) {

                return (x.name !== (undefined || null || ''));
            });
            this.editRoomWindows = currentRoomMeasurement.windows.windows;
            this.editRoomDoors = currentRoomMeasurement.doors.doors;
            this.editRoomFurnitures = !_.isEmpty(currentRoomMeasurement.furnitures) ? currentRoomMeasurement.furnitures.furnitures : [];
            this.editRoomNoteCategories = [];
            //make note first row empty if modal closes without update
            if(currentRoomMeasurement.roomnotes[0].emptyNote === true) {

                currentRoomMeasurement.roomnotes[0].Id = currentRoomMeasurement.roomnotes[0].description = "";
            }
            var uploadElement = document.querySelectorAll(`input[type="file"]`);
            for (let file = 0; file < uploadElement.length; file++) {

                uploadElement[file].value = '';
            }
        },
        
        //Function to check unique note category from dropdown in Add Room Modal
        checkNoteCategoryConditions(blockNumber = 0, category) {

            //for first note category block
            if (blockNumber == 0 && (category.CategoryElementId == 1 || category.CategoryElementId === 'NoteCategory')) {

                return true;
            }
            //for other note category blocks
            if (category.CategoryElementId == 1 || category.CategoryElementId === 'NoteCategory[' + blockNumber + ']') {

                return true;
            }
            return false;
        },

        //Function to check unique note category from dropdown in Edit Room Modal
        checkEditRoomNoteCategoryConditions(blockNumber, category) {

            //for other note category blocks
            if (category.CategoryElementId == 1 || category.CategoryElementId === 'EditNoteCategory[' + blockNumber + ']') {

                return true;
            }
            return false;
        },

        //Sends site M review notification email to Reviewer
        onReviewSubmit() {

            let self = this;
            var currentUrl = window.location.href.split("/");
            let postUrl = $("#SubmitForReview").data("review-submit-url");
            $("#UpdateSiteLoader").removeClass('hidden').find(".loader-text").html("Sending Review Notification");
            $.ajax({
                url: postUrl,
                data: {"sitemeasurementid": currentUrl.reverse()[1]},
                type: 'POST',
                dataType: 'json'
            })
            .done(function (response) {

                if(response.status === "success") {
                    
                    //On success hide form and show success message
                    $(".edit-sitepage").empty();
                    $(".datanotfound-legend").addClass("hidden");
                    self.populateNotifications(response, "ReviewNotificationArea", true);
                }
                //On error show error message
                self.populateNotifications({
                    status: "error",
                    message: response.message + ` (One of the reason may be User has not been assigned to project)`
                });
            })
            .fail(function (jqXHR) {

                if (jqXHR.status === 403) {

                    self.populateNotifications({
                        status: "error",
                        message: "Access denied!"
                    });
                } else {

                    self.populateNotifications({
                        status: "error",
                        message: AlertData["10077"]
                    });
                }
            })
            .always(function () {

                $("#UpdateSiteLoader").addClass('hidden');
                $("#SubmitForReview").trigger('blur');    
            });
        },

        //Sends site M approval notification email to Approver
        onApproveReview() {
            
            let self = this;
            var currentUrl = window.location.href.split("/");
            let postUrl = $("#SubmitForApproval").data("approve-sitem-url");
            $("#UpdateSiteLoader").removeClass('hidden').find(".loader-text").html("Sending Approval Notification");
            $.ajax({

                url: postUrl,
                data: {"sitemeasurementid": currentUrl.reverse()[1]},
                type: 'POST',
                dataType: 'json'
            })
            .done(function (response) {

                if(response.status === "success") {
                    
                    //On success hide form and show success message
                    $(".edit-sitepage").empty();
                    $(".datanotfound-legend").addClass("hidden");
                    self.populateNotifications(response, "ReviewNotificationArea", true);
                }
                //On error show error message
                self.populateNotifications({
                    status: "error",
                    message: response.message + ` (One of the reason may be User has not been assigned to project)`
                });
            })
            .fail(function (jqXHR) {

                if (jqXHR.status === 403) {

                    self.populateNotifications({
                        status: "error",
                        message: "Access denied!"
                    });
                } else {

                    self.populateNotifications({
                        status: "error",
                        message: AlertData["10077"]
                    });
                }
            })
            .always(function () {

                $("#UpdateSiteLoader").addClass('hidden');
                $("#SubmitForApproval").trigger('blur');    
            });
        },

        //Sends review reject notification email to Supervisor
        onRejectReview() {

            let self = this;
            var currentUrl = window.location.href.split("/");
            let postUrl = $("#RejectReview").data("review-reject-url");
            $("#UpdateSiteLoader").removeClass('hidden').find(".loader-text").html("Sending Reject Notification");
            $.ajax({

                url: postUrl,
                data: {"sitemeasurementid": currentUrl.reverse()[1]},
                type: 'POST',
                dataType: 'json'
            })
            .done(function (response) {
                
                if(response.status === "success") {
                    
                    //On success hide form and show success message
                    $(".edit-sitepage").empty();
                    $(".datanotfound-legend").addClass("hidden");
                    self.populateNotifications(response, "ReviewNotificationArea", true); 
                }
                //On error show error message
                self.populateNotifications({
                    status: "error",
                    message: response.message + ` (One of the reason may be User has not been assigned to project)`
                }); 
            })
            .fail(function (jqXHR) {

                if (jqXHR.status === 403) {

                    self.populateNotifications({
                        status: "error",
                        message: "Access denied!"
                    });
                } else {

                    self.populateNotifications({
                        status: "error",
                        message: AlertData["10077"]
                    });
                }
            })
            .always(function () {

                $("#UpdateSiteLoader").addClass('hidden');
                $("#RejectReview").trigger('blur');    
            });
        },

        //Sends site M approval reject notification email to Reviewer
        onApprovalAccept() {
            
            let self = this;
            var currentUrl = window.location.href.split("/");
            let postUrl = $("#AcceptApproval").data("approval-accept-url");
            $("#UpdateSiteLoader").removeClass('hidden').find(".loader-text").html("Sending Approval Notification");
            $.ajax({

                url: postUrl,
                data: {"sitemeasurementid": currentUrl.reverse()[1]},
                type: 'POST',
                dataType: 'json'
            })
            .done(function (response) {

                if(response.status === "success") {
                    
                    //On success hide form and show success message
                    $(".edit-sitepage").empty();
                    $(".datanotfound-legend").addClass("hidden");
                    self.populateNotifications(response, "ReviewNotificationArea", true);
                }
                //On error show error message
                self.populateNotifications({
                    status: "error",
                    message: response.message + ` (One of the reason may be User has not been assigned to project)`
                });
            })
            .fail(function (jqXHR) {

                if (jqXHR.status === 403) {

                    self.populateNotifications({
                        status: "error",
                        message: "Access denied!"
                    });
                } else {

                    self.populateNotifications({
                        status: "error",
                        message: AlertData["10077"]
                    });
                }
            })
            .always(function () {

                $("#UpdateSiteLoader").addClass('hidden');
                $("#AcceptApproval").trigger('blur');    
            });
        },

        //Sends site M approval accept notification email to Reviewer and Superviser
        onApprovalReject() {
            
            let self = this;
            var currentUrl = window.location.href.split("/");
            let postUrl = $("#RejectApproval").data("approval-reject-url");
            $("#UpdateSiteLoader").removeClass('hidden').find(".loader-text").html("Sending Reject Notification");
            $.ajax({

                url: postUrl,
                data: {"sitemeasurementid": currentUrl.reverse()[1]},
                type: 'POST',
                dataType: 'json'
            })
            .done(function (response) {

               if(response.status === "success") {
                    
                    //On success hide form and show success message
                    $(".edit-sitepage").empty();
                    $(".datanotfound-legend").addClass("hidden");
                    self.populateNotifications(response, "ReviewNotificationArea", true);
                }
                //On error show error message
                self.populateNotifications({
                    status: "error",
                    message: response.message + ` (One of the reason may be User has not been assigned to project)`
                });
            })
            .fail(function (jqXHR) {

                if (jqXHR.status === 403) {

                    self.populateNotifications({
                        status: "error",
                        message: "Access denied!"
                    });
                } else {

                    self.populateNotifications({
                        status: "error",
                        message: AlertData["10077"]
                    });
                }
            })
            .always(function () {

                $("#UpdateSiteLoader").addClass('hidden');
                $("#RejectApproval").trigger('blur');    
            });
        },
        
        //Initialize gallery popup when user clicks on room attachment
        initializeRoomThumbnailsPopup(roomThumbnails) {

            jquery(".attachment-gallery").magnificPopup({
                items: roomThumbnails,
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
                        // extend function that moves to next item
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
                        // extend function that moves back to prev item
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
                        $('.tooltip').css('display', 'none');
                    }
                }
            });
        },
       
        //Opens Note Category pop up
        openNotesPopup(roomNo) {
            
            $("#ViewNotesModal-"+roomNo).modal({
                show: true
            });
        }, 
        
        //Populate Backend validation errors of the form
        populateFormErrors(errors, formValidator) {
        
            for (let elementName in errors) {

                let errorObject = {},
                    previousValue = $("#" + elementName).data("previousValue") ? $("#" + elementName).data("previousValue") : {};
                previousValue.valid = false;

                previousValue.message = errors[elementName][0];

                $("#" + elementName).data("previousValue", previousValue);

                if (elementName.match("UploadFiles")) {

                    this.showSiteAttachmentsBlock = false;

                    for (let i = this.newSiteAttachments.length - 1; i >= 0; i--) {

                        if (this.isFileExistsInArray(this.newSiteAttachments[i], this.totalSiteAttachments)) {

                            this.removeAttachmentFromNewAttachmentsArray(this.newSiteAttachments[i], this.totalSiteAttachments);
                        }
                    }

                    this.newSiteAttachments = [];

                    this.clearFileUpload(elementName);

                    errorObject[elementName + "[]"] = errors[elementName][0];
                } else if (elementName.match("EditChecklistCopy")) {

                    this.shouldRenderChecklistCopies = false;

                    for (let i = this.newChecklistCopies.length - 1; i >= 0; i--) {

                        if (this.isFileExistsInArray(this.newChecklistCopies[i], this.totalChecklistCopies)) {

                            this.removeAttachmentFromNewAttachmentsArray(this.newChecklistCopies[i], this.totalChecklistCopies);
                        }
                    }

                    this.newChecklistCopies = [];

                    this.clearFileUpload(elementName);

                    errorObject[elementName + "[]"] = errors[elementName][0];
                } else if (elementName.match("EditSMCopy")) {

                     this.shouldRenderScannedCopies = false;

                    for (let i = this.newScannedCopies.length - 1; i >= 0; i--) {

                        if (this.isFileExistsInArray(this.newScannedCopies[i], this.totalScannedCopies)) {

                            this.removeAttachmentFromNewAttachmentsArray(this.newScannedCopies[i], this.totalScannedCopies);
                        }
                    }

                    this.newScannedCopies = [];

                    this.clearFileUpload(elementName);

                    errorObject[elementName + "[]"] = errors[elementName][0];
                } else {

                    errorObject[elementName] = errors[elementName][0];
                }
                formValidator.showErrors(errorObject);
            }
        },
        
        //Populate Backend validation errors of the form.
        populateRoomFormErrors(errors, formValidator) {
            
            for (let elementName in errors) {

                let errorObject = {},
                    previousValue = $("#" + elementName).data("previousValue") ? $("#" + elementName).data("previousValue") : {};
                previousValue.valid = false;

                previousValue.message = errors[elementName][0];

                $("#" + elementName).data("previousValue", previousValue);

                if (elementName.match("UploadNoteAttachmnts_") || elementName.match("EditUploadNoteAttachmnt_") || elementName.match("FireSpAttachment_") || elementName.match("EditFireSpAttachment_") || elementName === "OurDoorUnitAttachment" || elementName === "EditOurDoorUnitAttachment") {

                    errorObject[elementName + "[]"] = errors[elementName][0];
                } else if (elementName.match("UploadRoomPics")) {

                    this.showRoomsAttachmentsBlock = false;

                    this.totalRoomAttachments = [];

                    this.clearFileUpload(elementName);

                    errorObject[elementName + "[]"] = errors[elementName][0]; 
                } else if (elementName.match("UploadEditRoomPics")) {

                    this.showEditRoomAttachmentsBlock = false;

                    for (let i = this.newEditRoomAttachments.length - 1; i >= 0; i--) {

                        if (this.isFileExistsInArray(this.newEditRoomAttachments[i], this.totalEditRoomAttachments)) {

                            this.removeAttachmentFromNewAttachmentsArray(this.newEditRoomAttachments[i], this.totalEditRoomAttachments);
                        }
                    }

                    this.newEditRoomAttachments = [];

                    this.clearFileUpload(elementName);

                    errorObject[elementName + "[]"] = errors[elementName][0];
                } else if (elementName === "WallDirectionAttachments") {

                    this.ShowAcAttachmentsBlock = false;

                    this.TotalAcAttachments = [];

                    this.clearFileUpload(elementName);

                    errorObject[elementName + "[]"] = errors[elementName][0]; 
                } else if (elementName === "EditWallDirectionAttachments") {

                    this.ShowEditAcAttachmentsBlock = false;

                    for (let i = this.NewEditAcAttachments.length - 1; i >= 0; i--) {

                        if (this.isFileExistsInArray(this.NewEditAcAttachments[i], this.TotalEditAcAttachments)) {

                            this.removeAttachmentFromNewAttachmentsArray(this.NewEditAcAttachments[i], this.TotalEditAcAttachments);
                        }
                    }

                    this.NewEditAcAttachments = [];

                    this.clearFileUpload(elementName);

                    errorObject[elementName + "[]"] = errors[elementName][0];
                } else if (elementName.match("FireSpLocDir") || elementName.match("FireSpPFC") || elementName.match("FireSpPFL") || elementName.match("EditOurDoorUnitAttachment") || elementName.match("FireSpAttachment_")) {
                    
                    let splittedElem = elementName.split(".");
                    errorObject[splittedElem[0] + "[" + splittedElem[1] + "]"] = errors[elementName][0];
                } else {

                    errorObject[elementName] = errors[elementName][0];
                }
                formValidator.showErrors(errorObject);
            }
        },
            
        //Populates notifications of the form.
        populateNotifications(response, notificationAreaId = "NotificationArea", addMessageTimeout= false) {
            
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

                alertDiv.removeAttr('class').removeAttr('style').addClass('alert alert-dismissible alert-danger').html('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + response.message);
            }
            if (notificationTimeoutID) {

                clearTimeout(notificationTimeoutID);
            }
            if(addMessageTimeout === false) {

                notificationTimeoutID = setTimeout(this.clearNotificationMessage.bind(notificationAreaId, notificationAreaId), notificationTimeout);
            }
        },
        
        //Clears notifications of the form.
        clearNotificationMessage(notificationAreaId) {
            
            $("#" + notificationAreaId).children(".alert").fadeOut("slow", function () {

                $(this).addClass('hidden');
            });
        },
        
        //Projects Select2 Search... 
        initializeProjectsSelect2() {

            let self = this;
            jquery('#Projects').select2({
                placeholder: "Select a Project",
                language: {
                    noResults: function () {
                        return "No projects found";
                    }
                }
            }).on("change", function (e) {
                self.onProjectSelect(this, this.value, e);
            }).next("span.select2").css({
                display: 'block',
                width: '100%'
            });
        },

        //On Projects Select             
        onProjectSelect(projectRef, projectId, event) {    

            event.preventDefault();
            let self = this;
            if (projectId.length > 0) {

                jquery("#EditSiteMFormOverlay").removeClass('hidden').find(".loader-text").html("Fetching Site Info");

                self.fetchSiteInfo(projectId);

                $(projectRef).valid();

                $(projectRef).next('span.select2').find(".select2-selection").removeClass("select2-selection-error").closest('.form-group').removeClass('has-error');
            } else {

                $("#siteInfo").addClass("hidden");
            }
        },

        //Gets Site Information
        fetchSiteInfo(projectId) {
            
            let config = { headers: {'Data-Type': 'json'} };
            let self = this;
            axios.get('/getsiteinfo/' + projectId, config)
            .then(function (response) {

                $("#siteInfo").removeClass("hidden");
                self.enquiryProject = response.data.data.user;
                $("#SiteDetails").html(response.data.data.siteInfo);
            })
            .catch(function (error) {

                self.populateNotifications({
                    status: "error",
                    message: "It seems enquiry is not exists for selected project."
                });
            })
            .then(() => {

                jquery("#EditSiteMFormOverlay").addClass("hidden");
            });
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
            } else if (response.status === 'warning') {
                this.NotificationIcon = "warning";
            } else if (response.status === 'info') {
                this.NotificationIcon = "info";
            }
        },

        //Clears Success/Failure messages 
        clearOverLayMessage() {
            this.FormOverLay = true;
            this.NotificationMessage = "";
            this.NotificationIcon = "";
        },
        
        //Clears Ac Form
        clearAddAcForm() {

            addAcFormValidator.resetForm();
            $("#AddAcModal input,select,textarea").not(":input[type=submit],:checkbox,select[id='Projects']").val("");
            $('#AddAcModal .form-group').find('i').removeClass('text-danger');
            this.TotalAcAttachments = [];
            this.IsPowerPointAvailable = this.IsDrainagePointAvailable = this.IsCoreCuttingAvailable = this.IsCopperCuttingAvailable = false;
            //Clear all upload type elements
            var uploadElement = document.querySelectorAll(`input[type="file"]`);
            for (let file = 0; file < uploadElement.length; file++) {

                uploadElement[file].value = '';
            }
        },
        
        //Get Ac delete confirmation from user
        deleteAcConfirmation(deleteAcClickEvent) {

            this.RoomMeasurementId = deleteAcClickEvent.target.id;
            $("#DeleteAcModal").modal({show:true});
        },

        //Issue Ac delete request
        deleteAc() {

            $("#DeleteAcFormOverlay").removeClass('hidden');
            var currentUrl = window.location.href.split("/");
            let siteId = currentUrl.reverse()[1];
            let self = this;
            //Make ajax request to delete Ac
            $.ajax({
                url: $("#DeleteAcModal").data("deleteac-url") + '/' + siteId + '/' + this.RoomMeasurementId,
                type: 'GET',
                dataType: 'json'
            })
            .done(function (response) {

                if (response.status === "success") {

                    //Find a measurement & make it's Ac specification null/empty
                    let measurement = (_.find(ViewModelInstance.roomsViewData, ["id", ViewModelInstance.RoomMeasurementId]));

                    measurement.acspecifications = "";
                    
                    $("#DeleteAcModal").modal("hide");
                }  

            }.bind(this))
            .fail(function () {

                self.populateNotifications({
                    status: "error",
                    message: AlertData["10077"]
                }, "DeleteAcNotificationArea");

                console.log("error");
            })
            .always(function () {

                $("#DeleteAcFormOverlay").addClass('hidden');
                $("#DeleteAcSubmit").trigger("blur");
            });
        },
        
        //Clear Add Fire Sp form
        clearAddFireSpForm() {

            addFireSpFormValidator.resetForm();
            $("#AddFireSpModal input,select").not(":input[type=submit],:checkbox,select[id='Projects']").val("");
            this.FireSprinklers = [];
            //Clear all upload type elements
            var uploadElement = document.querySelectorAll(`input[type="file"]`);
            for (let file = 0; file < uploadElement.length; file++) {

                uploadElement[file].value = '';
            }
        },
        
        //Get Fire Sprinklers delete confirmation from user
        deleteFireSpConfirmation(deleteFireSpClickEvent) {

            this.RoomMeasurementId = deleteFireSpClickEvent.target.id;
            $("#DeleteFireSpModal").modal({show:true});
        },

        //Issue Fire Sprinklers delete request
        deleteFireSprinklers() {

            $("#DeleteFireSpFormOverlay").removeClass('hidden');
            var currentUrl = window.location.href.split("/");
            let siteId = currentUrl.reverse()[1];
            let self = this;
            //Make ajax request to delete Fire Sprinklers
            $.ajax({
                url: $("#DeleteFireSpModal").data("deletefiresp-url") + '/' + siteId + '/' + this.RoomMeasurementId,
                type: 'GET',
                dataType: 'json'
            })
            .done(function (response) {

                if (response.status === "success") {

                    //Find a measurement & make it's Ac specification null/empty
                    let measurement = (_.find(ViewModelInstance.roomsViewData, ["id", ViewModelInstance.RoomMeasurementId]));

                    measurement.firespspecifications = "";
                    
                    $("#DeleteFireSpModal").modal("hide");
                }
            }.bind(this))
            .fail(function () {

                self.populateNotifications({
                    status: "error",
                    message: AlertData["10077"]
                }, "DeleteFireSpNotificationArea");

                console.log("error");
            })
            .always(function () {

                $("#DeleteFireSpFormOverlay").addClass('hidden');
                $("#DeleteFireSpSubmit").trigger("blur");
            });
        },

        AddFSPAttachmentsHandler(key) {
            let inputField = $('input#FireSpAttachment_' + key);
            inputField.trigger("click");
            $(inputField).off().on('change', function (event) {
                let attachmentBTN = $('#AttachmentUploadBtn_' + key);
                let filesLength = $(this)[0].files.length;
                let tooltipTitle = " File Attached";
                if (filesLength > 0) {
                    if (filesLength > 1) {
                        tooltipTitle = " Files Attached";
                    }
                    attachmentBTN.tooltip('hide')
                        .attr('data-original-title', filesLength + tooltipTitle)
                        .tooltip('fixTitle');
                } else {
                    attachmentBTN.tooltip('hide')
                        .attr('data-original-title', 'Add Attachments')
                        .tooltip('fixTitle');
                }
            });
        },
        
        EditFSPAttachmentsHandler(key) {
            let inputField = $('input#EditFireSpAttachment_' + key);
            inputField.trigger("click");
            $(inputField).off().on('change', function (event) {
                let attachmentBTN = $('#EditAttachmentUploadBtn_' + key);
                let filesLength = $(this)[0].files.length;
                let tooltipTitle = " File Attached";
                if (filesLength > 0) {
                    if (filesLength > 1) {
                        tooltipTitle = " Files Attached";
                    }
                    attachmentBTN.tooltip('hide')
                        .attr('data-original-title', filesLength + tooltipTitle)
                        .tooltip('fixTitle');
                } else {
                    attachmentBTN.tooltip('hide')
                        .attr('data-original-title', 'Add Attachments')
                        .tooltip('fixTitle');
                }
            });
        }
    }
});
   
$(document).ready(function () {
    
    //Initialize room attachments Gallery    
    initializeAttachmentsGallery();   
    //Initialize room selector
    InitializeRoomSelector();
    //initialize form validator
    InitializeValidator();
    //initialize no. of windows/Doors/Furnitures input toggle
    initializeQntyToggle();
    //intialize form Create Room validator
    InitializeAddRoomValidator();
    //edit room specifications input boxes initialization
    editRoomParamInputBoxesInitialization(true);
    //intialize form Edit Room validator
    InitializeEditRoomValidator();
    //Windows/Doors inputs validations
    roomParamInputBoxesInitialization(true);
    InitializeAddAcValidator();
    InitializeEditAcValidator();
    InitializeAddFSpValidator();
    InitializeEditFSpValidator();
    InitializeNotesAttachmentsUpload("btn-attachmentupload", "fileupload", "AddAcModal", "AddAcForm");
    InitializeNotesAttachmentsUpload("btn-attachmentupload", "fileupload", "EditAcModal", "EditAcForm");
    
    //Add validation on change of Add Room note category
    $(document).on('change', '.note-category', function(e) {

        let currentElementId = e.currentTarget.id;
        let optionValue = $(this).val();
        if ($(this).val() !== '') {
            
            //Add Room Notes block element validation rules
            addRoomNotesElementValidationRules(true);
            //make CategoryElementId to 1 and again update it to current element id
            let elementIndex = _.findIndex(ViewModelInstance.noteCategories, function (item) {
                return item.CategoryElementId == currentElementId;
            });
            if (elementIndex !== -1) {

                ViewModelInstance.noteCategories[elementIndex].CategoryElementId = 1;
            }
            //update CategoryElementId to current(selectbox) element Id
            let categoryIndex = _.findIndex(ViewModelInstance.noteCategories, function (item) {     
                return item.Id == optionValue;
            });
            ViewModelInstance.noteCategories[categoryIndex].CategoryElementId = currentElementId;            
        } else {
            
            //update CategoryElementId to 1 if option has value 0(i.e first option select category)
            let elementIndex = _.findIndex(ViewModelInstance.noteCategories, function (item) {
                return item.CategoryElementId == currentElementId;
            });
            if (elementIndex !== -1) {
                
                ViewModelInstance.noteCategories[elementIndex].CategoryElementId = 1;
            }
        } 
    });
    
    //Add validation on change of Edit Room note category
    $(document).on('change', '.editroom-note-category', function(e) {
        
        let currentElementId = e.currentTarget.id;
        let optionValue = $(this).val();
        if (optionValue !== '') {
            
            //add validation to Notes Block element
            EditRoomNotesElementValidationRules();
            //update CategoryElementId to 1
            let elementIndex = _.findIndex(ViewModelInstance.editRoomNoteCategories, function (item) {

                return item.CategoryElementId == currentElementId;
            });
            if (elementIndex !== -1) {

                ViewModelInstance.editRoomNoteCategories[elementIndex].CategoryElementId = 1;
            }
            //update CategoryElementId to current(selectbox) element Id
            let categoryIndex = _.findIndex(ViewModelInstance.editRoomNoteCategories, function (item) {
                return item.Id == optionValue;
            });
            if (categoryIndex !== -1) {
                
                ViewModelInstance.editRoomNoteCategories[categoryIndex].CategoryElementId = currentElementId;
                //sort the array by Note Category Name
                _.sortBy(ViewModelInstance.editRoomNoteCategories, ["Name"]);
            }
            let Index = _.findIndex(ViewModelInstance.roomMeasurementNotes, function (item) {

                return item.CategoryElementId == currentElementId;
            });   
        } else {

            //update CategoryElementId to 1 if option has value 0(i.e first option select category)
            let elementIndex = _.findIndex(ViewModelInstance.editRoomNoteCategories, function (item) {
                return item.CategoryElementId == currentElementId;
            });
            if (elementIndex !== -1) {
                
                ViewModelInstance.editRoomNoteCategories[elementIndex].CategoryElementId = 1;
            }
        } 
    });
    
    //Clears Form fields Hide event of Add Room Modal
    $('#AddRoomModal').on('hidden.bs.modal', function (event) {

        ViewModelInstance.clearAddRoomForm();
    });
    
    //Clears Form fields Hide event of Add Room Modal
    $('#AddAcModal').on('hidden.bs.modal', function (event) {

        ViewModelInstance.clearAddAcForm();
    });
    
    $("#EditAcModal").on('hidden.bs.modal', function (event) {

        $("#EditAcNotificationArea").addClass("hidden");
        editAcFormValidator.resetForm();
        var uploadElement = document.querySelectorAll(`input[type="file"]`);
        for (let file = 0; file < uploadElement.length; file++) {

            uploadElement[file].value = '';
        }
        ViewModelInstance.TotalEditAcAttachments = [];
        ViewModelInstance.DeletedEditAcAttachments = [];
        ViewModelInstance.OldEditAcAttachments = [];
        ViewModelInstance.NewEditAcAttachments = [];
        (ViewModelInstance.EditAcData.PowerPoint.PFC) ? (ViewModelInstance.EditAcData.PowerPoint.IsAvailable = true) : ViewModelInstance.resetPowerPointValues();
        (ViewModelInstance.EditAcData.DrainagePoint.PFC) ? (ViewModelInstance.EditAcData.DrainagePoint.IsAvailable = true) : ViewModelInstance.resetDrainagePointtValues();
        (ViewModelInstance.EditAcData.CoreCutting.PFC) ? (ViewModelInstance.EditAcData.CoreCutting.IsAvailable = true) : ViewModelInstance.resetCoreCuttingValues();
        (ViewModelInstance.EditAcData.CopperCutting.PFC) ? (ViewModelInstance.EditAcData.CopperCutting.IsAvailable = true) : ViewModelInstance.resetCopperCuttingValues();
    });
    
    //Hides notification messages on Hide event
    $('#DeleteAcModal').on('hidden.bs.modal', function (event) {
        
       $("#DeleteAcNotificationArea").addClass("hidden");
    });
    
    //Clears Form fields Hide event of Add Fire Sp Modal
    $('#AddFireSpModal').on('hidden.bs.modal', function (event) {

        ViewModelInstance.clearAddFireSpForm();
    });
    
    $("#EditFireSpModal").on('hidden.bs.modal', function (event) {

        // Check for FSP empty inputs and clear it from FSP array
        $("#EditFireSpNotificationArea").addClass("hidden");
        
        editFireSpFormValidator.resetForm();

        //Update measurements data array
        let measurement = (_.find(ViewModelInstance.roomsViewData, ["id", ViewModelInstance.RoomMeasurementId]));

        let fireSprinkler= measurement.firespspecifications;

        measurement.firespspecifications = fireSprinkler.filter(function (fsp) {

            return ((fsp.PFC !== (undefined || null || '')) || (fsp.PFLW !== (undefined || null || '')) || (fsp.WallDirection !== (undefined || null || '')));
        });

        var uploadElement = document.querySelectorAll(`input[type="file"]`);
        
        for (let file = 0; file < uploadElement.length; file++) {

            uploadElement[file].value = '';
        }
    });
        
    $('#DeleteFireSpModal').on('hidden.bs.modal', function (event) {
        
       $("#DeleteFireSpNotificationArea").addClass("hidden");
    });
    
    //Hides notification messages on Hide event
    $('#DeleteRoomModal').on('hidden.bs.modal', function (event) {
        
       $("#DeleteRoomNotificationArea").addClass("hidden");
    });
    
    //Resets form fields on Edit Room Modal Hide event
    $('#EditRoomModal').on('hidden.bs.modal', function (event) {

        //Revert all elements to original values
        $("#EditRoomNotificationArea").addClass("hidden");
        
        $("#UploadEditRoomPics").val("");
        
        //Clear all notes uploads elements
        var notesUploadElements = document.querySelectorAll('input[id^="EditUploadNoteAttachmnt_"]');

        for (let note = 0; note < notesUploadElements.length; note++) {

            notesUploadElements[note].value = '';
        }
        
        ViewModelInstance.totalEditRoomAttachments = [];
        
        //Reset no of windows/doors, notes array to original
        currentRoomMeasurement.windows.windows = currentRoomMeasurement.windows.windows.filter(function (x) {

            return (x.name !== (undefined || null || ''));
        });
        
        currentRoomMeasurement.doors.doors = currentRoomMeasurement.doors.doors.filter(function (x) {

            return (x.name !== (undefined || null || ''));
        });
        
        currentRoomMeasurement.furnitures.furnitures = currentRoomMeasurement.furnitures.furnitures.filter(function (x) {

            return (x.name !== (undefined || null || ''));
        });
        
        ViewModelInstance.editRoomWindows = currentRoomMeasurement.windows.windows;
        
        ViewModelInstance.editRoomDoors = currentRoomMeasurement.doors.doors;
        
        ViewModelInstance.editRoomFurnitures = !_.isEmpty(currentRoomMeasurement.furnitures) ? currentRoomMeasurement.furnitures.furnitures : [];
        
        ViewModelInstance.editRoomNoteCategories = [];
       
        //Make note first row empty if modal closes without update
        if (currentRoomMeasurement.roomnotes[0].emptyNote === true) {

            currentRoomMeasurement.roomnotes[0].Id = currentRoomMeasurement.roomnotes[0].description = "";
        }
       
        var uploadElement = document.querySelectorAll(`input[type="file"]`);
        
        for (let file = 0; file < uploadElement.length; file++) {

            uploadElement[file].value = '';
        }
    });
});

/**
 * Function initializes AC Notes attachments Upload.
 * 
 * @return  No
 */
var InitializeNotesAttachmentsUpload = function (actionBtn, uploadControl, parentElement, formName) {
    
    $("#"+parentElement).off().on('click', '.'+actionBtn, function (event) {
        let inputField = $(this).closest('form#'+formName).find('input.'+uploadControl);
        initializeAttachementAction(inputField, actionBtn);
    });
};

/**
 * Initialize File upload input.
 *
 * @return void
 */
var initializeAttachementAction = function (inputField, actionBtn) {
    
    inputField.trigger("click");
    $(inputField).off().on('change', function (event) {
        let attachmentBTN = $(this).closest('form').find('.'+actionBtn);
        let filesLength = $(this)[0].files.length;
        let tooltipTitle = " File Attached";
        if (filesLength > 0) {
            if (filesLength > 1) {
                tooltipTitle = " Files Attached";
            }
            attachmentBTN.tooltip('hide')
                .attr('data-original-title', filesLength + tooltipTitle)
                .tooltip('fixTitle');
        } else {
            attachmentBTN.tooltip('hide')
                .attr('data-original-title', 'Add Attachments')
                .tooltip('fixTitle');
        }
    });
};

/**
 * Function initializes attachments gallery.
 * 
 * @return  No
 */
var initializeAttachmentsGallery = function () {

    ViewModelInstance.$nextTick(function () {
        
        jquery('.attachment-gallery').each(function () {
            
            jquery(this).magnificPopup({
                delegate: 'a',
                type: 'image',
                gallery: {
                    enabled: true
                }
            });
        });
    });
};

/**
 * Function intializes Validator.
 * 
 * @return  No
 */
var InitializeValidator = function () {
    
    editSiteMFormValidator = $("#EditSiteMForm").validate({
        
        ignore: [],
        onkeyup: function (element, event) {
            if (this.invalid.hasOwnProperty(element.name)) {
                $(element).valid();
            }
        },
        errorClass: "help-block text-danger",
        errorElement: "span",
        
        highlight: function (element, errorClass) {
            
           if (element.id === "Projects") {
               
                $(element).next('span.select2').find(".select2-selection").addClass("select2-selection-error").closest('.form-group').addClass('has-error');
            } else if ($(element).attr('id') === "UploadFiles" || $(element).attr('id') === "EditSMCopy" || $(element).attr('id') === "EditChecklistCopy") {
                
                $(element).parent('.form-group').find('i').addClass('text-danger');        
            } else {
                
                $(element).closest('.form-group').addClass("has-error");
            }
        },
        
        unhighlight: function (element, errorClass) {
            
            if (element.id === "Projects") {
                
                $(element).next('span.select2').find(".select2-selection").removeClass("select2-selection-error").closest('.form-group').removeClass('has-error');
            } else if ($(element).attr('id') === "UploadFiles" || $(element).attr('id') === "EditSMCopy" || $(element).attr('id') === "EditChecklistCopy") {

                 $(element).parent('.form-group').find('i').removeClass('text-danger');
            } else {
                
                $(element).closest('.form-group').removeClass("has-error");
            }
        },
        
        errorPlacement: function (error, element) {
            
            if (element.id === "Projects") {
                
                error.insertAfter($(element).next("span.select2")); 
            } else {
                
                error.appendTo($(element).parent());
            }
        },
        
        rules: {
            
            Projects: {
                required: true
            },
            Description: {
                CheckConsecutiveSpaces: true,
                maxlength: 255
            },
            "UploadFiles[]": {
                required: function () {
                    if (ViewModelInstance.totalSiteAttachments.length === 0) {

                        return true;

                    } else {

                        return false;

                    }
                },
                checkMultipleVideoImageExtensions: true,
                checkMultipleFilesSize: true,
                checkPerFileSizeInMultipleFiles: true
            },
            "EditSMCopy[]": {
                required: function () {
                    if (ViewModelInstance.totalScannedCopies.length === 0) {

                        return true;

                    } else {

                        return false;

                    }
                },
                checkMultipleFilesExtensions: true,
                checkMultipleFilesSize: true, 
                checkPerFileSizeInMultipleFiles: true
            },
            "EditChecklistCopy[]": {
                required: function () {
                    if (ViewModelInstance.totalChecklistCopies.length === 0) {

                        return true;

                    } else {

                        return false;

                    }
                }, 
                checkMultipleFilesExtensions: true,
                checkMultipleFilesSize: true, 
                checkPerFileSizeInMultipleFiles: true
            }
        },
        
        messages: {
            
            Projects: {
                required: "Please select a Project."
            },     
            "UploadFiles[]": {
                required: "Please upload a file."
            },
            "EditSMCopy[]": {
                required: "Please upload a file."
            },
            "EditChecklistCopy[]": {
                required: "Please upload a file."
            }
        },
        
        submitHandler: function (form) {
            
            $("#EditSiteMFormOverlay").removeClass('hidden').find(".loader-text").html("Updating");
            
            $(".alert").not("#NoRoomFoundAlert").addClass('hidden');
            
            let formData = new FormData();
            
            //Append deleted site attachments if exists to form data
            if(ViewModelInstance.deletedSiteAttachments.length >= 1) {
                
                formData.append("deletedfiles", JSON.stringify(ViewModelInstance.deletedSiteAttachments)); 
            } else {
                
                formData.append("deletedfiles", "No_files_deleted");
            }           
                        
            //Append newly uploaded site attachments to form data
            for (var i = 0; i < ViewModelInstance.newSiteAttachments.length; i++) {
                
                formData.append("Files_" + ViewModelInstance.newSiteAttachments[i].renamedFileName, ViewModelInstance.newSiteAttachments[i].fileObject);
            }
            
            //Append deleted SM Scanned Copies if exists to form data
            if(ViewModelInstance.deleteScannedCopies.length >= 1) {
                
                formData.append("deletedscannedfiles", JSON.stringify(ViewModelInstance.deleteScannedCopies)); 
            } else {
                
                formData.append("deletedscannedfiles", "No_files_deleted");
            }
            
            //Append newly uploaded SM Scanned Copies to form data
            for (var i = 0; i < ViewModelInstance.newScannedCopies.length; i++) {
                
                formData.append("SMScannnedFiles_" + ViewModelInstance.newScannedCopies[i].renamedFileName, ViewModelInstance.newScannedCopies[i].fileObject);
            }
            
            //Append deleted SM Checklist Copies if exists to form data
            if(ViewModelInstance.deleteChecklistCopies.length >= 1) {
                
                formData.append("deletedchecklistfiles", JSON.stringify(ViewModelInstance.deleteChecklistCopies)); 
            } else {
                
                formData.append("deletedchecklistfiles", "No_files_deleted");
            }
            
            //Append newly uploaded SM Checklist Copies to form data
            for (var i = 0; i < ViewModelInstance.newChecklistCopies.length; i++) {
                
                formData.append("SMChecklistFiles_" + ViewModelInstance.newChecklistCopies[i].renamedFileName, ViewModelInstance.newChecklistCopies[i].fileObject);
            }
            
            formData.append("Projects", $("#Projects").val());
            
            formData.append("Description", $("#Description").val());
            
            $.ajax({
                
                url: window.location.href,
                type: 'POST',
                dataType: 'json',
                contentType: false,
                processData: false,
                data: formData
            })
           .done(function (response) {
               
               //Check response status
               if(response.status !== "success") {
                    
                    ViewModelInstance.populateOverlayMessage(response);
                    return;
                }
                //Show success message
                ViewModelInstance.populateOverlayMessage(response);
                //Reset Vue variables as per updated data
                //Site Photo variables
                ViewModelInstance.totalSiteAttachments = [];
                ViewModelInstance.newSiteAttachments = [];
                ViewModelInstance.deletedSiteAttachments = [];
                //SM Scanned Copy variables
                ViewModelInstance.totalScannedCopies = [];
                ViewModelInstance.newScannedCopies = [];
                ViewModelInstance.deleteScannedCopies = []; 
                //SM Checklist Copy variables 
                ViewModelInstance.totalChecklistCopies = [];
                ViewModelInstance.newChecklistCopies = [];
                ViewModelInstance.deleteChecklistCopies = [];
                //Update with latest data
                ViewModelInstance.oldSiteAttachments = response.files.SMPhotos;
                ViewModelInstance.oldScannedCopies = response.files.SMScannedCopies;
                ViewModelInstance.oldChecklistCopies = response.files.SMChecklistCopies;
                //Update tracking variables
                ViewModelInstance.assignExistingFilestoVueVariable(ViewModelInstance.totalSiteAttachments, ViewModelInstance.oldSiteAttachments, "SitePhotos");
                ViewModelInstance.assignExistingFilestoVueVariable(ViewModelInstance.totalScannedCopies, ViewModelInstance.oldScannedCopies, "ScannedCopy");
                ViewModelInstance.assignExistingFilestoVueVariable(ViewModelInstance.totalChecklistCopies, ViewModelInstance.oldChecklistCopies, "ChecklistCopy");
            })
            .fail(function (jqXHR) {
                
                if (jqXHR.status === 422) {
                    
                    let response = JSON.parse(jqXHR.responseText);
                    ViewModelInstance.populateFormErrors(response.data.errors, editSiteMFormValidator);
                            
                } else if (jqXHR.status === 403) {
                    
                    ViewModelInstance.populateOverlayMessage({
                        status: "error",
                        message: "Access denied!"
                    });
                
                } else if (jqXHR.status === 413) {
                    
                    ViewModelInstance.populateOverlayMessage({
                        status: "warning",
                        message: "Total Max upload file size for above file upload is 10MB. Check files size and try again."
                    });
                            
                } else {
                    
                    ViewModelInstance.populateOverlayMessage({
                        status: "error",
                        message: AlertData["10077"]
                    });
                }
            })
            .always(function () {
                
                $("#EditSiteMFormOverlay").addClass('hidden');
                $("#EditSiteMSubmit").trigger('blur');
            });
        }
    });
};

//Function to initialize Room Select2 search
var InitializeRoomSelector = function () {
 
    jquery('#Room').select2({
        placeholder: "Select a Room",
        language: {
            noResults: function () {
                return "No rooms found";
            }
        }
    }).next("span.select2").css({
        display: 'block',
        width: '100%'
    });
    
    jquery("#Room").on('change', function (event) {

        if (jquery(this).val() !== "") {
            
            var selectedText = jquery("#Room option:selected").text();
           //Reset Create Room Form
            if (selectedText !== ViewModelInstance.selectedRoomArea) {
                ViewModelInstance.defaultWindowQuantity = 1;
                ViewModelInstance.defaultDoorQuantity = 1;
                ViewModelInstance.windowQuantity = 1;
                ViewModelInstance.doorQuantity = 1;
                ViewModelInstance.defaultFurnitureQnty = 1;
                ViewModelInstance.totalSelectedFurnitures = 1;
                ViewModelInstance.totalRoomAttachments = [];
                ViewModelInstance.roomId = jquery(this).val();
                ViewModelInstance.selectedRoomArea = selectedText.trim();
                ViewModelInstance.isRoomBlockHide = false;
                ViewModelInstance.removeEditRoomNoteValidationRules();
                addRoomFormValidator.resetForm();
                jquery('form#AddRoomForm :input').not(':button, :submit, :reset, select#Room').val('').removeAttr('value').removeAttr('aria-required');
            }
        } else {
            //if no room available hide add room section view
            ViewModelInstance.isRoomBlockHide = true;
        }
    });
};

//Function to initialize Add/Edit Room form Windows, Doors and Furnitures on change event activites
var initializeQntyToggle = function () {
    
    //Add Room Measurment indows, Doors and Furnitures quantity toggle
    $("#WindowQnty").on('change', function () {
        
        if ($(this).val() > 0) {
            
            ViewModelInstance.windowQuantity = +parseInt($(this).val());
            roomParamInputBoxesInitialization();
        } else {
            
            ViewModelInstance.windowQuantity = 0;
        }
    });

    $("#DoorQnty").on('change', function () {
        
        if ($(this).val() > 0) {

            ViewModelInstance.doorQuantity = +parseInt($(this).val());
            roomParamInputBoxesInitialization();
        } else {
            
            ViewModelInstance.doorQuantity = 0;
        }
       
    });
    
    $("#FurntQnty").on('change', function () {

        if ($(this).val() > 0) {

            ViewModelInstance.totalSelectedFurnitures = +parseInt($(this).val());
            roomParamInputBoxesInitialization(true);
        } else {

            ViewModelInstance.totalSelectedFurnitures = 0;
        }
    });
  
    //Edit Room Measurment indows, Doors and Furnitures quantity toggle
    $("#EditWindowQuantitySelect").on("change", function (event) {
        
        event.preventDefault();
        if (ViewModelInstance.windowSelected > ViewModelInstance.editRoomWindows.length) {

            if (oldWindowObjArray.length > ViewModelInstance.editRoomWindows.length) {

                ViewModelInstance.editRoomWindows = oldWindowObjArray;
                var latestQuantity = ViewModelInstance.windowSelected - ViewModelInstance.editRoomWindows.length;
                for (let obj = 0; obj < latestQuantity; obj++) {

                    ViewModelInstance.editRoomWindows.push({"name": ''});
                }
            } else {

                var latestQuantity = ViewModelInstance.windowSelected - ViewModelInstance.editRoomWindows.length;
                for (let obj = 0; obj < latestQuantity; obj++) {

                    ViewModelInstance.editRoomWindows.push({"name": ''});
                }
            }

        } else if (ViewModelInstance.windowSelected < ViewModelInstance.editRoomWindows.length) {

            var latestQuantity = ViewModelInstance.editRoomWindows.length - ViewModelInstance.windowSelected;
            for (let obj = 0; obj < latestQuantity; obj++) {

                ViewModelInstance.editRoomWindows.pop();
            }

        }
        editRoomParamInputBoxesInitialization();
    });
     
    $("#EditDoorsQuantitySelect").on("change", function (event) {
        
        event.preventDefault();
        if (ViewModelInstance.doorSelected > ViewModelInstance.editRoomDoors.length) {
            
            if (oldDoorsObjArray.length > ViewModelInstance.editRoomDoors.length) {

                ViewModelInstance.editRoomDoors = oldDoorsObjArray;
                var latestQuantity = ViewModelInstance.doorSelected - ViewModelInstance.editRoomDoors.length;
                for (let obj = 0; obj < latestQuantity; obj++) {

                    ViewModelInstance.editRoomDoors.push({"name": ''});
                }
            } else {

                let latestQuantity = ViewModelInstance.doorSelected - ViewModelInstance.editRoomDoors.length;
                for (let obj = 0; obj < latestQuantity; obj++) {

                    ViewModelInstance.editRoomDoors.push({"name": ''});
                }
            }

        } else if (ViewModelInstance.doorSelected < ViewModelInstance.editRoomDoors.length) {

            var latestQuantity = ViewModelInstance.editRoomDoors.length - ViewModelInstance.doorSelected;
            for (let obj = 0; obj < latestQuantity; obj++) {

                ViewModelInstance.editRoomDoors.pop();
            }

        }
        editRoomParamInputBoxesInitialization();
    });
    
    $("#EditFurnituresQuantitySelect").on("change", function (event) {
        
        event.preventDefault();
        if (ViewModelInstance.furnituresSelected > ViewModelInstance.editRoomFurnitures.length) {

            if (oldFurnituresObjArray.length > ViewModelInstance.editRoomFurnitures.length) {

                ViewModelInstance.editRoomFurnitures = oldFurnituresObjArray;
                var latestQuantity = ViewModelInstance.furnituresSelected - ViewModelInstance.editRoomFurnitures.length;
                for (let obj = 0; obj < latestQuantity; obj++) {

                    ViewModelInstance.editRoomFurnitures.push({"name": ''});
                }
            } else {

                var latestQuantity = ViewModelInstance.furnituresSelected - ViewModelInstance.editRoomFurnitures.length;
                for (let obj = 0; obj < latestQuantity; obj++) {

                    ViewModelInstance.editRoomFurnitures.push({"name": ''});
                }
            }

        } else if (ViewModelInstance.furnituresSelected < ViewModelInstance.editRoomFurnitures.length) {

            var latestQuantity = ViewModelInstance.editRoomFurnitures.length - ViewModelInstance.furnituresSelected;
            for (let obj = 0; obj < latestQuantity; obj++) {

                ViewModelInstance.editRoomFurnitures.pop();
            }

        }
        editRoomParamInputBoxesInitialization(true);
    });
};

/**
 * Function intializes Create Room Validator.
 * 
 * @return  No
 */
var InitializeAddRoomValidator = function () {
    
   addRoomFormValidator = $("#AddRoomForm").validate({
        ignore: [],
        onkeyup: function (element, event) {
            if (this.invalid.hasOwnProperty(element.name)) {
                $(element).valid();
            }
        },
        errorClass: "help-block text-danger",
        errorElement: "span",
        
        highlight: function (element, errorClass) {
            
            if (element.id === "Room") {
               
                $(element).next('span.select2').find(".select2-selection").addClass("select2-selection-error").closest('.form-group').addClass('has-error');
            } else if ($(element).attr('id') === "UploadRoomPics") {

                $(element).parent('.form-group').find('i').addClass('text-danger'); 
            } else {
                
                $(element).parent().addClass("has-error");
            }
        },
        
        unhighlight: function (element, errorClass) {
            
            if (element.id === "Room") {
               
                $(element).next('span.select2').find(".select2-selection").removeClass("select2-selection-error").closest('.form-group').removeClass('has-error');
            } else if ($(element).attr('id') === "UploadRoomPics") {

                $(element).parent('.form-group').find('i').removeClass('text-danger'); 
            } else {
                
                $(element).parent().removeClass("has-error");
            }
        },
        
        errorPlacement: function (error, element) {
            
            if ($(element).attr('id') === "UploadRoomPics" || $(element).attr('id') === "NoteCategory" || $(element).attr('id').match("UploadNoteAttachmnts_") || $(element).attr('id') === "OutDoorUnitLocation" || $(element).attr('id') === "OurDoorUnitAttachment") {

                error.appendTo($(element).parent());
            } else if (element.id === "Room") {

                error.insertAfter($(element).next("span.select2"));
            } else {

                return false;
            }
        },
        
        rules: {
            
            Room: {
                required: true
            },
            RoomWidth: {
                required: true,
                number: true,
                min: 1,
                maxlength: 6
            },
            RoomHeight: {
                required: true,
                number: true,
                min: 1,
                maxlength: 6
            },
            RoomLength: {
                required: true,
                number: true,
                min:1,
                maxlength: 6
            },
            "UploadRoomPics[]": {
                CheckFileExtension: true,
                checkMultipleFilesSize: true,
                checkPerFileSizeInMultipleFiles: true
            },
            OutDoorUnitLocation: {
                CheckConsecutiveSpaces: true,
                maxlength: 255
            },
            "OurDoorUnitAttachment[]": {
                CheckFileExtension: true,
                checkMultipleFilesSize: true,
                checkPerFileSizeInMultipleFiles: true
            }
        },
        
        messages: {

            Room: {
                required: "Room Can't be balnk."
            },
            "UploadRoomPics[]": {
                required: "Please upload a file."
            }
        },
        
        submitHandler: function (form) {
            
            $("#AddRoomFormOverlay").removeClass('hidden');
            
            $(".alert").addClass('hidden');
            
            let roomFormData = new FormData(form);
            
            var currentUrl = window.location.href.split("/");
           
            if (ViewModelInstance.totalRoomAttachments.length > 0) {
                
                //Append newly uploaded room attachment to form data
                for (var i = 0; i < ViewModelInstance.totalRoomAttachments.length; i++) {

                    roomFormData.append("Files_" + ViewModelInstance.totalRoomAttachments[i].name, ViewModelInstance.totalRoomAttachments[i]);
                }
            }
            
            //Get room pictures count
            roomFormData.append("PicturesCount", ViewModelInstance.totalRoomAttachments.length); 
            
            //Append Current SM Id
            roomFormData.append("SiteMeasuementId", currentUrl.reverse()[1]); 
            
            //Get Room area id
            roomFormData.append("Room", ViewModelInstance.roomId); 
            
            $.ajax({
                
                url: form.action,
                type: 'POST',
                dataType: 'json',
                contentType: false,
                processData: false,
                data: roomFormData
            })
           .done(function (response) {
               
                if(response.code !== 200) {
                    
                   ViewModelInstance.populateNotifications(response, "AddRoomNotificationArea");
                   return;
                }
                //Hide the Pop up
                $("#AddRoomModal").modal("hide");
                
                ViewModelInstance.clearAddRoomForm();
                
                //add new room to vue roomdata array
                ViewModelInstance.roomsViewData.push(response.room);
                
                //intitalize attachment gallery
                initializeAttachmentsGallery();  
                
               //update room select2 on next vue js dom update
                ViewModelInstance.$nextTick(function () {
                    
                    $("#Room").val("").trigger("change");
                    //delete roomarea from other than QE room group array if exists
                    let Index = _.findIndex(ViewModelInstance.rooms, function (room) {

                        return room.Id === response.selectedroom;
                    });
                    if(Index !== -1) {
                        
                        ViewModelInstance.rooms.splice(Index, 1);
                    }
                   //delete roomarea from QE group array if exists
                    let roomAreaIndex = _.findIndex(ViewModelInstance.QERooms, function (room) {

                        return room.Id === response.selectedroom;
                    });
                    if(roomAreaIndex !== -1) {
                        
                        ViewModelInstance.QERooms.splice(roomAreaIndex, 1);
                    }
                    InitializeRoomSelector();
                }); 
            }.bind(this)).fail(function (jqXHR) {
                
                if (jqXHR.status === 422) {
                    
                    let response = JSON.parse(jqXHR.responseText);
                    ViewModelInstance.populateRoomFormErrors(response.data.errors, addRoomFormValidator);
                            
                } else if (jqXHR.status === 403) {
                    
                    ViewModelInstance.populateNotifications({
                        status: "error",
                        message: "Access denied!"
                    },"AddRoomNotificationArea");

                } else if (jqXHR.status === 413) {
                    
                    ViewModelInstance.populateNotifications({
                        status: "warning",
                        message: "Total Max upload file size for above file upload is 10MB. Check files size and try again."
                    },"AddRoomNotificationArea");
                            
                } else {
                    
                    ViewModelInstance.populateNotifications({
                        status: "error",
                        message: AlertData["10077"]
                    },"AddRoomNotificationArea");
                }
            })            
            .always(function () {
                
                $("#AddRoomFormOverlay").addClass('hidden');
                $("#AddRoomFormSubmit").trigger('blur');
            });
        }
    });
};

/**
 * Function initializes Add Ac form validator.
 * 
 * @return  No
 */
var InitializeAddAcValidator = function () {
    
   addAcFormValidator = $("#AddAcForm").validate({
        ignore: [],
        onkeyup: function (element, event) {
            if (this.invalid.hasOwnProperty(element.name)) {
                $(element).valid();
            }
        },
        errorClass: "help-block text-danger",
        errorElement: "span",
        
        highlight: function (element, errorClass) {
            
            if ($(element).attr('id') === "WallDirectionAttachments") {
                
                $(element).parent('.form-group').addClass("has-error").find('i').addClass('text-danger');  
            } else {
                
                $(element).parent().addClass("has-error");
            }
        },
        
        unhighlight: function (element, errorClass) {

            if ($(element).attr('id') === "WallDirectionAttachments") {

                $(element).parent('.form-group').removeClass("has-error").find('i').removeClass('text-danger');
            } else {
                
                $(element).parent().removeClass("has-error");
            }
        },
        
        errorPlacement: function (error, element) {
            
            error.appendTo($(element).parent());
        },
        
        rules: {
            
            WallDirection: {
                required: true
            },
            "WallDirectionAttachments[]": {
                required: function () {
                    if (ViewModelInstance.TotalAcAttachments.length < 1) {

                        return true;
                    } else {

                        return false;
                    }
                },
                checkMultipleFilesExtensions: true,
                checkMultipleFilesSize: true,
                checkPerFileSizeInMultipleFiles: true
            },
            OutDoorUnitLocation: {
                CheckConsecutiveSpaces: true,
                maxlength: 255
            },
            "OurDoorUnitAttachment[]": {
                CheckFileExtension: true,
                checkMultipleFilesSize: true,
                checkPerFileSizeInMultipleFiles: true
            }
        },
        
        messages: {

            WallDirection: {
                required: "Wall Direction can't be blank."
            },
            "WallDirectionAttachments[]": {
                required: "Please upload a file."
            }
        },
        
        submitHandler: function (form) {
            
            $("#AddAcFormOverlay").removeClass('hidden');
            
            $(".alert").addClass('hidden');
            
            let roomFormData = new FormData(form);
            
            var currentUrl = window.location.href.split("/");
            
            if (ViewModelInstance.TotalAcAttachments.length > 0) {
                
                //Append newly uploaded Ac attachment to form data
                for (var i = 0; i < ViewModelInstance.TotalAcAttachments.length; i++) {

                    roomFormData.append("Files_" + ViewModelInstance.TotalAcAttachments[i].name, ViewModelInstance.TotalAcAttachments[i]);
                }
            }
            
            //Get room pictures count
            roomFormData.append("AttachmentsCount", ViewModelInstance.TotalAcAttachments.length); 
            
            //Append Current SM Id
            roomFormData.append("SiteMeasuementId", currentUrl.reverse()[1]); 
            
            //Get Room Measurement id
            roomFormData.append("RoomMeasurementId", ViewModelInstance.RoomMeasurementId); 
            
            $.ajax({
                
                url: form.action,
                type: 'POST',
                dataType: 'json',
                contentType: false,
                processData: false,
                data: roomFormData
            })
           .done(function (response) {
               
                if(response.code !== 200) {
                   ViewModelInstance.populateNotifications(response, "AddAcNotificationArea");
                   return;
                }
                //Update roomdata array
                let measurement = (_.find(ViewModelInstance.roomsViewData, ["id", ViewModelInstance.RoomMeasurementId]));
                
                measurement.acspecifications = response.acspecifications;
                
                ViewModelInstance.toggleAcCheckboxFlag(ViewModelInstance.roomsViewData);
                
                //Hide the Pop up
                $("#AddAcModal").modal("hide");
                
                //Clear Ac form
                ViewModelInstance.clearAddAcForm();
            }.bind(this)).fail(function (jqXHR) {
                
                if (jqXHR.status === 422) {
                    
                    let response = JSON.parse(jqXHR.responseText);
                    ViewModelInstance.populateRoomFormErrors(response.data.errors, addAcFormValidator);
      
                } else if (jqXHR.status === 403) {
                    
                    ViewModelInstance.populateNotifications({
                        status: "error",
                        message: "Access denied!"
                    },"AddAcNotificationArea");

                } else if (jqXHR.status === 413) {
                    
                    ViewModelInstance.populateNotifications({
                        status: "warning",
                        message: "Total Max upload file size for above file upload is 10MB. Check files size and try again."
                    },"AddAcNotificationArea");
                            
                } else {
                    
                    ViewModelInstance.populateNotifications({
                        status: "error",
                        message: AlertData["10077"]
                    },"AddAcNotificationArea");
                }
            })            
            .always(function () {
                
                $("#AddAcFormOverlay").addClass('hidden');
                $("#AddAcFormSubmit").trigger('blur');
            });
        }
    });
};

/**
 * Function initializes Edit Ac form validator.
 * 
 * @return  No
 */
var InitializeEditAcValidator = function () {
    
   editAcFormValidator = $("#EditAcForm").validate({
        ignore: [],
        onkeyup: function (element, event) {
            if (this.invalid.hasOwnProperty(element.name)) {
                $(element).valid();
            }
        },
        errorClass: "help-block text-danger",
        errorElement: "span",
        
        highlight: function (element, errorClass) {
            
            if ($(element).attr('id') === "EditWallDirectionAttachments") {
                
                $(element).parent('.form-group').addClass("has-error").find('i').addClass('text-danger');  
            } else {
                
                $(element).parent().addClass("has-error");
            }
        },
        
        unhighlight: function (element, errorClass) {

            if ($(element).attr('id') === "EditWallDirectionAttachments") {

                $(element).parent('.form-group').removeClass("has-error").find('i').removeClass('text-danger');
            } else {
                
                $(element).parent().removeClass("has-error");
            }
        },
        
        errorPlacement: function (error, element) {
 
            error.appendTo($(element).parent());
        },
        
        rules: {
            
            EditWallDirection: {
                required: true
            },
            "EditWallDirectionAttachments[]": {
                required: function () {
                    if (ViewModelInstance.TotalEditAcAttachments.length < 1) {
                        
                        return true;
                    } else {

                        return false;
                    }
                },
                CheckFileExtension: true,
                checkMultipleFilesSize: true,
                checkPerFileSizeInMultipleFiles: true
            },
            EditOutDoorUnitLocation: {
                CheckConsecutiveSpaces: true,
                maxlength: 255
            },
            "EditOurDoorUnitAttachment[]": {
                CheckFileExtension: true,
                checkMultipleFilesSize: true,
                checkPerFileSizeInMultipleFiles: true
            }
        },
        
        messages: {

            EditWallDirection: {
                required: "Wall Direction can't be blank."
            },
            "EditWallDirectionAttachments[]": {
                required: "Please upload a file."
            }
        },
        
        submitHandler: function (form) {
            
            $("#EditAcFormOverlay").removeClass('hidden');
            
            $(".alert").addClass('hidden');
            
            let editAcFormData = new FormData(form);
            
           //store deleted files if exists
            if(ViewModelInstance.DeletedEditAcAttachments.length >= 1) {
                
                editAcFormData.append("deletedacfiles", JSON.stringify(ViewModelInstance.DeletedEditAcAttachments));
                
            } else {
                
                editAcFormData.append("deletedacfiles", "No_deleted_files");
            }
            
            //Get new uploaded files
            for (var i = 0; i < ViewModelInstance.NewEditAcAttachments.length; i++) {
                
                editAcFormData.append("Files_" + ViewModelInstance.NewEditAcAttachments[i].renamedFileName, ViewModelInstance.NewEditAcAttachments[i].fileObject);  
            }
            
            var currentUrl = window.location.href.split("/");

            //Append Current SM Id
            editAcFormData.append("SiteMeasuementId", currentUrl.reverse()[1]); 
            
            //Get Room Measurement id
            editAcFormData.append("RoomMeasurementId", ViewModelInstance.RoomMeasurementId); 
            
            $.ajax({
                
                url: form.action,
                type: 'POST',
                dataType: 'json',
                contentType: false,
                processData: false,
                data: editAcFormData
            })
           .done(function (response) {
               
                if(response.code !== 200) {
                   ViewModelInstance.populateNotifications(response, "EditAcNotificationArea");
                   return;
                }
                //Update roomdata array
                let measurement = (_.find(ViewModelInstance.roomsViewData, ["id", ViewModelInstance.RoomMeasurementId]));
                
                measurement.acspecifications = response.acspecifications;
                
                ViewModelInstance.toggleAcCheckboxFlag(ViewModelInstance.roomsViewData);
                
                //Hide the Pop up
                $("#EditAcModal").modal("hide");
            }.bind(this)).fail(function (jqXHR) {
                
                if (jqXHR.status === 422) {
                    
                    let response = JSON.parse(jqXHR.responseText);
                    ViewModelInstance.populateRoomFormErrors(response.data.errors, editAcFormValidator);
      
                } else if (jqXHR.status === 403) {
                    
                    ViewModelInstance.populateNotifications({
                        status: "error",
                        message: "Access denied!"
                    },"EditAcNotificationArea");

                } else if (jqXHR.status === 413) {
                    
                    ViewModelInstance.populateNotifications({
                        status: "warning",
                        message: "Total Max upload file size for above file upload is 10MB. Check files size and try again."
                    },"EditAcNotificationArea");
                            
                } else {
                    
                    ViewModelInstance.populateNotifications({
                        status: "error",
                        message: AlertData["10077"]
                    },"EditAcNotificationArea");
                }
            })            
            .always(function () {
                
                $("#EditAcFormOverlay").addClass('hidden');
                $("#EditAcFormSubmit").trigger('blur');
            });
        }
    });
};

/**
 * Function initializes Add Fire Sp form validator.
 * 
 * @return  No
 */
var InitializeAddFSpValidator = function () {
    
   addFireSpFormValidator = $("#AddFireSpForm").validate({
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
        
        submitHandler: function (form) {
            
            $("#AddFireSpFormOverlay").removeClass('hidden');
            
            $(".alert").addClass('hidden');
            
            let roomFormData = new FormData(form);
            
            var currentUrl = window.location.href.split("/");
             
            //Append Current SM Id
            roomFormData.append("SiteMeasuementId", currentUrl.reverse()[1]); 
            
            //Get Room Measurement id
            roomFormData.append("RoomMeasurementId", ViewModelInstance.RoomMeasurementId); 
            
            $.ajax({
                
                url: form.action,
                type: 'POST',
                dataType: 'json',
                contentType: false,
                processData: false,
                data: roomFormData
            })
           .done(function (response) {
               
                if(response.code !== 200) {
                   ViewModelInstance.populateNotifications(response, "AddFireSpNotificationArea");
                   return;
                }
                //Update Measurements array
                let measurement = (_.find(ViewModelInstance.roomsViewData, ["id", ViewModelInstance.RoomMeasurementId]));
                
                measurement.firespspecifications = response.firespspecifications;
  
                //Hide the Pop up
                $("#AddFireSpModal").modal("hide");
                
                //Clear the form
                ViewModelInstance.clearAddFireSpForm();
            }.bind(this))
            .fail(function (jqXHR) {
                
                if (jqXHR.status === 422) {
                    
                    let response = JSON.parse(jqXHR.responseText);
                    ViewModelInstance.populateRoomFormErrors(response.data.errors, addFireSpFormValidator);
      
                } else if (jqXHR.status === 403) {
                    
                    ViewModelInstance.populateNotifications({
                        status: "error",
                        message: "Access denied!"
                    },"AddFireSpNotificationArea");

                } else if (jqXHR.status === 413) {
                    
                    ViewModelInstance.populateNotifications({
                        status: "warning",
                        message: "Total Max upload file size for above file upload is 10MB. Check files size and try again."
                    },"AddFireSpNotificationArea");
                            
                } else {
                    
                    ViewModelInstance.populateNotifications({
                        status: "error",
                        message: AlertData["10077"]
                    },"AddFireSpNotificationArea");
                }
            })            
            .always(function () {
                
                $("#AddFireSpFormOverlay").addClass('hidden');
                $("#CreateFireSpSubmitBtn").trigger('blur');
            });
        }
    });
};

/**
 * Function initializes Edit Fire Sp form validator.
 * 
 * @return  No
 */
var InitializeEditFSpValidator = function () {
    
    editFireSpFormValidator = $("#EditFireSpForm").validate({
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
        
        submitHandler: function (form) {
            
            $("#EditFireSpFormOverlay").removeClass('hidden');
            
            $(".alert").addClass('hidden');
            
            let roomFormData = new FormData(form);
            
            var currentUrl = window.location.href.split("/");
             
            //Append Current SM Id
            roomFormData.append("SiteMeasuementId", currentUrl.reverse()[1]); 
            
            //Get Room Measurement id
            roomFormData.append("RoomMeasurementId", ViewModelInstance.RoomMeasurementId); 
                        
            roomFormData.append("TotalFireSprinklers", JSON.stringify(ViewModelInstance.EditPageFireSprinklers));
            
            $.ajax({
                
                url: form.action,
                type: 'POST',
                dataType: 'json',
                contentType: false,
                processData: false,
                data: roomFormData
            })
           .done(function (response) {
               
                if(response.code !== 200) {
                   ViewModelInstance.populateNotifications(response, "EditFireSpNotificationArea");
                   return;
                }
                //Update Measurements array
                let measurement = (_.find(ViewModelInstance.roomsViewData, ["id", ViewModelInstance.RoomMeasurementId]));
                
                measurement.firespspecifications = response.firespspecifications;
  
                //Hide the Pop up
                $("#EditFireSpModal").modal("hide");
            }.bind(this))
            .fail(function (jqXHR) {
                
                if (jqXHR.status === 422) {
                    
                    let response = JSON.parse(jqXHR.responseText);
                    ViewModelInstance.populateRoomFormErrors(response.data.errors, editFireSpFormValidator);
      
                } else if (jqXHR.status === 403) {
                    
                    ViewModelInstance.populateNotifications({
                        status: "error",
                        message: "Access denied!"
                    },"EditFireSpNotificationArea");

                } else if (jqXHR.status === 413) {
                    
                    ViewModelInstance.populateNotifications({
                        status: "warning",
                        message: "Total Max upload file size for above file upload is 10MB. Check files size and try again."
                    },"EditFireSpNotificationArea");
                            
                } else {
                    
                    ViewModelInstance.populateNotifications({
                        status: "error",
                        message: AlertData["10077"]
                    },"EditFireSpNotificationArea");
                }
            })            
            .always(function () {
                
                $("#EditFireSpFormOverlay").addClass('hidden');
                $("#EditFireSpSubmitBtn").trigger('blur');
            });
        }
    });
};

/**
 * Function intializes Edit Room Validator.
 * 
 * @return  No
 */
var InitializeEditRoomValidator = function () {
    
    editRoomFormValidator = $("#EditRoomForm").validate({
        ignore: [],
        onkeyup: function (element, event) {
            if (this.invalid.hasOwnProperty(element.name)) {
                $(element).valid();
            }
        },
        errorClass: "help-block text-danger",
        errorElement: "span",
        
        highlight: function (element, errorClass) {
            
            if ($(element).attr('id') === "UploadEditRoomPics") {

               $(element).parent('.form-group').find('i').addClass('text-danger'); 
            } else {

                $(element).parent().addClass("has-error");
            }
        },
        
        unhighlight: function (element, errorClass) {
            
            if ($(element).attr('id') === "UploadEditRoomPics") {

                $(element).parent('.form-group').find('i').removeClass('text-danger'); 
            } else {

                $(element).parent().removeClass("has-error");
            }
        },
        
        errorPlacement: function (error, element) {
            
            if ($(element).attr('id') === "UploadEditRoomPics" || $(element).attr('id').match("EditUploadNoteAttachmnt_")) {

                error.appendTo($(element).parent());
            }  else {

                return false;
            }
        },
        
        rules: {
            
            EditRoomWidth: {
                required: true,
                number:true,
                min: 1,
                maxlength: 6
            },
            EditRoomHeight: {
                required: true,
                min: 1,
                number:true,
                maxlength: 6
            },
            EditRoomLength: {
                required: true,
                number:true,
                min: 1,
                maxlength: 6
            },
            "UploadEditRoomPics[]": {
                checkMultipleFilesExtensions: true,
                checkMultipleFilesSize: true,
                checkPerFileSizeInMultipleFiles: true
            }
        },
        
        messages: {

            "UploadEditRoomPics[]": {
                
                required: "Please upload a file."
            }
        },
        
        submitHandler: function (form) {
            
            $("#EditRoomMFormOverlay").removeClass('hidden');
            
            $(".alert").addClass('hidden');
            
            let editRoomFormData = new FormData(form);
            
            var currentUrl = window.location.href.split("/");
            
           //store deleted files if exists
            if(ViewModelInstance.deletedEditRoomAttachments.length >= 1) {
                
                editRoomFormData.append("deletedroomfiles", JSON.stringify(ViewModelInstance.deletedEditRoomAttachments));
                
            } else {
                
                editRoomFormData.append("deletedroomfiles", "No_deleted_files");
            }
            //Get new uploaded files
            for (var i = 0; i < ViewModelInstance.newEditRoomAttachments.length; i++) {
                
                editRoomFormData.append("Files_" + ViewModelInstance.newEditRoomAttachments[i].renamedFileName, ViewModelInstance.newEditRoomAttachments[i].fileObject);  
            }

            let editRoomId = ViewModelInstance.editRoomId;
            
            //Post URL
            let postUrl = "/sitemeasurement/roommeasurement/"+ editRoomId  + "/edit";
            
            //Append Site Id to form data
            editRoomFormData.append("SiteMeasuementId", currentUrl.reverse()[1]);
            
            editRoomFormData.append("RoomAreaId", ViewModelInstance.roomSpecifications.roomId);

            $.ajax({
                
                url: postUrl,
                type: 'POST',
                dataType: 'json',
                contentType: false,
                processData: false,
                data: editRoomFormData
            })            
           .done(function (response) {
               
                if(response.status !== "success") {
                    
                   ViewModelInstance.populateNotifications(response, "EditRoomNotificationArea");
                   return;
                }
                
                //Reset Edit Room Vue variables
                ViewModelInstance.totalEditRoomAttachments = [];
                
                ViewModelInstance.newEditRoomAttachments = [];
                
                ViewModelInstance.deletedEditRoomAttachments = [];
                
                $("#UploadEditRoomPics").val("");
                
                this.oldEditRoomAttachments = response.roomAttachments;
                
                //Initialize tracking array with updated data
                ViewModelInstance.assignExistingFilestoVueVariable(ViewModelInstance.totalEditRoomAttachments, this.oldEditRoomAttachments, "EditRoomPhotos");
                
               //Initialize attachment gallery
                initializeAttachmentsGallery();
                
                //Find updated room and update roomsViewData array
                var roomindex = _.findIndex(ViewModelInstance.roomsViewData, {'id': ViewModelInstance.editRoomId});
                
               ViewModelInstance.roomsViewData[roomindex] = response.updatedroom;
                
               //Hide the modal
                ViewModelInstance.$nextTick(function () {
                    $("#EditRoomModal").modal("hide");
                });     
            }.bind(this))       
            .fail(function (jqXHR) {
                
                if (jqXHR.status === 422) {
                    
                    let response = JSON.parse(jqXHR.responseText);
                    ViewModelInstance.populateRoomFormErrors(response.data.errors, editRoomFormValidator);         
                } else if (jqXHR.status === 403) {
                    
                    ViewModelInstance.populateNotifications({
                        status: "error",
                        message: "Access denied!"
                    },"EditRoomNotificationArea");
                } else if (jqXHR.status === 413) {
                    
                    ViewModelInstance.populateNotifications({
                        status: "warning",
                        message: "Total Max upload file size for above file upload is 10MB. Check files size and try again."
                    }, "EditRoomNotificationArea");          
                } else {
                    
                    ViewModelInstance.populateNotifications({
                        status: "error",
                        message: AlertData["10077"]
                    }, "EditRoomNotificationArea");
                }
            })
            .always(function () {
                
                $("#EditRoomMFormOverlay").addClass('hidden');
                $("#EditRoomFormSubmit").trigger('blur');
            });
        }
    });
};

//Add Room form input boxes validation rules
var roomParamInputBoxesInitialization = function (isFurniture = false) {

    ViewModelInstance.$nextTick(function () {
        
        $("body input.input-sm").each(function () {
            
            $(this).rules("add", {
                required: true,
                number: true,
                min: 1,
                maxlength: 6
            });
        });
        if (isFurniture) {

            $("#FurnituresTable select.designitem-dropdown").each(function () {

                $(this).rules("add", {
                    required: true
                });
            });
        }
    });
};

//Edit Room input boxes validation rules
var editRoomParamInputBoxesInitialization = function (isFurniture = false) {

    ViewModelInstance.$nextTick(function () {
        
        $("tbody#EditwindowsTableBody input.input-sm1").each(function () {

            $(this).rules("add", {
                required: true,
                number: true,
                min: 1,
                maxlength: 6
            });
        });

        $("tbody#EditDoorsTableBody input.input-sm1").each(function () {

            $(this).rules("add", {
                required: true,
                number: true,
                min: 1,
                maxlength: 6
            });
        });
        
        $("tbody#EditFurtnitureTableBody input.input-sm1").each(function () {

            $(this).rules("add", {
                required: true,
                number: true,
                min: 1,
                maxlength: 6
            });
        });
        if (isFurniture) {

            $("#EditFurtnitureTableBody select.designitem-dropdown").each(function () {

                $(this).rules("add", {
                    required: true
                });
            });
        }
    });
};

//Add Validation to Notes Blocks
var addRoomNotesElementValidationRules = function (notesAdded) {
        
    let required = false;

    ViewModelInstance.$nextTick(function () {

        if(notesAdded) {
            
            required = true;
        }

        $("div#AddRoomSection select.note-category").each(function () {

            $(this).rules("add", {

                required: required
            });
        });

       $("div#AddRoomSection input.note-description").each(function () {

            $(this).rules("add", {

                required: required,
                CheckConsecutiveSpaces: true,
                maxlength: 255
            });
        });
            
        $("div#AddRoomSection input.notes-upload").each(function () {

            $(this).rules("add", {

                checkMultipleFilesExtensions: true,
                checkMultipleFilesSize: true,
                checkPerFileSizeInMultipleFiles: true
            });
        });
    });
};

//Add Validation to Edit Room Notes Blocks
var EditRoomNotesElementValidationRules = function (notesCount = 2) {
    
    let required = false;

    ViewModelInstance.$nextTick(function () {
        
        if(notesCount > 1) {
            
            required = true;
        }
        
        $("div#EditRoomSection select.editroom-note-category").each(function () {

            $(this).rules("add", {

                required: required
            });
       });
       
       $("div#EditRoomSection input.note-description").each(function () {

            $(this).rules("add", {

                required: required,
                CheckConsecutiveSpaces: true,
                maxlength: 255
            });
        });
            
        $("div#EditRoomSection input.editroom-notes-upload").each(function () {

            $(this).rules("add", {

                checkMultipleFilesExtensions: true,
                checkMultipleFilesSize: true,
                checkPerFileSizeInMultipleFiles: true
            });
        });
    });
};