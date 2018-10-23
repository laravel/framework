require('../../bootstrap');
var $ = require('jquery');
require('select2');
import Form from '../../utilities/Form';

const VueInstance = new Vue({
    el: "#MapProject",
    data() {
        return {
            SelectedEstimation: '',
            SelectedProject: '',
            SelectedProjectName: '',
            QuickEstimates: QuickEstimates,
            PNProjects: PNProjects,
            GetGroupsUrl: GetGroupsUrl,
            PostFormUrl: PostFormUrl,
            GroupUsers: [],
            ShowOverlay: false,
            SelectedUsers: {},
            IsDisableButton: true,
            Notification: '',
            CalloutClass: '',
            IsShowForm : true,
            GroupNotification: false
        };
    },
    methods: {
        QuickEstSelect(SelectedEst) {
            this.SelectedEstimation = SelectedEst;
            if (this.SelectedEstimation && this.SelectedProject) {
                this.GetGroupUsers();
            }
        },
        ProjectSelect(SelectedPrj, ProjectName) {
            this.SelectedProject = SelectedPrj;
            this.SelectedProjectName = ProjectName;
            if (this.SelectedEstimation && this.SelectedProject) {
                this.GetGroupUsers();
            }
        },
        GetGroupUsers() {
            this.Notification = '';
            this.IsDisableButton = true;
            this.GroupUsers = [];
            this.SelectedUsers = {};
            this.ShowOverlay = true;
            this.GroupNotification = false;
            axios.get(this.GetGroupsUrl + '/' + this.SelectedProject)
                    .then(response => {
                        this.GroupUsers = response.data;
                        this.ShowOverlay = false;
                        if(response.data.length == 0){
                            this.GroupNotification = true;
                        }
                    })
                    .catch(error => {
                        this.ShowOverlay = false;
                        this.Notification = error.response.data;
                        this.CalloutClass = 'callout callout-' + this.Notification.data.alertType;
                    });
        },
        SelectUser(UserId, GroupId) {
            if (UserId) {
                this.SelectedUsers[GroupId] = UserId;
            } else {
                delete this.SelectedUsers[GroupId];
            }
            if ((this.GroupUsers.length > 0) && (this.GroupUsers.length == Object.keys(this.SelectedUsers).length)) {
                this.IsDisableButton = false;
            } else {
                this.IsDisableButton = true;
            }
        },
        onSubmit() {
            this.ShowOverlay = true;
            axios.post(this.PostFormUrl, this.requiredData())
                    .then(response => {
                        this.IsShowForm = false;
                        this.ShowOverlay = false;
                        this.Notification = response.data;
                        this.CalloutClass = 'callout callout-' + this.Notification.data.alertType;
                    })
                    .catch(error => {
                        this.ShowOverlay = false;
                        this.Notification = error.response.data;
                        this.CalloutClass = 'callout callout-' + this.Notification.data.alertType;
                    });
        },
        requiredData() {
            let data = {};
            data['SelectedEstimation'] = this.SelectedEstimation;
            data['SelectedProject'] = this.SelectedProject;
            data['SelectedUsers'] = this.SelectedUsers;
            data['SelectedProjectName'] = this.SelectedProjectName;
            return data;
        }
    }
});

$(document).ready(function () {

    /**
     * Intializing the search autoselect
     * 
     */
    $(".SearchQuickEstimates").select2({
        placeholder: 'Please Select Estimation'
    }).on("change", function (e) {
        VueInstance.QuickEstSelect(this.value);
    });

    $(".SearchProjects").select2({
        placeholder: 'Please Select Estimation'
    }).on("change", function (e) {
        let SelectedText = this.options[this.selectedIndex].innerHTML;
        VueInstance.ProjectSelect(this.value, SelectedText);
    });
});