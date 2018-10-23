@extends('layouts/master_template')

@section('dynamicStyles')
<link href="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.css') }}" rel="stylesheet" />
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            @if ($ViewType == 'search')
            <div class="box-body">
                @if(empty($Rooms))
                <div class="callout callout-info mr-tp-6 mr-bt-6">
                    <p>No Rooms are avaiable. Click here to <a href="{{route('rooms.create')}}" title="Add a Room">Add a Room</a>.</p>
                </div>
                @else
                <div class="row">
                    <div class="col-md-4 col-sm-12">
                        <div class="form-group">
                            <label for="RoomCode">Room*</label>
                            <select name="RoomCode" id="RoomCode" class="form-control">
                                <option value="">Select an Room</option>
                                @foreach($Rooms as $Key => $Room)
                                <option value="{{$Room->Id}}">{{$Room->Name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @endif
            @if ($ViewType == 'Edit' && isset($RoomDetails))
            <div class="box-header with-border">
                <div class="row">  
                    <div class="col-md-4 col-sm-12">
                        <div class="form-group mr-tp-6 mr-bt-6">
                            <select name="RoomSearch" id="RoomSearch" class="form-control">
                                <option value="">Select an Room</option>
                                @foreach($Rooms as $Key => $Room)
                                @if($RoomDetails->Name === $Room->Name)
                                <option value="{{$Room->Id}}" selected>{{$Room->Name}}</option>
                                @else
                                <option value="{{$Room->Id}}">{{$Room->Name}}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-body">
                <form action="" method="POST" accept-charset="utf-8" id="EditRoomForm">
                    <input type="hidden" name="RoomId" value="{{$RoomDetails->Id}}" id="RoomId">
                    <div class="row">                     
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="RoomName">Room Name*</label>
                                <input autocomplete="off" type="text" name="RoomName" id="RoomName" class="form-control" placeholder='Ex: Kitchen' value="{{$RoomDetails->Name}}"/>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="ShortCode">Short Code*</label>
                                <input autocomplete="off" type="text" name="ShortCode" id="ShortCode" class="form-control" placeholder='Ex: KTH' value="{{$RoomDetails->ShortCode}}"/>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="SortOrder">Sort Order</label>
                                <input type="number" step="1" min="1" max="999" name="SortOrder" id="SortOrder" placeholder="Ex: 23" class="form-control" value="{{$RoomDetails->SortOrder}}"/>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="no-text-transform">Status</label>
                                <div class="mr-tp-6">
                                    @if ($RoomDetails->IsActive == 1)
                                    <input type="radio" name="RoomActive" id="RoomActiveYes" value="Active" checked="checked" class="input-radio"/>
                                    @else
                                    <input type="radio" name="RoomActive" id="RoomActiveYes" value="Active" class="input-radio"/>
                                    @endif
                                    <label for="RoomActiveYes" tabindex="0"></label>
                                    <label for="RoomActiveYes" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                    @if ($RoomDetails->IsActive == 0)
                                    <input type="radio" name="RoomActive" id="RoomActiveNo" value="Inactive" checked="checked" class="input-radio"/>
                                    @else
                                    <input type="radio" name="RoomActive" id="RoomActiveNo" value="Inactive" class="input-radio"/>
                                    @endif
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
                                <input type="text" name="RoomDescription" id="RoomDescription" class="form-control" rows="3" placeholder='Ex: Pooja Room' value="{{$RoomDetails->Description}}" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="RoomUnit">Units</label>
                                <select name="RoomUnit[]" id="RoomUnit" class="form-control" multiple="multiple">
                                    @foreach($EnquiryUnits as $Key => $EnquiryUnit)
                                    @if(in_array($EnquiryUnit['Id'],$RoomDetails['EnquiryUnit']))
                                    <option value="{{$EnquiryUnit["Id"]}}" selected>{{$EnquiryUnit["Name"]}}</option>
                                    @else
                                    <option value="{{$EnquiryUnit["Id"]}}">{{$EnquiryUnit["Name"]}}</option>
                                    @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="RoomComments">Comments</label>
                                <textarea name="RoomComments" id="RoomComments" class="form-control" rows="2" placeholder='Ex: Pooja Room' style="resize:none">{{$RoomDetails->Comment}}</textarea>
                            </div>
                        </div>                       
                    </div>
                    <div class="row">
                        <div class="col-md-8 text-center">
                            <p>
                                <input type="submit" name="EditRoomFormSubmit" value="Update" class="btn btn-primary button-custom" id="EditRoomFormSubmit" />
                                <input type="reset" name="EditRoomFormReset" value="Undo Changes" class="btn button-custom" id="EditRoomFormReset" />
                            </p>
                        </div>
                    </div>
                </form>
                <div class="form-loader hidden" id="EditRoomFormLoader">Saving Room data...</div>
                <div class="form-loader hidden" id="EditRoomFetchLoader">Fetching Room data...</div>               
            </div>
            @endif 
        </div>
    </div>
</div>
@include('notificationOverlay')
@endsection

@section('dynamicScripts')
<script src="{{asset('/js/common.js')}}"></script>
<script src="{{asset('/plugins/select2/select2.min.js')}}"></script>
<script src="{{ URL::assetUrl('/js/NotificationOverlay.js') }}"></script>
<script src="{{asset('js/roommaster/edit.js')}}"></script>
@endsection