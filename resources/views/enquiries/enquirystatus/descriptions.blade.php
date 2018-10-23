@extends('layouts/master_template')

@section('dynamicStyles')
<link href="{{ asset('/css/enquiries/enquirystatus.css') }}" rel="stylesheet"/>
@endsection

@section('content')
<div id="StatusDescriptionsPage" v-cloak>
    <div class="col-md-12 text-right addNew-block">
        <a class="btn btn-primary button-custom fl-rt AddButton" data-toggle="tooltip" title="Click here to add Description" @click.prevent="addDescription"> <i class="fa fa-fw fa-plus-square"></i> New Status Description</a>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="pd-tp-14" v-if="filteredDescriptions.length < 1"> 
                        <div class="callout callout-info">
                            <p>
                                <i class="fa fa-fw fa-info-circle"></i>No Descriptions found.
                            </p>
                        </div>
                    </div>
                    <!-- Descriptions Vue table component -->
                    <v-client-table :columns="columns" :data="filteredDescriptions" :options="options" v-else>
                        <span slot="Id" slot-scope="props">@{{props.index}}</span>
                        <span slot="Description" slot-scope="props">@{{props.row.Description}}</span>
                        <template slot="IsActive" slot-scope="props">
                            <span  class="label label-success" v-if="props.row.IsActive==1">Active</span>
                            <span class="label label-danger" v-else>InActive</span>
                        </template>
                        <template slot="Action" slot-scope="props">
                            <a class="btn btn-custom btn-edit btn-sm mr-rt-3" data-toggle="tooltip" data-original-title="Edit" role="button" @click.prevent="editDescription(props.row.Id)">
                                <span class="glyphicon glyphicon-pencil btn-edit"></span>
                            </a>
                        </template>
                    </v-client-table>
                </div>
            </div>
        </div>
    </div>
    <!-- Create Description Modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="AddDescriptionModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Add Description</h4>
                </div>
                <form :action="StoreDescriptionUrl" method="POST" accept-charset="utf-8" id="AddDescriptionForm">
                    <div class="modal-body pd-bt-0">
                        <div class="form-group">
                            <label for="Description">Description*</label>
                            <input type="text" name="Description" id="Description" class="form-control" placeholder="Ex: Lost Enquiry"/>
                        </div>
                        <div class="form-group">
                            <label for="EnquiryStatus">Enquiry Status*</label>
                            <select name="EnquiryStatus" id="EnquiryStatus" class="form-control">
                                <option value="">Select Status</option>  
                                <option v-for="status in StatusAvailable" :value="status.Id">@{{ status.Name }}</option>
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
                                <label for="Inactive" class="text-normal cursor-pointer mr-rt-8">InActive</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" id="AddDescSubmitBtn">Save</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <div class="form-overlay" v-if="ShowSaveDescLoader">
                    <div class="large loader"></div>
                    <div class="loader-text">Saving Description</div>
                </div>
            </div>
            <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
        </div>
    </div>
    <!-- Edit Description Modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="EditDescriptionModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Edit Description</h4>
                </div>
                <form action="{{route('enquirystatus.description.update', ["id" => ""])}}" method="POST" accept-charset="utf-8" id="EditDescriptionForm">
                    <div class="modal-body pd-bt-0">
                        <div class="form-group">
                            <label for="EditDescription">Description*</label>
                            <input type="text" name="EditDescription" id="EditDescription" :value="selectedDescription.Description" class="form-control" placeholder="Ex: Lost Enquiry"/>
                        </div>
                        <div class="form-group">
                            <label for="EditEnquiryStatus">Name*</label>
                            <select name="EditEnquiryStatus" id="EditEnquiryStatus" class="form-control">
                                <option value="">Select Status</option>  
                                <option v-for="status in StatusAvailable" :value="status.Id" :selected="status.Id===selectedDescription.EnquiryStatusId">@{{ status.Name }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status*</label>
                            <div class="mr-tp-6">
                                <input type="radio" name="EditStatus" id="EditStatusActive" value="Active" class="input-radio" :checked="selectedDescription.IsActive"/>
                                <label for="EditStatusActive" tabindex="0"></label>
                                <label for="EditStatusActive" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                <input type="radio" name="EditStatus" id="EditStatusInActive" value="Inactive" class="input-radio" :checked="!selectedDescription.IsActive"/>
                                <label for="EditStatusInActive" tabindex="0"></label>
                                <label for="EditStatusInActive" class="text-normal cursor-pointer mr-rt-8">InActive</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" id="EdiDescSubmitBtn">Update</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <div class="form-overlay" v-if="ShowUpdateDescLoader">
                    <div class="large loader"></div>
                    <div class="loader-text">Updating Description</div>
                </div>
            </div>
            <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
        </div>
    </div>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ asset('/js/common.js') }}"></script>
<script src="{{ asset('/js/enquiries/enquirystatus/descriptions.js') }}"></script>
@endsection
