@extends('layouts/master_template')

@section('dynamicStyles')
<link rel="stylesheet" href="{{ asset('/plugins/select2/select2.min.css') }}">
<link href="{{ asset('/css/materials/types.css') }}" rel="stylesheet"/>
<link href="{{ asset('/css/materials/vueTable.css') }}" rel="stylesheet"/>
@endsection

@section('content')
<div id="MaterialTypesPage" v-cloak>
    <div class="col-md-12 text-right addNew-block">
        <a class="btn btn-primary button-custom fl-rt AddButton" data-toggle="tooltip" title="Click here to Add new Type" @click.prevent="addType"> <i class="fa fa-fw fa-plus-square"></i> New Type</a>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="pd-tp-14" v-if="filteredTypes.length === 0"> 
                        <div class="callout callout-info">
                            <p><i class="fa fa-fw fa-info-circle" aria-hidden="true"></i> No Material types available.</p>
                        </div>
                    </div>
                    <!-- Types Vue table component -->
                    <v-client-table :columns="columns" :data="filteredTypes" :options="options" v-else>
                        <span slot="Id" slot-scope="props">@{{props.index}}</span>
                        <span slot="Description" slot-scope="props" v-html="(props.row.Description) ? props.row.Description : '<small>N/A</small>'"></span>
                        <span slot="FormCategoryId" slot-scope="props" v-html="getCategory(props.row.FormCategoryId)"></span>
                        <template slot="IsActive" slot-scope="props">
                            <span  class="label label-success" v-if="props.row.IsActive">Active</span>
                            <span class="label label-danger" v-if="!props.row.IsActive">InActive</span>
                        </template>
                        <template slot="Action" slot-scope="props">
                            <a class="btn btn-custom btn-edit btn-sm mr-rt-3" data-toggle="tooltip" data-original-title="Edit Type" role="button" @click.prevent="editType(props.row.Id)">
                                <span class="glyphicon glyphicon-pencil btn-edit"></span>
                            </a>
                            <a class="btn btn-custom btn-edit btn-sm" data-toggle="tooltip" data-original-title="View Type" role="button" @click.prevent="viewType(props.index, props.row.Id)">
                                <span class="glyphicon glyphicon-eye-open btn-edit"></span>
                            </a>
                        </template>
                    </v-client-table>
                </div>
            </div>
        </div>
    </div>
    <!-- Create Type Modal -->
    <div class="modal fade" role="dialog" id="AddTypeModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Add Type</h4>
                </div>
                <!-- Create Type Modal component -->
                <create-type-popup></create-type-popup>
                <div class="form-overlay" id="AddTypeFormOverlay" :class="{hidden: ShowSaveTypeLoader}">
                    <div class="large loader"></div>
                    <div class="loader-text">Saving Type</div>
                </div>
                <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
            </div>
        </div>
    </div>
    <!-- Edit Type Modal --> 
    <div class="modal fade" role="dialog" id="EditTypeModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Edit Type</h4>
                </div>
                <!-- Update Type Modal component -->
                <update-type-popup :update-type-url="CurrentTypeId?(UpdateTypeRoute+'/'+CurrentTypeId):UpdateTypeRoute" :type-data="selectedType" :form-categories="FormCategories"></update-type-popup>
                <div class="form-overlay" id="EditTypeFormOverlay" :class="{hidden: ShowUpdateTypeLoader}">
                    <div class="large loader"></div>
                    <div class="loader-text">Updating Type</div>
                </div>
                <div class="notification-overlay" :class="{hidden: UpdateFormOverLay}" @click.prevent="clearOverLayMessage()">
                    <div style="text-align: center;" :class="'overlay-'+NotificationIcon">
                        <button type="button" class="close notificationOverlay-close" @click.prevent="clearOverLayMessage()" aria-label="Close" title="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <div :class="'notificationOverlay-icon fa fa-' + NotificationIcon">
                    </div>
                    <div class="nofitication-message">@{{NotificationMessage}}</div>
                    </div>
                </div> 
            </div>
        </div>
    </div>
    <!-- Type's view component -->
    <view-type-popup :type-data="selectedType" :type-index="currentTypeIndex"></view-type-popup>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ asset('/js/common.js') }}"></script>
<script src="{{ asset('/js/materials/types.js') }}"></script>
@endsection
