/**
 /**
 *  
 * Map Role Permission script
 * 
 **/

let DatatableObj, roleTitles, ValidatorObj, SerializedArray, MapRolePermissionFormData, RolePermissionMapJSON = {};

/** Include needed packages **/
require('../../bootstrap');
//Resolve Jquery library conflict
var jquery = require('jquery');
// convert timestamp to local format date and time
let moment = require('moment');

/** Import Vue table package **/
let VueTable = require('vue-tables-2');
Vue.use(VueTable.ClientTable);

// Register Components
import OverlayNotification from '../../components/overlayNotification';

/** Initialize Vue instance **/
const VueInstance = new Vue({
    el: '#RolePermissionPage',
    data: {
        Roles: Roles, // User Roles
        Permissions: Permissions, //User Permissions
        FormOverLay: true, // Toggle for notification loader
        NotificationIcon: "",
        NotificationMessage: "", //Message for notification loader
        SelectedRolePermission: SelectedRolePermission,
        ShowSubmitOverlay: false, // Form submit ajax loader flag
        detachedRolePermissions: [], // Detached Role Permissions Array
        AttachedRolePermissions: [] // Attached Role Permissions Array
    },
    components: {
        'overlay-notification': OverlayNotification
    },
    mounted() {
        this.MappedRolePermission();
    },
    computed: {
        filteredRoles: function () {
            return _.sortBy(this.Roles, [role => role.slug.toLowerCase()], ['desc']);
        },
        filteredPermissions: function () {
            return _.sortBy(this.Permissions, [permission => permission.Slug.toLowerCase()], ['desc']);
        }
    },
    methods: {

        MappedRolePermission() {
            _.forEach(this.SelectedRolePermission, function ($RolePermissions) {
                var PreselectedCheckboxes = $("#" + $RolePermissions.RoleId + "-" + $RolePermissions.PermissionId + "-RolePermission");
                PreselectedCheckboxes.prop('checked', true);
            });
        },

        // Populates notifications of the form.
        populateNotifications(response) {
            this.NotificationMessage = response.message;
            this.NotificationIcon = response.status;
            this.FormOverLay = false;
            if (response.status == "success") {
                this.ShowSubmitOverlay = false;
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
                this.ShowSubmitOverlay = false;
                this.NotificationIcon = "ban";
            }
        },

        // Hide Success Message
        clearOverLayMessage() {
            this.FormOverLay = true;
            this.NotificationMessage = "";
            this.NotificationIcon = "";
        },

        // Redirect to RolePermission listing page
        refreshPage() {
            // Redirect to RolePErmission listing page
            window.location = '/management/rolepermission';
        },

        // On failed form submission  
        onFail(error) {
            this.ShowSubmitOverlay = false;
            this.populateNotifications({
                status: "error",
                message: AlertData["10077"]
            });
        },
    }
});

$(document).ready(function () {

    // Initialize datatables
    initializeDataTable();
    InitializeValidator();

});

// DataTable initialization
function initializeDataTable() {
    // DataTable initialization
    DatatableObj = $('#RolePermissionTable').DataTable({
        paging: true,
        retrieve: true,
        autoWidth: false,
        "scrollX": true,
        scrollCollapse: true,
        pageLength: 10,
        pagingType: "simple_numbers",
         fixedColumns:   {leftColumns: 1},
        "aoColumnDefs": [
            {'bSortable': false, 'aTargets': ['no-sort']}
        ]

    });
    $("#RolePermissionTable_filter input").attr('placeholder', 'Search...').focus();
}

// Initialize form validator 
var InitializeValidator = function () {
    ValidatorObj = $("#MapRolePermission").validate({
        submitHandler: function (form, event) {
            event.preventDefault();
            $("#SubmitBtn").trigger('blur');
            SerializedArray = DatatableObj.$('input').serializeArray();
            MapRolePermissionFormData = new FormData();
            let newRolePermission = new Array();
            SerializedArray.map(function (inputObject, index) {
                var SplitInputName = inputObject.name.split("-");
                if (SplitInputName.length === 3) {
                    newRolePermission.push({"PermissionId": parseInt(SplitInputName[1]), "RoleId": parseInt(SplitInputName[0])});
                    var checkbox = $("#" + SplitInputName[0] + "-" + SplitInputName[1] + "-" + SplitInputName[2]);
                    if (checkbox.is(':checked')) {
                        if (inputObject.value === "on") {
                            inputObject.value = true;
                        } else if (inputObject.value === "off") {
                            inputObject.value = false;
                        }
                        if (SplitInputName[0] in RolePermissionMapJSON) {
                            if (!(SplitInputName[1] in RolePermissionMapJSON[SplitInputName[0]])) {
                                RolePermissionMapJSON[SplitInputName[0]][SplitInputName[1]] = {};
                            }
                            RolePermissionMapJSON[SplitInputName[0]][SplitInputName[1]][SplitInputName[2]] = inputObject.value;
                        } else {
                            RolePermissionMapJSON[SplitInputName[0]] = {};
                            RolePermissionMapJSON[SplitInputName[0]][SplitInputName[1]] = {};
                            RolePermissionMapJSON[SplitInputName[0]][SplitInputName[1]][SplitInputName[2]] = inputObject.value;
                        }
                    }
                } else {
                    MapRolePermissionFormData.append(inputObject.name, inputObject.value);
                }
            });
            VueInstance.detachedRolePermissions = VueInstance.SelectedRolePermission.filter(comparer(newRolePermission));
            VueInstance.AttachedRolePermissions = newRolePermission.filter(comparer(VueInstance.SelectedRolePermission));
            console.log(VueInstance.AttachedRolePermissions);
            var detachedRolePermissionsIds = _.map(VueInstance.detachedRolePermissions, 'Id');
            if (Object.keys(VueInstance.SelectedRolePermission).length === 0 && Object.keys(VueInstance.AttachedRolePermissions).length === 0) {
                VueInstance.populateNotifications({
                    status: "warning",
                    message: 'Map at least one Selection'
                });
                return;
            }
            MapRolePermissionFormData.append("AttachedRolePermissions", JSON.stringify(VueInstance.AttachedRolePermissions));
            MapRolePermissionFormData.append("DetachedRolePermissionIds", detachedRolePermissionsIds);
            VueInstance.ShowSubmitOverlay = true;
            $.ajax({
                url: "/management/rolepermission",
                type: 'POST',
                dataType: 'json',
                data: MapRolePermissionFormData,
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
                        VueInstance.ShowSubmitOverlay = false;
                        $("#SubmitBtn").trigger('blur');
                    });
        }
    });
};

function comparer(otherArray) {
    return function (current) {
        return otherArray.filter(function (other) {
            return other.PermissionId == current.PermissionId && other.RoleId == current.RoleId
        }).length == 0;
    }
}
