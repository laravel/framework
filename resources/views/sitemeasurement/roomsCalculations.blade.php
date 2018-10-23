@extends('layouts/master_template')

@section('dynamicStyles')
<link rel="stylesheet" href="{{ asset('css/sitemeasurement/roomcals.css') }}">
@endsection

@section('content')
<div id="RoomsAreaCalculationsPage" v-cloak>
    <div class="row">
        <user-information :user-info="userInfo"></user-information>
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Notes</label>
                                <div v-html="formattedNotes"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Status</label>
                                <div>  
                                    <span :class="'label label-' + statusLabels[status.code]">@{{status.text}}</span> 
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-md-offset-1 text-right">
                            <site-information :site-info="siteInfo"></site-information>
                        </div>
                    </div>    
                </div>
                <div class="box-header with-border" v-if="rooms.length === 0">
                    <div class="callout callout-info mr-8">
                        <p>Rooms has been not added for this Site Measurement.</p>
                    </div>
                </div>
                <div id="SiteMeasureListBox" v-else>
                    <room-area-results :rooms="rooms"></room-area-results>
                </div>
                <div class="box-header with-border">
                    <div class="row">
                        <div class="col-md-12">
                            <button name="Back" class="btn btn-primary btn-custom fl-rt back-btn" onclick="window.location ='{{route('sitemeasurement.list')}}'">
                              Back
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <small>*Red colored boxes shows invalid values.</small>
        </div>
    </div>
</div>
@endsection

@section('dynamicScripts')
<script src="{{asset('/js/sitemeasurement/roomcals.js')}}"></script>
@endsection