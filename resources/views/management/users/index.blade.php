@extends('layouts/master_template')

@section('content')
    <div id="UsersPage" data-bootstrap-url="{{ route('management.users.bootstap') }}" v-cloak>
        <div class="col-md-12 text-right addNew-block">
            <a data-toggle="tooltip" title="" class="btn btn-primary button-custom fl-rt AddButton" data-original-title="Click here to Add New User" @click="addUser">
                <i class="fa fa-fw fa-plus-square"></i> New User</a>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-body">
                        <div class="notification-area" id="UsersPageNotificationArea">
                            <div class="alert alert-dismissible hidden">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                <p class="body"></p>
                            </div>
                        </div>
                        
                        <div class="pd-tp-14" v-if="filteredUsers.length === 0"> 
                            <div class="callout callout-info">
                                <p class="text-center">
                                    <i class="fa fa-fw fa-info-circle"></i>No Users found.
                                </p>
                            </div>
                        </div>
                        <!-- Vue table component -->
                        <v-client-table :columns="columns" :data="filteredUsers" :options="options" v-else>
                            <template slot="id" slot-scope="props">@{{props.index}}</template>
                            <template slot="Roles" slot-scope="props">@{{ getRoles(props.row.id) }}</template>
                            <template slot="Status" slot-scope="props">
                                <span  class="label label-success" v-if="props.row.isActive==1">Active</span>
                                <span class="label label-danger" v-else>InActive</span>
                            </template>
                            <template slot="Action" slot-scope="props">
                                <a data-toggle="tooltip" data-original-title="Edit" role="button" @click="editUser(props.row.id)">
                                    <i class="fa fa-pencil text-black" aria-hidden="true"></i>
                                </a>
                                <a data-toggle="tooltip" data-original-title="View" role="button" @click="viewUser(props.row.id)">
                                    <i class="fa fa-eye text-black" aria-hidden="true"></i>
                                </a>
                                <a data-toggle="tooltip" data-original-title="Delete" role="button" @click="deleteUsers(props.row.id)">
                                    <i class="fa fa-trash text-black" aria-hidden="true"></i>
                                </a>
                            </template>
                        </v-client-table>
                    </div>
                    <div class="form-overlay hidden" id="UsersPageFormOverlay">
                        <div class="large loader"></div>
                        <div class="loader-text">Fetching Users...</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" tabindex="-1" role="dialog" id="AddUserModal">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title no-text-transform">Add an User</h4>
                    </div>
                    <form action="{{ route('management.users.store') }}" method="POST" accept-charset="utf-8" id="AddUserForm">
                        <div class="modal-body pd-bt-0">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="FirstName">First Name*</label>
                                        <input type="text" name="FirstName" id="FirstName" class="form-control" placeholder="Ex: John"/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="LastName">Last Name*</label>
                                        <input type="text" name="LastName" id="LastName" class="form-control" placeholder="Ex: Doe"/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="Email">Email*</label>
                                        <input type="email" name="Email" id="Email" class="form-control" placeholder="Ex: user@example.com" data-entity-existence-url="{{ route('check.email') }}"/>
                                        <i class="fa form-control-feedback" aria-hidden="true"></i>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="Mobile">Mobile*</label>
                                        <input type="text" name="Mobile" id="Mobile" class="form-control" placeholder="Ex: 8989899898" data-entity-existence-url="{{ route('check.mobile') }}"/>
                                        <i class="fa form-control-feedback" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="Aadhar">Aadhar Number</label>
                                        <input type="text" name="Aadhar" id="Aadhar" class="form-control" placeholder="Ex: 4321-8765-5678-1234"/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="PANCard">PAN Card</label>
                                        <input type="text" name="PANCard" id="PANCard" class="form-control text-uppercase" placeholder="Ex: CBAP1234AB"/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="ProfilePicture">Profile Picture</label>
                                        <div class="input-group">
                                            <label class="input-group-addon" for="ProfilePicture">
                                                <i class="fa fa-picture-o"></i>
                                            </label>
                                            <label class="form-control" for="ProfilePicture">
                                                <span class="placeholder-text text-normal no-text-transform" id="ProfilePictureAlias">Ex: gravatar.png</span>
                                            </label>
                                        </div>
                                        <input type="file" name="ProfilePicture" id="ProfilePicture" class="form-control hidden" accept="image/*"/>

                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="no-text-transform">Map to Planio?*</label>
                                        <div class="mr-tp-6">
                                            <input type="radio" name="MapToPlanio" id="MapToPlanioYes" value="Yes" class="input-radio" checked="checked"/>
                                            <label for="MapToPlanioYes" tabindex="0"></label>
                                            <label for="MapToPlanioYes" class="text-normal cursor-pointer mr-rt-8">Yes</label>
                                            <input type="radio" name="MapToPlanio" id="MapToPlanioNo" value="No" class="input-radio"/>
                                            <label for="MapToPlanioNo" tabindex="0"></label>
                                            <label for="MapToPlanioNo" class="text-normal cursor-pointer mr-rt-8">No</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Account Status*</label>
                                        <div class="mr-tp-6">
                                            <input type="radio" name="Status" id="Active" value="Active" class="input-radio" checked="checked"/>
                                            <label for="Active" tabindex="0"></label>
                                            <label for="Active" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                            <input type="radio" name="Status" id="Inactive" value="Inactive" class="input-radio"/>
                                            <label for="Inactive" tabindex="0"></label>
                                            <label for="Inactive" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="no-text-transform">Send Email and SMS notifications?*</label>
                                        <div class="mr-tp-6">
                                            <input type="radio" name="SendNotification" id="SendNotificationYes" value="Yes" class="input-radio"/>
                                            <label for="SendNotificationYes" tabindex="0"></label>
                                            <label for="SendNotificationYes" class="text-normal cursor-pointer mr-rt-8">Yes</label>
                                            <input type="radio" name="SendNotification" id="SendNotificationNo" value="No" class="input-radio" checked="checked"/>
                                            <label for="SendNotificationNo" tabindex="0"></label>
                                            <label for="SendNotificationNo" class="text-normal cursor-pointer mr-rt-8">No</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="mr-bt-10">Assign Roles*</label>
                                        <div class="roles-list">
                                            @foreach($roles as $index => $role)
                                            <div class="list-role">
                                                <input type="checkbox" name="Roles" id="Roles-{{ $role->Id }}" class="checkbox" v-model="selected.roles" value="{{ $role->Id }}"/>
                                                <label for="Roles-{{ $role->Id }}" tabindex="0"></label>
                                                <label for="Roles-{{ $role->Id }}" class="role text-normal cursor-pointer mr-rt-8">{{ $role->Title }}</label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary pull-left button-custom" id="AddUserFormSubmit">Add</button>
                            <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                    <div class="form-overlay hidden" id="AddUserFormOverlay">
                        <div class="large loader"></div>
                        <div class="loader-text">Adding User...</div>
                    </div>
                </div>
                <div class="notification-area hidden" id="AddUserFormNotificationArea">
                    <div class="alert alert-dismissible hidden no-border">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <p class="body"></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" tabindex="-1" role="dialog" id="ViewUserModal">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title no-text-transform">View an User</h4>
                    </div>
                    <div class="modal-body">
                        <table class="table table-striped table-bordered">
                            <tbody>
                                <tr></tr>
                                <tr>
                                    <td width="40%">First Name</td>
                                    <td width="60%">@{{ currentUser.firstname }}</td>
                                </tr>
                                <tr>
                                    <td>Last Name</td>
                                    <td>@{{ currentUser.lastname }}</td>
                                </tr>
                                <tr>
                                    <td>Email</td>
                                    <td>@{{ currentUser.email }}</td>
                                </tr>
                                <tr>
                                    <td>Mobile</td>
                                    <td>@{{ currentUser.mobile }}</td>
                                </tr>
                                <tr>
                                    <td>Assigned Roles</td>
                                    <td>@{{ getRoles(currentUser.id) }}</td>
                                </tr>
                                <tr>
                                    <td>Account Status</td>
                                    <td>
                                        <span class="label label-success" v-if="currentUser.isActive">Active</span>
                                        <span class="label label-danger" v-else>InActive</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Aadhar</td>
                                    <td>@{{ currentUser.aadhar }}</td>
                                </tr>
                                <tr>
                                    <td>PAN Number</td>
                                    <td>@{{ currentUser.panCard }}</td>
                                </tr>
                                <tr>
                                    <td>Does this user mapped to Planio?</td>
                                    <td>
                                        <span class="label label-success" v-if="isMappedToPlanio(currentUser.id)">Yes</span>
                                        <span class="label label-danger" v-else>No</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Planio UserId</td>
                                    <td>@{{ getPlanioUserId(currentUser.id) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" tabindex="-1" role="dialog" id="EditUserModal">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title no-text-transform">Edit an User</h4>
                    </div>
                    <form action="{{ route('management.users.store') }}" method="POST" accept-charset="utf-8" id="EditUserForm">
                        <div class="modal-body pd-bt-0">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="FirstName">First Name*</label>
                                        <input type="text" name="FirstName" id="FirstName" class="form-control" placeholder="Ex: John" :value="currentUser.firstname"/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="LastName">Last Name*</label>
                                        <input type="text" name="LastName" id="LastName" class="form-control" placeholder="Ex: Doe" :value="currentUser.lastname"/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="Email">Email</label>
                                        <input type="text" id="Email" class="form-control" disabled="disabled" :value="currentUser.email"/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="Mobile">Mobile</label>
                                        <input type="text" id="Mobile" class="form-control" disabled="disabled" :value="currentUser.mobile"/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="Aadhar">Aadhar Number</label>
                                        <input type="text" name="Aadhar" id="Aadhar" class="form-control" placeholder="Ex: 4321-8765-5678-1234" :value="currentUser.aadhar"/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="PANCard">PAN Card</label>
                                        <input type="text" name="PANCard" id="PANCard" class="form-control text-uppercase" placeholder="Ex: CBAP1234AB" :value="currentUser.panCard"/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="EditProfilePicture">Profile Picture</label>
                                        <div class="input-group">
                                            <label class="input-group-addon" for="EditProfilePicture">
                                                <i class="fa fa-picture-o"></i>
                                            </label>
                                            <label class="form-control" for="EditProfilePicture">
                                                <span class="placeholder-text text-normal no-text-transform" id="ProfilePictureAlias">Ex: gravatar.png</span>
                                            </label>
                                        </div>
                                        <input type="file" name="EditProfilePicture" id="EditProfilePicture" class="form-control hidden" accept="image/*"/>

                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Account Status*</label>
                                        <div class="mr-tp-6">
                                            <input type="radio" name="EditStatus" id="EditActive" value="Active" class="input-radio" v-model="selected.isActive"/>
                                            <label for="EditActive" tabindex="0"></label>
                                            <label for="EditActive" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                            <input type="radio" name="EditStatus" id="EditInactive" value="Inactive" class="input-radio" v-model="selected.isActive"/>
                                            <label for="EditInactive" tabindex="0"></label>
                                            <label for="EditInactive" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="mr-bt-10">Assign Roles*</label>
                                        <div class="roles-list">
                                            @foreach($roles as $index => $role)
                                            <div class="list-role">
                                                <input type="checkbox" name="EditRoles" id="EditRoles-{{ $role->Id }}" class="checkbox" v-model="selected.roles" value="{{ $role->Id }}"/>
                                                <label for="EditRoles-{{ $role->Id }}" tabindex="0"></label>
                                                <label for="EditRoles-{{ $role->Id }}" class="role text-normal cursor-pointer mr-rt-8">{{ $role->Title }}</label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary pull-left button-custom" id="EditUserFormSubmit">Update</button>
                            <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                    <div class="form-overlay hidden" id="EditUserFormOverlay">
                        <div class="large loader"></div>
                        <div class="loader-text">Updating User...</div>
                    </div>
                </div>
                <div class="notification-area hidden" id="EditUserFormNotificationArea">
                    <div class="alert alert-dismissible hidden no-border">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <p class="body"></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" tabindex="-1" role="dialog" id="DeleteUserModal">
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
                        <button type="button" class="btn btn-primary pull-left" id="DeleteUserSubmit">Yes</button>
                        <button type="button" class="btn pull-left mr-lt-10" data-dismiss="modal">No</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('dynamicStyles')
    <link href="{{ asset('/css/management/roles/index.css') }}" rel="stylesheet"/>
    <link href="{{ asset('/css/management/users/index.css') }}" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('/plugins/multiselect/bootstrap-multiselect.css') }}">
@endsection

@section('dynamicScripts')
    <script src="https://unpkg.com/vue/dist/vue.js"></script>
    <script src="https://unpkg.com/vue-router/dist/vue-router.js"></script>
    <script src="https://cdn.jsdelivr.net/lodash/4.17.4/lodash.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue-tables-2@1.4.50/dist/vue-tables.js"></script>
    <script src="{{ asset('/plugins/multiselect/bootstrap-multiselect.js') }}"></script>
    <script src="{{ asset('/js/common.js') }}"></script>
    <script src="{{ asset('/js/management/users/index.js') }}"></script>
@endsection
