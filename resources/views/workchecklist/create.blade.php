@extends('layouts/master_template')
@section('content')
<div id="WorkChecklistPage" v-cloak>
    <div class="row">
        <div class="col-md-12 text-right custom-info-block" v-if ="! _.isEmpty(User)">
            <span class="pd-5 text-capitalize user-info">
                <i class="fa fa-user text-info" aria-hidden="true"></i>&nbsp;
                @{{User.Name}}
            </span>
            <span class="pd-5 user-info">
                <i class="fa fa-phone-square text-info" aria-hidden="true"></i>&nbsp;
                @{{User.PrimaryPhone}}@{{(User.SecPhone) ? ' / ' + User.SecPhone : '' }}
            </span>
            <span class="pd-5 user-info"> 
                <i class="fa fa-envelope-square text-info" aria-hidden="true"></i>&nbsp;
                @{{User.Email}}
            </span>
        </div>
        <div class="col-md-12">
            <div class="box box-primary">
                @if($ViewType === "Select")
                <div class="box-body">
                    <div class="row pd-lt-15 pd-rt-15">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="Project">Project*</label>
                                <select name="Project" id="Project" class="form-control">
                                    <option value="">Select Project</option>
                                    @foreach($Projects as $Project)
                                    <option value="{{$Project['Id']}}">{{$Project['Name']}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="Type">Checklist Type*</label>
                                <select name="Type" id="Type" class="form-control">
                                    <option value="">Select Type</option>
                                    <option v-for="Type in ChecklistTypes" :value="Type.Id">
                                        @{{ Type.Name }}
                                    </option> 
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                <div class="box-header with-border">
                    @if($ViewType !== "Select")
                    <div id="FormContainer">
                        @if(!$isFormEmpty)
                        {!! form($form) !!}
                        @else
                        <h3 class="text-center">Coming Soon...</h3>
                        @endif
                        <div class="overlay" v-if="ShowOverlay">
                            <div class="large loader"></div>
                            <div class="loader-text">Saving Data</div>
                        </div>
                        <div id="NotificationArea">
                            <div class="alert alert-dismissible hidden"></div>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="overlay" v-if="ShowFetchOVerlay">
                    <div class="large loader"></div>
                    <div class="loader-text">Fetching Types</div>
                </div>
            </div>
        </div>
    </div>
    <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
</div>
@endsection

@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl('/plugins/datepicker/bootstrap-datepicker.min.css') }}" />
<link rel="stylesheet" href="{{ URL::assetUrl('/plugins/timepicker/bootstrap-timepicker.min.css') }}">
<link rel="stylesheet" href="{{ asset('/plugins/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/workchecklist/checklist.css') }}">
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl('/plugins/datepicker/bootstrap-datepicker.min.js') }}"></script>
<script src="{{ URL::assetUrl('/plugins/timepicker/bootstrap-timepicker.min.js') }}"></script>
<script src="{{ asset('js/common.js') }}"></script>
<script src="{{ asset('js/workchecklist/checklist.js') }}"></script>
@endsection