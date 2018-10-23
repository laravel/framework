@extends('layouts/master_template')

@section('dynamicStyles')
<link href="{{ asset('/css/materials/brands.css') }}" rel="stylesheet"/>
<link href="{{ asset('/css/materials/vueTable.css') }}" rel="stylesheet"/>
@endsection

@section('content')
<div id="BrandsPage" v-cloak>
    <div class="col-md-12 text-right addNew-block">
        <a class="btn btn-primary button-custom fl-rt AddButton" data-toggle="tooltip" title="Click here to Add new Brand" @click.prevent="addBrand"> <i class="fa fa-fw fa-plus-square"></i> New Brand</a>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="pd-tp-14" v-if="brands.length === 0"> 
                        <div class="callout callout-info">
                            <p><i class="fa fa-fw fa-info-circle" aria-hidden="true"></i> No Brands available.</p>
                        </div>
                    </div>
                    <!-- Brands Vue table component -->
                    <v-client-table :columns="columns" :data="filteredBrands" :options="options" v-else>
                        <span slot="Id" slot-scope="props">@{{props.index}}</span>
                        <span slot="Description" slot-scope="props" v-html="getDescription(props.row.Description)"></span>
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
    <!-- Create Brand Modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="AddBrandModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Add Brand</h4>
                </div>
                <!-- Create Brand Modal component -->
                <create-brand-popup></create-brand-popup>
                <div class="form-overlay" id="AddBrandFormOverlay" :class="{hidden: ShowSaveBrandLoader}">
                     <div class="large loader"></div>
                    <div class="loader-text">Saving Brand</div>
                </div>
            </div>
            <div class="notification-area hidden" id="AddBrandFormNotificationArea">
                <div class="alert alert-dismissible hidden no-border">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <p class="body"></p>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Brand Modal --> 
    <div class="modal fade" tabindex="-1" role="dialog" id="EditBrandModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Edit Brand</h4>
                </div>
                <!-- Update Brand Modal component -->
                <update-brand-popup :update-brand-url="currentBrandId?(UpdateBrandUrl+'/'+currentBrandId):UpdateBrandUrl" :brand-data="selectedBrandData"></update-brand-popup>
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
    <!-- Brand's view component -->
    <view-brand-popup :brand-data="selectedBrandData" :brand-index="currentBrandIndex"></view-brand-popup>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ asset('/js/common.js') }}"></script>
<script src="{{ asset('/js/materials/brands.js') }}"></script>
@endsection
