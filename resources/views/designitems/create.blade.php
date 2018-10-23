@extends('layouts/master_template')
@section('dynamicStyles')
<link href="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.css') }}" rel="stylesheet" />
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-body">
                <form action="" method="POST" accept-charset="utf-8" id="NewItemForm">
                    <div class="row">
                        <div class="mr-tp-16 mr-bt-10">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ItemName">Name*</label>
                                    <input autocomplete="off" type="text" name="ItemName" placeholder='Ex: Dining Table Details'  id="ItemName" class="form-control" value=""/>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="no-text-transform">Status</label>
                                    <div class="mr-tp-6">
                                        <input type="radio" name="ItemActive" id="ItemActiveYes" checked="checked" value="Active" class="input-radio"/>
                                        <label for="ItemActiveYes" tabindex="0"></label>
                                        <label for="ItemActiveYes" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                        <input type="radio" name="ItemActive" id="ItemActiveNo" value="Inactive" class="input-radio">
                                        <label for="ItemActiveNo" tabindex="0"></label>
                                        <label for="ItemActiveNo" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="form-group">
                                    <label for="ItemCode">Code*</label>
                                    <input autocomplete="off" type="text" name="ItemCode" placeholder='Ex: DNT'  id="ItemCode" class="form-control" value=""/>
                                </div>
                            </div>
                        </div>    
                    </div>
                    <div class="row">
                        <div class="col-md-8" style="text-align:center">
                            <p>
                                <input type="submit" name="" value="Save" class="btn btn-primary button-custom" id="NewItemSubmit">
                                <input type="reset" name="NewItemReset" value="Clear" class="btn button-custom" id="NewItemrReset">
                            </p>
                        </div>
                    </div>
                </form>
            </div>
            <div class="form-loader hidden" id="FormLoader">Saving data...</div>
        </div>
    </div>
</div>
@include('notificationOverlay')
@endsection

@section('dynamicScripts')
<script src="{{asset('/js/common.js')}}"></script>
<script src="{{asset('/plugins/select2/select2.min.js')}}"></script>
<script src="{{ URL::assetUrl('/js/NotificationOverlay.js') }}"></script>
<script src="{{asset('/js/designitems/create.js')}}"></script>
@endsection