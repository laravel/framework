@extends('layouts/master_template')

@section('dynamicStyles')
<link href="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.css') }}" rel="stylesheet" />
<link href="{{ URL::assetUrl('/css/materials/common.css') }}" rel="stylesheet" />
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary clearfix">
            <div class=" box-header with-border">
                <div class="row">
                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label for="">Category</label>
                            <select class="form-control" name="laminates" id="Laminates" onchange="location = this.value;">   
                                <option value="">Choose a Category</option>
                                @foreach($catagories as $Key => $category)
                                <option value='{{ route($category->Slug) }}' {{ $category->Slug === $slug ? 'selected="selected' : '' }}>{{ $category->Name}}</option>
                                @endforeach
                            </select> 
                        </div>
                    </div>
                </div>
            </div>
            <div id="FormContainer">
                {!! form($form) !!}
            </div>
            <div id="NotificationArea">
                <div class="alert alert-dismissible hidden"></div>
            </div>
        </div>
        <div class="form-loader hidden" id="SurfaceCoatingFormLoader"></div>
    </div>
</div>
@include('notificationOverlay')
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.js') }}"></script>
<script src="{{ URL::assetUrl('/js/common.js') }}"></script>
<script src="{{ URL::assetUrl('/js/materials/common.js') }}"></script>
<script src="{{ URL::assetUrl('/js/materials/wallpaper.js') }}"></script>
<script src="{{ URL::assetUrl('/js/materials/masters.js') }}"></script>
@endsection
