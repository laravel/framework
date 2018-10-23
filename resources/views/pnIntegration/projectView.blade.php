@extends('layouts/master_template')
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <div class="row">
                    <div class="col-md-6">
                        <h3 class="mr-tp-12">{{$ProjectData->Name}}</h3>
                        <p>{{$ProjectData->QuickEstimate->Name}} ({{$ProjectData->QuickEstimate->ReferenceNumber}})</p>
                        <p>Created on {{$CreatedDate}}</p>
                    </div>
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-12">
                       <h4>Description</h4>
                       <p>{{$ProjectData->Description?$ProjectData->Description:"N/A"}}</p>
                    </div>
                </div>
                <h4 class="no-text-transform mr-tp-10 mr-bt-20">Roles</h4>
                <div class="row">
                @foreach($UserNRoles as $Value)
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{$Value["RoleTitle"]}}</label>
                        <p>{{$Value["UserName"]}}</p>
                    </div>
                </div>
                @endforeach
                </div>
                <small>* N/A: Data Not Available</small>
            </div>
        </div>        
    </div>
</div>
@endsection

@section('dynamicStyles')
@endsection