@extends('layouts/master_template')

@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
<link rel="stylesheet" href="{{ asset('css/management/RolePermissions.css') }}">

@endsection

@section('content')
<div id="RolePermissionPage" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="callout callout-info" v-if="Permissions.length === 0 || Roles.length == 0">
                        <p><i class="fa fa-fw fa-info-circle"></i> There are no permission to be map.</p>
                    </div>
                    <form id="MapRolePermission" method="POST" action="" enctype="multipart/form-data" v-else>
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <div class="row">
                            <div class="col-md-12 table-responsive">
                                <table class="table table-bordered table-condensed" id="RolePermissionTable">
                                    <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                                        <tr>
                                        <th style="vertical-align:middle;width: 20%">Role Permission</th>
                                        <th class="no-sort" v-for="(role,index) in filteredRoles" style="vertical-align:middle">@{{role.title}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(permission,index) in filteredPermissions">
                                        <td style="vertical-align:middle;background-color: white;">
                                            <b>@{{permission.Slug}}</b>
                                        </td>
                                        <td class="text-center text-vertical-align" v-for="(role,index) in filteredRoles">
                                            <input type="checkbox" class="checkbox" :name="role.id + '-' + permission.Id + '-RolePermission'" :id="role.id + '-' + permission.Id + '-RolePermission'">
                                        <label :for="role.id + '-' + permission.Id + '-RolePermission'"></label>
                                        </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <input type="submit" name="SubmitBtn" value="Update" data-toggle="tooltip" title="Update" class="btn btn-primary button-custom" id="SubmitBtn"/> 
                                <input type="reset" class="btn button-custom" value="Undo Changes" data-toggle="tooltip" id="CancelBtn" title="Undo Changes"/>
                            </div>
                        </div>
                    </form>  
                </div>
                <div class="form-overlay hidden" id="RolesPageFormOverlay"  v-if="ShowSubmitOverlay">
                    <div class="large loader"></div>
                    <div class="loader-text">Fetching Roles...</div>
                </div> 
                <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
            </div>
        </div>
    </div>
</div>
@endsection
@section('dynamicScripts')
<script src="{{ asset('/js/common.js') }}"></script>
<script src="{{ URL::assetUrl('/AdminLTE/plugins/jQueryUI/jquery-ui.min.js') }}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="https://cdn.jsdelivr.net/lodash/4.17.4/lodash.min.js"></script>
<script src="https://cdn.datatables.net/fixedcolumns/3.2.6/js/dataTables.fixedColumns.min.js"></script>
<script src="{{ asset('/js/management/RolePermissions.js') }}"></script>
@endsection
