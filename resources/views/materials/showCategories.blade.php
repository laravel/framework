@extends('layouts/master_template')

@section('content')
<div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="row">
                        @foreach($Categories as $Value)
                        <div class="col-md-2">
                            <div class="small-box bg-blue">
                                <div class="inner">
                                    <p class="category-name">{{$Value["Name"]}}</p>
                                    <p class="small-box-footer actions-para">
                                        <a href="{{route($Value["Slug"])}}">
                                            <i class="fa fa-plus-square" aria-hidden="true"></i>&nbsp; Add
                                        </a>
                                        <a class="mr-lt-8" href="{{route('materials.list', ['category'=> $Value["Slug"]])}}">
                                            <i class="fa fa-list-ul" aria-hidden="true"></i>&nbsp; List
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>  
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicStyles')
<link href="{{ URL::assetUrl('/css/materials/common.css') }}" rel="stylesheet" />
@endsection
