@extends('layouts/master_template')

@section('content')
<div id="carousel" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary pd-bt-10">
                <div class="box-header with-border pd-bt-0">
                    <div class="row">     
                        <div class="col-sm-12 col-md-1 pd-rt-0">
                            <label>Select Item: </label>
                        </div>
                        <div class="col-sm-12 col-md-2 pd-lt-0">
                            <select name="Filter" id="Filter" class="form-control" @change="onChangeEvent()" v-model='selected'>
                                <option v-for="filter in Filter" :value="filter.toLowerCase()">@{{filter}}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="box-body pd-tp-0">
                    <div class="pd-tp-14" v-if="Data.length === 0"> 
                        <div class="callout callout-info">
                            <p><i class="fa fa-fw fa-info-circle" aria-hidden="true"></i> No Data available.</p>
                        </div>
                    </div>
                    <!-- Vue table component -->
                    <v-client-table :columns="columns" :data="Data" :options="options" v-else>
                        <span slot="Id" slot-scope="props">@{{props.index}}</span>
                        <template slot="Action" slot-scope="props">
                            <a class="btn btn-custom btn-edit btn-sm mr-rt-3" data-toggle="tooltip" data-original-title="Map" role="button" @click.prevent="map(props.row.Id)">
                                <span class="glyphicon glyphicon-pencil btn-edit"></span>
                            </a>
                        </template>
                    </v-client-table>
                </div>
                <small>* N/A: Data Not Available</small>
            </div>
        </div>
    </div> 
    <div class="notification-overlay" :class="{hidden: Loader}" id="Loader">
        <div class="large loader"></div>
       <div class="loader-text">@{{LoaderMessage}}</div>
   </div>
    <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
     <!-- Map Modal -->
    <map-popup v-if="SelectedItem" :filter="selected" :url="Url" :loader="UpdateLoader" :selected-item="SelectedItem" :detail-est-items="DetailESTItems"></map-popup>

</div>
@endsection

@section('dynamicScripts')
<script src="{{ asset('/js/qeitems/qeitemmap.min.js') }}"></script>
@endsection

@section('dynamicStyles')
<link href="{{ asset('/css/materials/vueTable.css') }}" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/vendor/select2.min.css') }}">
<style>
    .select2-container--default .select2-search--inline .select2-search__field {
      width: 100% !important;
    }
</style>
@endsection
