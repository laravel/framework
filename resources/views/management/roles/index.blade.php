@extends('layouts/master_template')

@section('content')
<div id="RolesPage" v-cloak>
    <div class="col-md-12 text-right addNew-block">
        <a class="btn btn-primary button-custom fl-rt AddButton" data-toggle="tooltip" title="Click here to Add new Role" @click.prevent="addRole">
            <i class="fa fa-fw fa-plus-square"></i> Add New Role
        </a>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="notification-area hidden" id="RolePageNotificationArea">
                        <div class="alert alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <p class="body"></p>
                        </div>
                    </div>
                    <div class="pd-tp-14" v-if="filteredRoles.length === 0"> 
                        <div class="callout callout-info">
                            <p><i class="fa fa-fw fa-info-circle" aria-hidden="true"></i> No Roles available.</p>
                        </div>
                    </div>
                    <!-- Brands Vue table component -->
                    <v-client-table :columns="columns" :data="filteredRoles" :options="options" v-else>
                        <span slot="Id" slot-scope="props">@{{props.index}}</span>
                        <span slot="id" slot-scope="props" v-html="getPlanioGroupName(props.row.id)"></span>
                        <span slot="RoleCategoryId" slot-scope="props" v-html="getRoleCategoryName(props.row.RoleCategoryId)"></span>
                        <template slot="IsActive" slot-scope="props">
                            <span  class="label label-success" v-if="props.row.isActive">Active</span>
                            <span class="label label-danger" v-if="!props.row.isActive">InActive</span>
                        </template>
                        <template slot="Actions" slot-scope="props">
                            <a class="mr-rt-3" data-toggle="tooltip" data-original-title="Edit Role" role="button" @click.prevent="editRole(props.row.id)">
                                <i class="fa fa-pencil text-black"></i>
                            </a>
                            <a class="mr-rt-3" data-toggle="tooltip" data-original-title="View Role" role="button" @click.prevent="viewRole(props.row.id)">
                                <i class="fa fa-eye text-black"></i>
                            </a>
                            <a data-toggle="tooltip" data-original-title="Delete Role" role="button" @click.prevent="deleteRole(props.row.id)">
                                <i class="fa fa-trash text-black"></i>
                            </a>
                        </template>
                    </v-client-table>
                </div>
                <div class="form-overlay hidden" id="RolesPageFormOverlay">
                    <div class="large loader"></div>
                    <div class="loader-text">Fetching Roles...</div>
                </div>                       
            </div>
        </div>
    </div>
    <div class="modal fade" tabindex="-1" role="dialog" id="AddRoleModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Add a Role</h4>
                </div>
                <form action="{{ route('management.roles.store') }}" method="POST" accept-charset="utf-8" id="AddRoleForm">
                    <div class="modal-body pd-bt-0">
                        <div class="form-group">
                            <label for="Title">Title*</label>
                            <input type="text" name="Title" id="Title" class="form-control" placeholder="Ex: Administrator"/>
                        </div>
                        <div class="form-group has-feedback">
                            <label for="Slug">Slug*</label>
                            <input type="text" name="Slug" id="Slug" class="form-control" placeholder="Ex: Manager" data-entity-existence-url="{{ route('check.slug') }}"/>
                            <i class="fa form-control-feedback" aria-hidden="true"></i>
                        </div>
                        <div class="form-group">
                            <label for="Category">Category*</label>
                            <select name="Category" id="Category" class="form-control">
                                <option value="">Select a Category</option>  
                                <option v-for="category in filteredCategories" :value="category.Id">@{{ category.name }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="Sort Order">Sort Order*</label>
                            <input type="number" step="1" min="1" max="999" name="SortOrder" id="SortOrder" placeholder="Ex: 23" class="form-control"/>
                        </div>
                        <div class="form-group">
                            <label for="PlanioGroup">Planio Group</label>
                            <select name="PlanioGroup" id="PlanioGroup" class="form-control">
                                <option value="">Select</option>
                                <option v-for="group in filteredGroups" :value="group.id">@{{ group.name }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status*</label>
                            <div class="mr-tp-6">
                                <input type="radio" name="Status" id="Active" value="Active" class="input-radio"/>
                                <label for="Active" tabindex="0"></label>
                                <label for="Active" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                <input type="radio" name="Status" id="Inactive" value="Inactive" class="input-radio"/>
                                <label for="Inactive" tabindex="0"></label>
                                <label for="Inactive" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" id="AddRoleFormSubmit">Save</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <div class="form-overlay hidden" id="AddRoleFormOverlay">
                    <div class="large loader"></div>
                    <div class="loader-text">Adding Role...</div>
                </div>
            </div>
            <div class="notification-area hidden" id="AddRoleFormNotificationArea">
                <div class="alert alert-dismissible hidden no-border">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <p class="body"></p>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" tabindex="-1" role="dialog" id="ViewRoleModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">View a Role</h4>
                </div>
                <div class="modal-body">
                    <table class="table table-striped table-bordered">
                        <tbody>
                            <tr></tr>
                            <tr>
                                <td width="40%">Title</td>
                                <td width="60%">@{{ currentRoleData.title }}</td>
                            </tr>
                            <tr>
                                <td>Slug</td>
                                <td>@{{ currentRoleData.slug }}</td>
                            </tr>
                            <tr>
                                <td>Category</td>
                                <td v-html="getRoleCategoryName(currentRoleData.categoryId)"></td>
                            </tr>
                            <tr>
                                <td>Sort Order</td>
                                <td>@{{ currentRoleData.sortOrder }}</td>
                            </tr>
                            <tr>
                                <td>Status</td>
                                <td>
                                    <span class="label label-success" v-if="currentRoleData.status">Active</span>
                                    <span class="label label-danger" v-else>InActive</span>
                                </td>
                            </tr>
                            <tr>
                                <td>Planio Group Id</td>
                                <td>@{{ currentRoleData.groupId }}</td>
                            </tr>
                            <tr>
                                <td>Planio Group</td>
                                <td>@{{ currentRoleData.group }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" tabindex="-1" role="dialog" id="EditRoleModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Edit a Role</h4>
                </div>
                <form action="{{ route('management.roles.store') }}" method="POST" accept-charset="utf-8" id="EditRoleForm">
                    <div class="modal-body pd-bt-0">
                        <div class="form-group">
                            <label for="EditRoleTitle">Title*</label>
                            <input type="text" name="EditRoleTitle" id="EditRoleTitle" class="form-control" placeholder="Ex: Administrator" :value="currentRoleData.title"/>
                        </div>
                        <div class="form-group">
                            <label for="EditCategory">Category*</label>
                            <select name="EditCategory" id="EditCategory" class="form-control" v-model="currentRoleData.categoryId">
                                <option value="">Select a Category</option>  
                                <option v-for="category in filteredCategories" :value="category.Id">@{{ category.name }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="EditSortOrder">Sort Order*</label>
                            <input type="number" step="1" min="1" max="999" name="EditSortOrder" id="EditSortOrder" placeholder="Ex: 23" class="form-control" :value="currentRoleData.sortOrder"/>
                        </div>
                        <div class="form-group">
                            <label for="EditRolePlanioGroup">Planio Group</label>
                            <select name="EditRolePlanioGroup" id="EditRolePlanioGroup" class="form-control" v-model="currentRoleData.groupId">
                                <option value="">Select</option>
                                <option v-for="group in filteredGroups" :value="group.id">@{{ group.name }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status*</label>
                            <div class="mr-tp-6">
                                <input type="radio" name="EditRoleStatus" id="EditRoleActive" value="Active" class="input-radio" :checked="currentRoleData.status == 1"/>
                                <label for="EditRoleActive" tabindex="0"></label>
                                <label for="EditRoleActive" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                <input type="radio" name="EditRoleStatus" id="EditRoleInactive" value="Inactive" class="input-radio" :checked="currentRoleData.status == 0"/>
                                <label for="EditRoleInactive" tabindex="0"></label>
                                <label for="EditRoleInactive" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" id="EditRoleFormSubmit">Save</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <div class="form-overlay hidden" id="EditRoleFormOverlay">
                    <div class="large loader"></div>
                    <div class="loader-text">Updating Role...</div>
                </div>
            </div>
            <div class="notification-area hidden" id="EditRoleFormNotificationArea">
                <div class="alert alert-dismissible hidden no-border">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <p class="body"></p>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" tabindex="-1" role="dialog" id="DeleteRoleModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Confirm</h4>
                </div>
                <div class="modal-body">
                    Are you sure?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary pull-left" id="DeleteRoleSubmit">Yes</button>
                    <button type="button" class="btn pull-left mr-lt-10" data-dismiss="modal">No</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicStyles')
<link href="{{ asset('/css/management/roles/index.css') }}" rel="stylesheet"/>
@endsection

@section('dynamicScripts')
<script src="{{ asset('/js/common.js') }}"></script>
<script src="https://unpkg.com/vue/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vue-tables-2@1.4.50/dist/vue-tables-2.min.js"></script>
<script src="https://cdn.jsdelivr.net/lodash/4.17.4/lodash.min.js"></script>
<script src="{{ asset('/js/management/roles/index.js') }}"></script>
@endsection
