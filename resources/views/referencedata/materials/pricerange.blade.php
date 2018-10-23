@extends('layouts/master_template')

@section('dynamicStyles')
<link href="{{ asset('/css/referencedata/common.css') }}" rel="stylesheet"/>
@endsection

@section('content')
<div id="PriceRangePage" v-cloak>
    <div class="col-md-12 text-right addNew-block">
        <a class="btn btn-primary button-custom fl-rt AddButton" data-toggle="tooltip" title="Click here to add Range" @click.prevent="addRange"> 
            <i class="fa fa-fw fa-plus-square"></i> New Price Range
        </a>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="pd-tp-14" v-if="filteredRanges.length < 1"> 
                        <div class="callout callout-info">
                            <p>
                                <i class="fa fa-fw fa-info-circle"></i>No Price range found.
                            </p>
                        </div>
                    </div>
                    <!-- Vue table component -->
                    <v-client-table :columns="columns" :data="PriceRanges" :options="options" v-else>
                        <span slot="Id" slot-scope="props">@{{props.index}}</span>
                        <span slot="Description" slot-scope="props">@{{props.row.Name}}</span>
                        <span slot="Description" slot-scope="props" v-html="(props.row.Description) ? props.row.Description : '<small>N/A</small>'"></span>
                        <template slot="IsActive" slot-scope="props">
                            <span  class="label label-success" v-if="props.row.IsActive==1">Active</span>
                            <span class="label label-danger" v-else>InActive</span>
                        </template>
                        <template slot="Action" slot-scope="props">
                            <a class="btn btn-custom btn-edit btn-sm mr-rt-3" data-toggle="tooltip" data-original-title="Edit" role="button" @click.prevent="editRange(props.row.Id)">
                                <span class="glyphicon glyphicon-pencil btn-edit"></span>
                            </a>
                        </template>
                    </v-client-table>
                </div>
            </div>
        </div>
    </div>
    <!-- Create Range Modal Component -->
    <create-range :url="StoreRoute" :loader="ShowSaveLoader" :overlay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @closeoverlay="clearOverLayMessage()"></create-range>
    <!-- Edit Range Modal Component -->
    <edit-range :url="UpdateRoute+'/'+currentRangeId" :selected-range="selectedRange" :loader="ShowUpdateLoader" :overlay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @closeoverlay="clearOverLayMessage()"></edit-range>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ asset('/js/common.js') }}"></script>
<script src="{{ asset('/js/referencedata/materials/pricerange.js') }}"></script>
@endsection
