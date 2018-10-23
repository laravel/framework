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
                @if(empty($DesignItems))
                <div class="callout callout-info mr-tp-6 mr-bt-6">
                    <p>No Items are avaiable. Click here to <a href="{{route('designitems.create')}}" title="Add a Item">Add a Item</a>.</p>
                </div>
                @else
                <div class="row">
                    <div class="col-sm-12 col-md-5">
                        <div class="form-group">
                            <label for="ItemSearch">Item*</label>
                            <select name="ItemSearch" id="ItemSearch" class="form-control">
                                <option value="">Select Item</option>                            
                                @foreach($DesignItems as $Key => $DesignItem)
                                <option value="{{$DesignItem['Id']}}">{{$DesignItem['Name']}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @endif
            @if ($ViewType == 'Edit' && isset($EditDesignItem))
            <div class="box-header with-border">
                <div class="row">   
                    <div class="col-sm-12 col-md-5">
                        <div class="form-group mr-tp-6 mr-bt-6">
                             <label for="Item">Item*</label>
                            <select name="Item" id="Item" class="form-control">
                                <option value="">Select Item</option>                            
                                @foreach($DesignItems as $Key => $DesignItem)
                                <option value="{{$DesignItem['Id']}}"  {{ (isset($EditDesignItem) && ($DesignItem['Id'] == $EditDesignItem['Id'])) ? 'selected="selected"' :  ''}}>{{$DesignItem['Name']}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-body">
                <form action="" method="POST" accept-charset="utf-8" id="UpdateItemForm">
                    <input type="hidden" value="{{$EditDesignItem['Id']}}" name="ItemId" id="ItemId">
                    <div class="row">
                        <div class="mr-tp-16 mr-bt-10">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ItemName">Name*</label>
                                    <input autocomplete="off" type="text" name="ItemName" placeholder='Ex: Dining Table Details'  id="ItemName" class="form-control" value="{{$EditDesignItem['Name'] }}"/>
                                </div>
                            </div>                              
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="no-text-transform">Status</label>
                                    @if($EditDesignItem['IsActive'] == 1)
                                    <?php
                                    $FirstChecked = "checked = 'checked'";
                                    $SecondChecked = "";
                                    ?>
                                    @else
                                    <?php
                                    $FirstChecked = "";
                                    $SecondChecked = "checked = 'checked'";
                                    ?>
                                    @endif
                                    <div class="mr-tp-6">
                                        <input type="radio" name="DesignItemActive" id="DesignItemActiveYes" value="Active" <?php echo $FirstChecked; ?> class="input-radio">
                                        <label for="DesignItemActiveYes" tabindex="0"></label>
                                        <label for="DesignItemActiveYes" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                        <input type="radio" name="DesignItemActive" id="DesignItemActiveNo" value="Inactive" <?php echo $SecondChecked; ?> class="input-radio">
                                        <label for="DesignItemActiveNo" tabindex="0"></label>
                                        <label for="DesignItemActiveNo" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="form-group">
                                    <label for="ItemCode">Code *</label>
                                    <input autocomplete="off" type="text" name="ItemCode" placeholder='Ex: DNT'  id="ItemCode" class="form-control" value="{{$EditDesignItem['Code'] }}"/>
                                </div>
                            </div>
                        </div>    
                    </div>
                    <div class="row">
                        <div class="col-md-8" style="text-align:center">
                            <p>
                                <input type="submit" name="" value="Update" class="btn btn-primary button-custom" id="ItemUpdateSubmit">
                                <input type="reset" name="" value="Undo" class="btn button-custom" id="ItemUpdateReset">
                            </p>
                        </div>
                    </div>
                </form>
            </div>
            @endif
            <div class="form-loader hidden" id="FormLoader">Saving data...</div>
            <div class="form-loader hidden" id="FetchLoader">Fetching data ...</div>
        </div>
    </div>
</div>
@include('notificationOverlay')
@endsection

@section('dynamicScripts')
<script src="{{asset('/js/common.js')}}"></script>
<script src="{{asset('/plugins/select2/select2.min.js')}}"></script>
<script src="{{ URL::assetUrl('/js/NotificationOverlay.js') }}"></script>
<script src="{{asset('js/designitems/edit.js')}}"></script>
@endsection