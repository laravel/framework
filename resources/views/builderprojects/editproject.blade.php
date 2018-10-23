@extends('layouts/master_template')
@section('dynamicStyles')
<link href="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.css') }}" rel="stylesheet" />
<link href="{{ URL::assetUrl('/css/builderproject/common.css') }}" rel="stylesheet" />
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            @if ($ViewType == 'search')
            <div class="box-header with-border">
                <div class="row">
                    <div class="form-group col-md-4">
                        <label for="ProjectSearch">Project</label>
                        <select name="ProjectSearch" id="ProjectSearch" class="form-control">
                            <option value="">Select Project</option>
                            @foreach($Projects as $Key => $Project)
                            <option value="{{$Project['Id']}}" {{ (isset($ProjectDetails) && ($Project['Id'] == $ProjectDetails['Id'])) ? 'selected="selected"' :  ''}}>{{$Project['Name']}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            @endif
            @if ($ViewType == 'Edit' && isset($ProjectDetails))
            <div class="box-header with-border">
                <div class="row">
                    <div class="form-group col-md-4">
                        <label for="ProjectSearch">Project</label>
                        <select name="ProjectSearch" id="ProjectSearch" class="form-control">
                            <option value="">Select Project</option>
                            @foreach($Projects as $Key => $Project)
                            <option value="{{$Project['Id']}}" {{ (isset($ProjectDetails) && ($Project['Id'] == $ProjectDetails['Id'])) ? 'selected="selected"' :  ''}}>{{$Project['Name']}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="box-body ">
                <form action="" method="POST" accept-charset="utf-8" id="UpdateProjectForm">
                    <input type="hidden" name="ProjectId" value="{{$ProjectDetails["Id"]}}">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="ProjectName">Project Name*</label>
                                <input autocomplete="off" type="text" name="ProjectName" placeholder='Ex: Sarika Heights'  id="ProjectName" class="form-control" value="{{$ProjectDetails['Name']}}"/>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="ProjectBuilder">Builder*</label>
                                <select name="ProjectBuilder" id="ProjectBuilder" class="form-control">
                                    <option value="">Select Builder</option>
                                    @foreach($Builders as $Key => $Builder)
                                    <option value="{{$Builder['Id']}}"{{ ($Builder['Id'] == $ProjectDetails['Builder']['Id']) ? 'selected="selected"' :  ''}}>{{$Builder['Name']}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="UpdateAddressLine1">Site Address*</label>
                                <input autocomplete="off" type="text" name="UpdateAddressLine1" placeholder='Ex: S-405, Sarika Heights'  id="UpdateAddressLine1" class="form-control" value="{{$ProjectDetails['address']['AddressLine1']}}"/>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="UpdateAddressLine2">Area</label>
                                <input autocomplete="off" type="text" name="UpdateAddressLine2" placeholder='Ex: Kondapur'  id="UpdateAddressLine2" class="form-control" value="{{$ProjectDetails['address']['AddressLine2']}}"/>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="LandMark">Landmark</label>
                                <input autocomplete="off" type="text" name="LandMark" placeholder='Ex: Inorbit Mall'  id="LandMark" class="form-control" value="{{$ProjectDetails['address']['Landmark']}}"/>

                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="StateEditForm">Select State*</label>
                                <select name="StateEditForm" id="StateEditForm" class="form-control">
                                    <option value="">Select State</option>
                                    @foreach($States as $Key => $State)
                                    <option value="{{$State['Id']}}" {{ ($State['Id'] == $ProjectDetails['address']['StateId']) ? 'selected="selected"' :  ''}}>{{$State['Name']}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="CityEditForm">Select City*</label>
                                <select name="CityEditForm" id="CityEditForm" class="form-control">
                                    <option value="">Select City</option>
                                    @foreach($Cities as $Key => $City)
                                    <option value="{{$City['Id']}}" {{ ($City['Id'] == $ProjectDetails['address']['CityId']) ? 'selected="selected"' :  ''}}>{{$City['Name']}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="ZipcodeEditForm">Pin</label>
                                <input autocomplete="off" type="text" name="ZipcodeEditForm" placeholder='Ex: 500001'  id="ZipcodeEditForm" class="form-control" value="{{$ProjectDetails['address']['Zipcode']}}"/>

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
                                    <option value="{{$UnitType['Id']}}" {{ ($UnitType['Id'] == $ProjectDetails['UnitTypeId']) ? 'selected="selected"' :  ''}}>{{$UnitType['Name']}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <?php $EnquiryUnits = json_decode($ProjectDetails['EnquiryUnitId'], true); ?>
                            <div class="form-group">
                                <label for="Unit">Unit</label>
                                <select name="Unit[]" id="Unit" class="form-control" multiple="multiple">
                                    <option value="">Select Unit</option>
                                    @foreach($Units as $Unit)
                                    @if(!empty($EnquiryUnits) && in_array($Unit["Id"], $EnquiryUnits))
                                    <option value="{{$Unit['Id']}}" selected="selected">{{$Unit['Description']}}</option>
                                    @else
                                    <option value="{{$Unit['Id']}}">{{$Unit['Description']}}</option>
                                    @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="NoOfUnits">No of Units</label>
                                <input autocomplete="off" type="text" name="NoOfUnits" placeholder='Ex: 50'  id="NoOfUnits" class="form-control" value="{{$ProjectDetails['UnitQuantity']}}"/>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-2">
                            <label for="MinSize">Min Size</label>
                            <div class="input-group">
                                <input autocomplete="off" type="text" name="MinSize" placeholder='Ex: 2210'  id="MinSize" class="form-control" value="{{$ProjectDetails['MinimumSize']}}"/>
                                <div class="input-group-addon">Sq ft</div>
                            </div>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="MaxSize">Max Size</label>
                            <div class="input-group">
                                <input autocomplete="off" type="text" name="MaxSize" placeholder='Ex: 1540'  id="MaxSize" class="form-control" value="{{$ProjectDetails['MaximumSize']}}"/>
                                <div class="input-group-addon">Sq ft</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="Price">Price Per Sqft &#8377;</label>
                                <input autocomplete="off" type="text" name="Price" placeholder='Ex: 1999'  id="Price" class="form-control" value="{{$ProjectDetails['PricePerSQFT']}}"/>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="ProjectActive">Active</label>
                                <select name="ProjectActive" id="ProjectActive" class="form-control">
                                    <option value="1" {{ ( $ProjectDetails['IsActive'] == 1) ? 'Selected' : ''}}>Yes</option>
                                    <option value="0" {{ ( $ProjectDetails['IsActive'] != 1) ? 'Selected' : ''}}>No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row" >
                        <div class="form-group col-md-4">
                            <label for="SiteLatitude" class="control-label">Latitude</label>    
                            <input class="form-control" readonly="readonly" name="SiteLatitude" type="text" id="SiteLatitude" aria-invalid="false" value="{{$ProjectDetails['address']['Latitude']}}">
                        </div>
                        <div class="form-group col-md-4">

                            <label for="SiteLongitude" class="control-label">Longitude</label>    
                            <input class="form-control" readonly="readonly" name="SiteLongitude" type="text" id="SiteLongitude" value="{{$ProjectDetails['address']['Longitude']}}">
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
                                <input type="submit" name="submit" value="Update" class="btn btn-primary button-custom" id="UpdateProjectFormSubmit" />
                                <input type="reset" name="reset" value="Undo" class="btn button-custom" id="UpdateProjectFormReset" />
                            </p>
                        </div>
                    </div>
                </form>
                <div class="form-loader hidden" id="UpdateProjectFormLoader">Saving data...</div>
                <div class="mr-tp-10">
                    <label>Mark your Site</label>
                    <div id="customerSiteMap" style="height:400px;"></div>
                </div>
                <div class="pd-tp-15 pd-bt-15">*:&nbsp;<small>Mandatory fields</small></div>
            </div>
            @endif 

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
@if ($ViewType == 'Edit' )
<script src="{{ asset('/js/BuilderProject/ProjectMap.js') }}"></script>
@endif
@endsection