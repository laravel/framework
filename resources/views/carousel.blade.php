@extends('layouts/master_template')

@section('dynamicStyles')
<link href="{{ asset('/css/materials/vueTable.css') }}" rel="stylesheet"/>
<link rel="stylesheet" href="{{ URL::assetUrl("/css/magnific-popup.css")}}">
<link href="{{ asset('/css/carousel/carousel.css') }}" rel="stylesheet"/>
@endsection

@section('content')
<div id="carousel" v-cloak>
    <div class="col-md-12 text-right">
        <a class="btn btn-primary button-custom fl-rt AddButton" data-toggle="tooltip" title="Click here to Add" @click.prevent="add"> <i class="fa fa-fw fa-plus-square"></i> Add</a>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="pd-tp-14" v-if="Data.length === 0"> 
                        <div class="callout callout-info">
                            <p><i class="fa fa-fw fa-info-circle" aria-hidden="true"></i> No Data available.</p>
                        </div>
                    </div>
                    <!-- Vue table component -->
                    <v-client-table :columns="columns" :data="sortCarouselData" :options="options" v-else>
                        <span slot="Id" slot-scope="props">@{{props.index}}</span>
                        <template slot="Source" slot-scope="props">
                            <a :href="URL+props.row.Source" class="img-popup">
                                <img class="img-list" :src="URL+props.row.Source">
                            </a>
                        </template>
                        <template slot="Actions" slot-scope="props">
                            <a class="btn btn-custom btn-edit btn-sm mr-rt-3" data-toggle="tooltip" data-original-title="Edit" role="button" @click.prevent="edit(props.row.key)">
                                <span class="glyphicon glyphicon-pencil btn-edit"></span>
                            </a>
                            <a class="btn btn-custom btn-edit btn-sm" data-toggle="tooltip" data-original-title="View" role="button" @click.prevent="view(props.row.key)">
                                <span class="glyphicon glyphicon-eye-open btn-edit"></span>
                            </a>
                             <a class="btn btn-custom btn-edit btn-sm" data-toggle="tooltip" data-original-title="Delete" role="button" @click.prevent="deleteCarousel(props.row.key)">
                                <span class="glyphicon glyphicon-trash btn-edit"></span>
                            </a>
                        </template>
                    </v-client-table>
                </div>
            </div>
        </div>
    </div> 
    <div class="notification-overlay" :class="{hidden: Loader}" id="Loader">
        <div class="large loader"></div>
       <div class="loader-text">@{{LoaderMessage}}</div>
   </div>
    <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
        
    <!-- Create Modal -->
    <add-popup :url="FormUrl+'/store'" :loader="SaveLoader" :length="CarouselLength"></add-popup>
    <!-- Update Modal -->
    <update-popup v-if="SelectedCarousel" :url="FormUrl+'/update'" :loader="UpdateLoader" :length="CarouselLength" :selected-carousel="SelectedCarousel"></update-popup>
    <!-- View Modal -->
    <view-popup v-if="SelectedCarousel" :url="URL" :selected-carousel="SelectedCarousel"></view-popup>

    <div class="modal fade" id="ConfirmationModal" tabindex="-1" role="dialog" >
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Confirm</h4>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary pull-left" @click="deleteComment">Yes</button>
                    <button type="button" class="btn pull-left mr-lt-10" data-dismiss="modal">No</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ asset('/js/carousel/carousel.js') }}"></script>
@endsection
