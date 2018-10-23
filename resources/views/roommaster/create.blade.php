@extends('layouts/master_template')
@section('dynamicStyles')
<link href="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.css') }}" rel="stylesheet" />
@endsection
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <form action="" method="POST" accept-charset="utf-8" id="AddNewRoomForm">
                        <div class="row">                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="RoomName">Name*</label>
                                    <input autocomplete="off" type="text" name="RoomName" id="RoomName" class="form-control" placeholder='Ex: Kitchen'/>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="ShortCode">ShortCode*</label>
                                    <input autocomplete="off" type="text" name="ShortCode" id="ShortCode" class="form-control" placeholder='Ex: KTCH'/>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="SortOrder">Sort Order</label>
                                    <input type="number" step="1" min="1" max="999" name="SortOrder" id="SortOrder" placeholder="Ex: 23" class="form-control"/>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="no-text-transform">Status</label>
                                    <div class="mr-tp-6">
                                        <input type="radio" name="RoomActive" id="RoomActiveYes" checked="checked" value="Active" class="input-radio"/>
                                        <label for="RoomActiveYes" tabindex="0"></label>
                                        <label for="RoomActiveYes" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                        <input type="radio" name="RoomActive" id="RoomActiveNo" value="Inactive" class="input-radio">
                                        <label for="RoomActiveNo" tabindex="0"></label>
                                        <label for="RoomActiveNo" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">                         
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="RoomDescription">Description</label>
                                    <input type="text" name="RoomDescription" id="RoomDescription" class="form-control" placeholder='Ex: Pooja Room' />
                                </div>
                            </div>
                            <div class="col-md-4">
                            <div class="form-group">
                                <label for="RoomUnit">Units</label>
                                <select name="RoomUnit[]" id="RoomUnit" class="form-control" multiple="multiple">
                                    @foreach($EnquiryUnits as $Key => $EnquiryUnit)
                                   <option value="{{$EnquiryUnit["Id"]}}">{{$EnquiryUnit["Name"]}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="RoomComments">Comments</label>
                                    <textarea name="RoomComments" id="RoomComments" class="form-control" rows="2" placeholder='Ex: Pooja Room' style="resize:none"></textarea>
                                </div>
                            </div>
                            
                        </div>
                        <div class="row">
                            <div class="col-md-8 col-sm-12 text-center">
                                <p>
                                    <input type="submit" name="AddNewRoomFormSubmit" value="Save" class="btn btn-primary button-custom" id="AddNewRoomFormSubmit" />
                                    <input type="reset" name="AddNewRoomFormReset" value="Clear" class="btn button-custom" id="AddNewRoomFormReset" />
                                </p>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="form-loader hidden" id="AddNewRoomFormLoader">Saving Room data...</div>
            </div>
        </div>
    </div>
@include('notificationOverlay')
@endsection

@section('dynamicScripts')
    <script src="{{asset('/js/common.js')}}"></script>
    <script src="{{asset('/plugins/select2/select2.min.js')}}"></script>
    <script src="{{ URL::assetUrl('/js/NotificationOverlay.js') }}"></script>
    <script src="{{asset('/js/roommaster/create.js')}}"></script>
@endsection