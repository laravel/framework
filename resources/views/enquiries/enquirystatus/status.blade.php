@extends('layouts/master_template')

@section('dynamicStyles')
<link href="{{ asset('/css/enquiries/enquirystatus.css') }}" rel="stylesheet"/>
@endsection

@section('content')
<div id="EnquiryStatusPage" v-cloak>
    <div class="col-md-12 text-right addNew-block">
        <a class="btn btn-primary button-custom fl-rt AddButton" data-toggle="tooltip" title="Click here to Add Status" @click.prevent="addEnquiryStatus"> <i class="fa fa-fw fa-plus-square"></i> New Status</a>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="pd-tp-14" v-if="filteredEnquiryStatus.length < 1"> 
                        <div class="callout callout-info">
                            <p>
                                <i class="fa fa-fw fa-info-circle"></i>No Status found.
                            </p>
                        </div>
                    </div>
                    <!-- Status Vue table component -->
                    <v-client-table :columns="columns" :data="filteredEnquiryStatus" :options="options" v-else>
                        <span slot="Id" slot-scope="props">@{{props.index}}</span>
                        <span slot="Name" slot-scope="props">@{{ props.row.Name}}</span>
                        <span slot="Description" slot-scope="props" v-html="getStatusDesc(props.row.Description)"></span>
                        <template slot="IsActive" slot-scope="props">
                            <span  class="label label-success" v-if="props.row.IsActive==1">Active</span>
                            <span class="label label-danger" v-else>InActive</span>
                        </template>
                        <template slot="Action" slot-scope="props">
                            <a class="btn btn-custom btn-edit btn-sm mr-rt-3" data-toggle="tooltip" data-original-title="Edit" role="button" @click.prevent="editEnquiryStatus(props.row.Id)">
                                <span class="glyphicon glyphicon-pencil btn-edit"></span>
                            </a>
                        </template>
                    </v-client-table>
                </div>
            </div>
        </div>
    </div>
    <!-- Create Status Modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="AddEnquiryStatusModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Add Enquiry Status</h4>
                </div>
                <form :action="StoreEnquiryStatusRoute" method="POST" accept-charset="utf-8" id="AddEnquiryStatusForm">
                    <div class="modal-body pd-bt-0">
                        <div class="form-group">
                            <label for="Name">Name*</label>
                            <input type="text" name="Name" id="Name" class="form-control" placeholder="Ex: Lost"/>
                        </div>
                        <div class="form-group">
                            <label for="Description">Description</label>
                            <input type="text" name="Description" id="Description" class="form-control" placeholder="Ex: Lost Enquiry"/>
                        </div>
                        <div class="form-group">
                            <label>Status*</label>
                            <div class="mr-tp-6">
                                <input type="radio" name="Status" id="Active" value="Active" class="input-radio"/>
                                <label for="Active" tabindex="0"></label>
                                <label for="Active" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                <input type="radio" name="Status" id="Inactive" value="Inactive" class="input-radio"/>
                                <label for="Inactive" tabindex="0"></label>
                                <label for="Inactive" class="text-normal cursor-pointer mr-rt-8">InActive</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" id="AddStatusFormSubmitBtn">Save</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <div class="form-overlay" id="AddStatusFormOverlay" v-if="ShowSaveStatusLoader">
                    <div class="large loader"></div>
                    <div class="loader-text">Saving Status</div>
                </div>
            </div>
            <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
        </div>
    </div>
    <!-- Edit Status Modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="EditEnquiryStatusModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Edit Enquiry Status</h4>
                </div>
                <form action="{{route('enquirystatus.update', ["id" => ""])}}" method="POST" accept-charset="utf-8" id="EditEnquiryStatusForm">
                    <div class="modal-body pd-bt-0">
                        <div class="form-group">
                            <label for="EditEnquiryStatusName">Name*</label>
                            <input type="text" name="EditEnquiryStatusName" id="EditEnquiryStatusName" :value="selectedEnquiryStatusData.Name" class="form-control" placeholder="Ex: Lost"/>
                        </div>
                        <div class="form-group">
                            <label for="EditEnquiryStatusDescription">Description</label>
                            <input type="text" name="EditEnquiryStatusDescription" id="EditEnquiryStatusDescription" :value="selectedEnquiryStatusData.Description" class="form-control" placeholder="Ex: Lost Enquiry"/>
                        </div>
                        <div class="form-group">
                            <label>Status*</label>
                            <div class="mr-tp-6">
                                <input type="radio" name="EditEnquiryStatus" id="EditEnquiryStatusActive" value="Active" class="input-radio" :checked="selectedEnquiryStatusData.IsActive"/>
                                <label for="EditEnquiryStatusActive" tabindex="0"></label>
                                <label for="EditEnquiryStatusActive" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                <input type="radio" name="EditEnquiryStatus" id="EditEnquiryStatusInActive" value="Inactive" class="input-radio" :checked="!selectedEnquiryStatusData.IsActive"/>
                                <label for="EditEnquiryStatusInActive" tabindex="0"></label>
                                <label for="EditEnquiryStatusInActive" class="text-normal cursor-pointer mr-rt-8">InActive</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" id="EditEnquiryStatusFormSubmitBtn">Update</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <div class="form-overlay" id="EditEnquiryStatusFormOverlay" v-if="ShowUpdateEnquiryStatusLoader">
                    <div class="large loader"></div>
                    <div class="loader-text">Updating Status</div>
                </div>
            </div>
            <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
        </div>
    </div>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ asset('/js/common.js') }}"></script>
<script src="{{ asset('/js/enquiries/enquirystatus/status.js') }}"></script>
@endsection
