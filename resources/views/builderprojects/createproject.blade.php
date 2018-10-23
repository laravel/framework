@extends('layouts/master_template')
@section('dynamicStyles')
<link href="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.css') }}" rel="stylesheet" />
<link href="{{ URL::assetUrl('/css/builderproject/common.css') }}" rel="stylesheet" />
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-body">
                <form action="" method="POST" accept-charset="utf-8" id="ProjectForm">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="ProjectName">Project Name*</label>
                                <input autocomplete="off" type="text" name="ProjectName" placeholder='Ex: Sarika Heights'  id="ProjectName" class="form-control" value=""/>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="ProjectBuilder">Builder*</label>
                                <select name="ProjectBuilder" id="ProjectBuilder" class="form-control">
                                    <option value="">Select Builder</option>
                                    @foreach($Builders as $Key => $Builder)
                                    <option value="{{$Builder['Id']}}">{{$Builder['Name']}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="AddressLine1">Site Address*</label>
                                <input autocomplete="off" type="text" name="AddressLine1" placeholder='Ex: S-405, Sarika Heights'  id="AddressLine1" class="form-control" value=""/>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="AddressLine2">Area</label>
                                <input autocomplete="off" type="text" name="AddressLine2" placeholder='Ex: Kondapur'  id="AddressLine2" class="form-control" value=""/>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="LandMark">Landmark</label>
                                <input autocomplete="off" type="text" name="LandMark" placeholder='Ex: Inorbit Mall'  id="LandMark" class="form-control" value=""/>

                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="State">Select State*</label>
                                <select name="State" id="State" class="form-control">
                                    <option value="">Select State</option>
                                    @foreach($States as $Key => $State)
                                    <option value="{{$State['Id']}}">{{$State['Name']}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="City">Select City*</label>
                                <select name="City" id="City" class="form-control">
                                    <option value="">Select City</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="Zipcode">Pin</label>
                                <input autocomplete="off" type="text" name="Zipcode" placeholder='Ex: 500001'  id="Zipcode" class="form-control" value=""/>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="UnitType">Unit Type</label>
                                <select name="UnitType" id="UnitType" class="form-control">
                                    <option value="">Select Unit Type</option>
                                    @foreach($UnitTypes as $UnitType)
                                    <option value="{{$UnitType['Id']}}">{{$UnitType['Name']}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="Unit">Unit</label>
                                <select name="Unit[]" id="Unit" class="form-control" multiple="multiple">
                                    <option value="">Select Unit</option>
                                    @foreach($Units as $Unit)
                                    <option value="{{$Unit['Id']}}">{{$Unit['Description']}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="NoOfUnits">No of Units</label>
                                <input autocomplete="off" type="text" name="NoOfUnits" placeholder='Ex: 50'  id="NoOfUnits" class="form-control" value=""/>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-2">
                            <label for="MinSize">Min Size</label>
                            <div class="input-group">
                                <input autocomplete="off" type="text" name="MinSize" placeholder='Ex: 2210'  id="MinSize" class="form-control" value=""/>
                                <div class="input-group-addon">Sq ft</div>
                            </div>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="MaxSize">Max Size</label>
                            <div class="input-group">
                                <input autocomplete="off" type="text" name="MaxSize" placeholder='Ex: 1540'  id="MaxSize" class="form-control" value=""/>
                                <div class="input-group-addon">Sq ft</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="Price">Price Per Sqft &#8377;</label>
                                <input autocomplete="off" type="text" name="Price" placeholder='Ex: 1999.99'  id="Price" class="form-control" value=""/>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="ProjectActive">Active</label>
                                <select name="ProjectActive" id="ProjectActive" class="form-control">
                                    <option value="1" selected="selected">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row" >
                        <div class="form-group col-md-4">
                            <label for="SiteLatitude" class="control-label">Latitude</label>    
                            <input class="form-control" readonly="readonly" name="SiteLatitude" type="text" id="SiteLatitude" aria-invalid="false">
                        </div>
                        <div class="form-group col-md-4">

                            <label for="SiteLongitude" class="control-label">Longitude</label>    
                            <input class="form-control" readonly="readonly" name="SiteLongitude" type="text" id="SiteLongitude">
                        </div>
                        <div class="form-group col-md-4">
                            <label></label>
                            <div class="form-control-static">
                                <a href="#" id="ResetMap">
                                    <i class="fa fa-repeat" aria-hidden="true"></i>&nbsp;&nbsp;Reset Map</a>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mr-tp-10" style="text-align:center">
                            <p>
                                <input type="submit" name="" value="Save" class="btn btn-primary button-custom" id="ProjectFormSubmit" />
                                <input type="reset" name="" value="Clear" class="btn button-custom" id="ProjectFormReset" />
                            </p>
                        </div>
                    </div>
                </form>
                <div class="form-loader hidden" id="ProjectFormLoader">Saving data...</div>
                <div class="mr-tp-10">
                    <label>Mark your Site</label>
                    <div id="customerSiteMap" style="height:400px;"></div>
                </div>
                <div class="pd-tp-15 pd-bt-15">*:&nbsp;<small>Mandatory fields</small></div>
            </div>
        </div>
    </div>
</div>
@include('notificationOverlay')
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.full.min.js') }}"></script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}"></script>
<script src="{{ URL::assetUrl('/js/common.js') }}"></script>
<script src="{{ URL::assetUrl('/js/NotificationOverlay.js') }}"></script>
<script src="{{ URL::assetUrl('/js/BuilderProject/Project.js') }}"></script>
<script src="{{ asset('/js/BuilderProject/ProjectMap.js') }}"></script>
@endsection