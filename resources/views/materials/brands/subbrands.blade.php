@extends('layouts/master_template')

@section('dynamicStyles')
<link rel="stylesheet" href="{{ asset('/plugins/select2/select2.min.css') }}">
<link href="{{ asset('/css/materials/SubBrand.css') }}" rel="stylesheet"/>
<link href="{{ asset('/css/materials/vueTable.css') }}" rel="stylesheet"/>
@endsection

@section('content')
<div id="SubBrandsPage" v-cloak>
    <div class="col-md-12 text-right addNew-block">
        <a class="btn btn-primary button-custom fl-rt AddButton" data-toggle="tooltip" title="Click here to Add new Sub Brand" @click.prevent="addSubBrand"> <i class="fa fa-fw fa-plus-square"></i> New Sub Brand</a>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="pd-tp-14" v-if="SubBrands.length === 0"> 
                        <div class="callout callout-info">
                            <p><i class="fa fa-fw fa-info-circle" aria-hidden="true"></i> No Sub Brands available.</p>
                        </div>
                    </div>
                    <!-- Sub Brands Vue table component -->
                    <v-client-table :columns="columns" :data="filteredSubBrands" :options="options" v-else>
                        <span slot="Id" slot-scope="props">@{{props.index}}</span>
                        <span slot="Description" slot-scope="props" v-html="(props.row.Description) ? props.row.Description : '<small>N/A</small>'"></span>
                        <span slot="BrandId" slot-scope="props" v-html="getBrand(props.row.BrandId)"></span>
                        <template slot="IsActive" slot-scope="props">
                            <span  class="label label-success" v-if="props.row.IsActive">Active</span>
                            <span class="label label-danger" v-if="!props.row.IsActive">InActive</span>
                        </template>
                        <template slot="Action" slot-scope="props">
                            <a class="btn btn-custom btn-edit btn-sm mr-rt-3" data-toggle="tooltip" data-original-title="Edit Brand" role="button" @click.prevent="editBrand(props.row.Id)">
                                <span class="glyphicon glyphicon-pencil btn-edit"></span>
                            </a>
                            <a class="btn btn-custom btn-edit btn-sm" data-toggle="tooltip" data-original-title="View Brand" role="button" @click.prevent="viewBrand(props.index, props.row.Id)">
                                <span class="glyphicon glyphicon-eye-open btn-edit"></span>
                            </a>
                        </template>
                    </v-client-table>
                </div>
            </div>
        </div>
    </div>
    <!-- Create Sub Brand Modal -->
    <div class="modal fade" role="dialog" id="AddSubBrandModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Add Sub Brand</h4>
                </div>
                <!-- Create Sub Brand Modal component -->
                <create-subbrand-popup></create-subbrand-popup>
                <div class="form-overlay" id="AddSubBrandFormOverlay" :class="{hidden: ShowSaveBrandLoader}">
                     <div class="large loader"></div>
                    <div class="loader-text">Saving Brand</div>
                </div>
            </div>
            <div class="notification-area hidden" id="AddSubBrandFormNotificationArea">
                <div class="alert alert-dismissible hidden no-border">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <p class="body"></p>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Sub Brand Modal --> 
    <div class="modal fade" role="dialog" id="EditSubBrandModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Edit Sub Brand</h4>
                </div>
                 <!-- Update Sub Brand Modal component -->
                <update-subbrand-popup :update-brand-url="currentSubBrandId?(UpdateBrandRoute+'/'+currentSubBrandId):UpdateBrandRoute" :brand-data="selectedBrandData" :brands="brands"></update-subbrand-popup>
                <div class="form-overlay" id="EditBrandFormOverlay" :class="{hidden: ShowUpdateBrandLoader}">
                     <div class="large loader"></div>
                    <div class="loader-text">Updating Brand</div>
                </div>
            </div>
            <div class="notification-area hidden" id="EditBrandFormNotificationArea">
                <div class="alert alert-dismissible hidden no-border">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <p class="body"></p>
                </div>
            </div>
        </div>
    </div>
    <!-- Sub Brand's view component -->
    <view-brand-popup :brand-data="selectedBrandData" :brand-index="currentSubBrandIndex"></view-brand-popup>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ asset('/js/common.js') }}"></script>
<script src="{{ asset('/js/materials/SubBrand.js') }}"></script>
@endsection
