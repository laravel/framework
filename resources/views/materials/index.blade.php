@extends('layouts/master_template')

@section('content')
<div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-4 col-sm-12">
                            <div class="form-group">
                                <label for="">Category*</label>
                                <select class="form-control" name="laminates" id="Laminates" onchange="location = this.value;">   
                                    <option value="">Choose a Category</option>
                                    @foreach($catagories as $Key => $category)
                                    <option value='{{ route($category->Slug) }}'>{{ $category->Name}}</option>
                                    @endforeach
                                </select> 
                            </div>
                        </div>
                    </div>  
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicStyles')
<link rel="stylesheet" href="{{asset('/plugins/select2/select2.min.css')}}">
@endsection

@section('dynamicScripts')
<script src="{{asset('/plugins/select2/select2.min.js')}}"></script>
<script src="{{asset('js/materials/masters.js')}}"></script>
@endsection
