@extends('layouts/master_template')
@section('dynamicStyles')
<link href="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.css') }}" rel="stylesheet" />
<link href="{{ URL::assetUrl('/css/builderproject/common.css') }}" rel="stylesheet" />
@endsection
@section('content')
<div class="row">
    <div class="col-md-12 text-right addNew-block">
        <a class="btn btn-primary button-custom fl-rt AddButton" id="AddBuilderButton" data-toggle="tooltip" title="Click here to Add New Builder" > <i class="fa fa-fw fa-plus-square"></i> New Builder</a>
    </div>
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-body">
                <div class="row mr-tp-15">     
                    <div class="form-group col-sm-12 col-md-5">
                        <select name="Builder" id="Builder" class="form-control">
                            <option value="">Select Builder</option>
                            @foreach($Builders as $Key => $Builder)
                            <option value="{{$Builder['Id']}}" {{ (isset($BuilderDetails) && ($Builder['Id'] == $BuilderDetails['Id'])) ? 'selected="selected"' :  ''}}>{{$Builder['Name']}}</option>
                            @endforeach
                        </select>
                    </div>
                </div> 
                <div class="NewBuilder hidden mr-tp-17">
                    <h4 class="text-blue no-text-transform">New Builder</h4>
                    <form action="" method="POST" accept-charset="utf-8" id="NewBuilderForm">
                        <div class="row">
                            <div class="mr-tp-10 mr-bt-10">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="ProjectBuilder">Builder Name*</label>
                                        <input autocomplete="off" type="text" name="BuilderName" placeholder='Ex: Ramky Group'  id="BuilderName" class="form-control" value=""/>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="BuilderActive">Active</label>
                                        <select name="BuilderActive" id="BuilderActive" class="form-control">
                                            <option value="1" selected="selected">Yes</option>
                                            <option value="0">No</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div> 
                        <div class="row">
                            <div class="col-md-8" style="text-align:center">
                                <p>
                                    <input type="submit" name="" value="Save" class="btn btn-primary button-custom" id="NewBuilderSubmit">
                                    <input type="reset" name="NewBuilderReset" value="Clear" class="btn button-custom" id="NewBuilderReset">
                                </p>
                            </div>
                        </div>
                    </form>
                    <div class="mr-tp-5">*:&nbsp;<small>Mandatory fields</small></div>
                </div>
                @if ($ViewType == 'Edit' && isset($BuilderDetails))
                <div class="UpdateBuilder mr-tp-17">
                    <h4 class="text-blue no-text-transform">Edit Builder</h4>
                    <form action="" method="POST" accept-charset="utf-8" id="UpdateBuilderForm">
                        <input type="hidden" value="{{$BuilderDetails['Id']}}" name="BuilderId">
                        <div class="row">  
                            <div class=" mr-tp-10 mr-bt-10">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="ProjectBuilder">Builder Name*</label>
                                        <input autocomplete="off" type="text" name="BuilderName" placeholder='Ex: Ramky Group'  id="BuilderName" class="form-control" value="{{$BuilderDetails['Name']}}"/>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="BuilderActive">Active</label>
                                        <select name="BuilderActive" id="BuilderActive" class="form-control">
                                            <option value="1" {{ ( $BuilderDetails['IsActive'] == 1) ? 'Selected' : ''}}>Yes</option>
                                            <option value="0" {{ ( $BuilderDetails['IsActive'] != 1) ? 'Selected' : ''}}>No</option>
                                        </select>
                                    </div>
                                </div>
                            </div> 
                        </div>
                        <div class="row">
                            <div class="col-md-8" style="text-align:center">
                                <p>
                                    <input type="submit" name="" value="Update" class="btn btn-primary button-custom" id="BuilderUpdateSubmit">
                                    <input type="reset" name="" value="Undo" class="btn button-custom" id="BuilderUpdateReset">
                                </p>
                            </div>
                        </div>
                    </form>
                    <div class="mr-tp-5">*:&nbsp;<small>Mandatory fields</small></div>
                </div>
                @endif
                <div class="notification-overlay hidden" id="FormLoader">Saving data...</div>
            </div>
        </div>
    </div>
</div>
@include('notificationOverlay')
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.full.min.js') }}"></script>
<script src="{{ URL::assetUrl('/js/common.js') }}"></script>
<script src="{{ URL::assetUrl('/js/NotificationOverlay.js') }}"></script>
<script src="{{ URL::assetUrl('/js/BuilderProject/Builder.js') }}"></script>
@endsection