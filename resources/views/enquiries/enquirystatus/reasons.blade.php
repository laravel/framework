@extends('layouts/master_template')

@section('dynamicStyles')
<link href="{{ asset('/css/enquiries/enquirystatus.css') }}" rel="stylesheet"/>
@endsection

@section('content')
<div id="StatusReasonsPage" v-cloak>
    <div class="col-md-12 text-right addNew-block">
        <a class="btn btn-primary button-custom fl-rt AddButton" data-toggle="tooltip" title="Click here to add Reason" @click.prevent="addReason"> 
            <i class="fa fa-fw fa-plus-square"></i> New Status Reason
        </a>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="pd-tp-14" v-if="filteredReasons.length < 1"> 
                        <div class="callout callout-info">
                            <p>
                                <i class="fa fa-fw fa-info-circle"></i>No Reasons found.
                            </p>
                        </div>
                    </div>
                    <!-- Reasons Vue table component -->
                    <v-client-table :columns="columns" :data="filteredReasons" :options="options" v-else>
                        <span slot="Id" slot-scope="props">@{{props.index}}</span>
                        <span slot="Description" slot-scope="props">@{{props.row.Reason}}</span>
                        <template slot="IsActive" slot-scope="props">
                            <span  class="label label-success" v-if="props.row.IsActive==1">Active</span>
                            <span class="label label-danger" v-else>InActive</span>
                        </template>
                        <template slot="Action" slot-scope="props">
                            <a class="btn btn-custom btn-edit btn-sm mr-rt-3" data-toggle="tooltip" data-original-title="Edit" role="button" @click.prevent="editReason(props.row.Id)">
                                <span class="glyphicon glyphicon-pencil btn-edit"></span>
                            </a>
                        </template>
                    </v-client-table>
                </div>
            </div>
        </div>
    </div>
    <!-- Create Reason Modal -->
    <create-reason :url="StoreReasonRoute" :loader="ShowSaveLoader" :enquiry-status="StatusAvailable" :overlay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @closeoverlay="clearOverLayMessage()"></create-reason>
    <!-- Edit Reason Modal -->
    <edit-reason :url="UpdateReasonRoute+'/'+currentReasonId" :selected-reason="selectedReason" :loader="ShowUpdateLoader" :enquiry-status="StatusAvailable" :overlay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @closeoverlay="clearOverLayMessage()"></edit-reason>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ asset('/js/common.js') }}"></script>
<script src="{{ asset('/js/enquiries/enquirystatus/reasons.js') }}"></script>
@endsection
