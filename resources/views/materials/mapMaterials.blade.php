@extends('layouts/master_template')

@section('dynamicStyles')
<link href="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.css') }}" rel="stylesheet" />
<link href="{{ asset('/css/materials/mapMaterial.css') }}" rel="stylesheet"/>
<link href="{{ asset('/css/materials/vueTable.css') }}" rel="stylesheet"/>
@endsection

@section('content')
<div id="MapMaterials" v-cloak>
    <div class="col-md-12 text-right addNew-block">
        <a class="btn btn-primary button-custom fl-rt AddButton" data-toggle="tooltip" title="Click here to Map a Material" @click.prevent="mapMaterial"> <i class="fa fa-fw fa-plus-square"></i> Map Material</a>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="pd-tp-14" v-if="Data.length === 0"> 
                        <div class="callout callout-info">
                            <p><i class="fa fa-fw fa-info-circle" aria-hidden="true"></i> No Mapped Materials available.</p>
                        </div>
                    </div>
                    <!-- Brands Vue table component -->
                    <v-client-table :columns="columns" :data="Data" :options="options" v-else>
                        <span slot="Id" slot-scope="props">@{{props.index}}</span>
                        <span slot="Brand" slot-scope="props" v-html="getBrand(props.row.Brand)"></span>
                        <span slot="SubBrand" slot-scope="props" v-html="getSubBrand(props.row.SubBrand)"></span>
                        <span slot="FormCategory" slot-scope="props" v-html="getFormCategory(props.row.FormCategory)"></span>
                        <template slot="IsActive" slot-scope="props">
                            <span  class="label label-success" v-if="props.row.IsActive==1">Active</span>
                            <span class="label label-danger" v-else>InActive</span>
                        </template>
                        <template slot="Action" slot-scope="props">
                            <a class="btn btn-custom btn-edit btn-sm mr-rt-3" data-toggle="tooltip" data-original-title="Edit" role="button" @click.prevent="edit(props.row)">
                                <span class="glyphicon glyphicon-pencil btn-edit"></span>
                            </a>
                            <a class="btn btn-custom btn-edit btn-sm" data-toggle="tooltip" data-original-title="View" role="button" @click.prevent="view(props.index, props.row)">
                                <span class="glyphicon glyphicon-eye-open btn-edit"></span>
                            </a>
                        </template>
                    </v-client-table>
                </div>
            </div>
            <small>Note*: Warranty and Guarantee are in days.</small>
        </div>
    </div>  
    <!-- Create Modal -->
    <map-popup :url="MaterialUrl+'/store'" :loader="ShowSaveLoader" :brands="Brands" :sub-brands="filteredSubBrands" :form-categories="FormCategories"></map-popup>
    <!-- Update Modal -->
    <update-popup  :url="MaterialUrl+'/update'" :loader="ShowUpdateLoader" :brands="Brands" :sub-brands="filteredUpdateSubBrands" :form-categories="FormCategories" :selected-material="SelectedMaterial"></update-popup>
     <!-- View Modal -->
    <view-popup :brands="Brands" :sub-brands="SubBrands" :form-categories="FormCategories" :selected-material="SelectedMaterial"></view-popup>

</div>
@endsection

@section('dynamicScripts')
<script src="{{ asset('/js/common.js') }}"></script>
<script src="{{ asset('/js/materials/MapMaterials.js') }}"></script>
@endsection
