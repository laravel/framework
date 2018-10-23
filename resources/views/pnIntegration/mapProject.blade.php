@extends('layouts/master_template')

@section('content')
<div id="MapProject">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    @if(empty($QuickEstimates) || empty($PNProjects))
                    <div class="callout callout-info">
                        <h4>Information!</h4>
                        @if(empty($QuickEstimates))
                        <p>No estimations found.</p>
                        @else
                        <p>No projects found.</p>
                        @endif
                    </div>
                    @else
                    <form v-if="IsShowForm" action="{{ route('pnintegration.mapusers')}}" method="POST" accept-charset="utf-8" id="MapProjectForm" @submit.prevent="onSubmit">
                        <div class="row">
                            <div class="col-md-4 col-sm-6">
                                <div class="form-group">
                                    <label for="QuickEst">Quick Estimation*</label>
                                    <select name="QuicEst" class="form-control SearchQuickEstimates">
                                        <option></option>
                                        <option v-for="QuickEst in QuickEstimates" :value="QuickEst.ReferenceNumber">@{{ QuickEst.Name }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="form-group">
                                    <label for="Project">Planio Project*</label>
                                    <select name="PnProject" class="form-control SearchProjects">
                                        <option></option>
                                        <option v-for="Project in PNProjects" :value="Project.identifier">@{{Project.name}}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row" v-if="GroupNotification">
                            <div class="col-md-4">
                                <p>No groups found in selected project.</p>
                            </div>

                        </div>
                        <div v-if="GroupUsers.length">
                            <div class="box-header with-border mr-bt-10">
                                <h3 class="box-title">Assign Users</h3>
                            </div>
                            <div class="row" v-for="GroupUser in GroupUsers">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label for="Group">@{{ GroupUser.Group.name }}<span class="text-danger"> *</span></label>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <select class="form-control GroupUsers" @change="SelectUser($event.target.value, GroupUser.Group.id)">
                                            <option value="">Select User</option>
                                            <option v-for="User in GroupUser.Users" :value="User.PNUserID">@{{ User.Email }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 col-sm-6">
                                    <button type="submit" class="btn btn-primary button-custom" :disabled="IsDisableButton">Submit</button>
                                </div>
                            </div>
                        </div>

                        <div class="form-loader" v-if="ShowOverlay">
                            <div class="overlay">
                                <i class="fa fa-refresh fa-spin"></i>
                            </div>
                        </div>
                    </form>
                    <div class="box-header" id="CalloutNotification" v-if="Notification">
                        <div :class="CalloutClass">
                            <h4 id="NotificationHeader" v-if="Notification.data.alertTitle">@{{Notification.data.alertTitle}}</h4>
                            <p id="NotificationBody">@{{Notification.data.alertMessage}}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicStyles')
<link rel="stylesheet" href="{{asset('/plugins/select2/select2.min.css')}}">
@endsection

@section('dynamicScripts')
<script src="{{asset('/js/common.js')}}"></script>
<script src="{{ asset('/js/pnIntegration/mapProject.js') }}"></script>
@endsection