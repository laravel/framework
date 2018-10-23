@extends('layouts/master_template')

@section('dynamicStyles')
<link rel="stylesheet" href="{{ asset('css/management/Permissions.css') }}">
@endsection

@section('content')
<div id="PermissionsPage" v-cloak>
    <div class="col-md-12 text-right addNew-block">
        <a class="btn btn-primary button-custom fl-rt AddButton" data-toggle="tooltip" title="Click here to Add new Permission" @click.prevent="addPermission"> <i class="fa fa-fw fa-plus-square"></i> New Permission</a>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="pd-tp-14" v-if="IDProPermissions.length === 0"> 
                        <div class="callout callout-info">
                            <p><i class="fa fa-fw fa-info-circle" aria-hidden="true"></i> No Permissions available.</p>
                        </div>
                    </div>
                    <!-- Permissions Vue table component -->
                    <v-client-table  :data="filteredPermissions" :columns="columns" :options="options" v-else>
                    </v-client-table>
                </div>
            </div>
        </div>
    </div>
    <!--Create Permission Modal--> 
    <div class="modal fade" tabindex="-1" role="dialog" id="AddPermissionModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Add Permission</h4>
                </div>
                <!--Create Permission Modal component--> 
                <create-permission-popup></create-permission-popup>
                <div class="form-overlay" id="AddPermissionFormOverlay" :class="{hidden: ShowSavePermissionLoader}">
                     <div class="large loader"></div>
                    <div class="loader-text">Saving Permission</div>
                </div>
            </div>
            <div class="notification-area hidden" id="AddPermissionFormNotificationArea">
                <div class="alert alert-dismissible hidden no-border">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <p class="body"></p>
                </div>
            </div>
        </div>
    </div>
    <!--Edit Permission Modal-->  
    <div class="modal fade" tabindex="-1" role="dialog" id="EditPermissionModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Edit Permission</h4>
                </div>
                <!--Update Permission Modal component--> 
                <update-permission-popup :update-permission-url="currentPermissionId?(UpdatePermissionUrl+'/'+currentPermissionId):UpdatePermissionUrl" :permission-data="selectedPermissionData"></update-permission-popup>
                <div class="form-overlay" id="EditPermissionFormOverlay" :class="{hidden: ShowUpdatePermissionLoader}">
                     <div class="large loader"></div>
                    <div class="loader-text">Updating Permission</div>
                </div>
            </div>
            <div class="notification-area hidden" id="EditPermissionFormNotificationArea">
                <div class="alert alert-dismissible hidden no-border">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <p class="body"></p>
                </div>
            </div>
        </div>
    </div>
    <!--Permission's view component--> 
    <view-permission-popup :permission-data="selectedPermissionData" :permission-index="currentPermissionId"></view-brand-popup>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ asset('/js/management/permissions.js') }}"></script>
@endsection
