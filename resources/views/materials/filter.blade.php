@extends('layouts/master_template')
@section('dynamicStyles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/css/bootstrap-datepicker3.min.css" rel="stylesheet"/>
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
<link href="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.css') }}" rel="stylesheet" />
<link rel="stylesheet" href="{{ URL::assetUrl("/css/materials/common.css")}}">
<link rel="stylesheet" href="{{ URL::assetUrl("/css/magnific-popup.css")}}">
@endsection
@section('content')
<div id="MaterialMaster" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border filter-view">
                    <form id="MaterialSearchForm" method="POST" action="{{ route('search.materials')}}">
                        <div id="AllFilterView">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="Category">Material Category</label>
                                    <select class="form-control" name="Category" id="Category">   
                                        <option value="">Choose a Category</option>
                                        <option v-for="category in Categories" :value="category.Id" :selected="category.Slug==Slug">
                                            @{{ category.Name }}
                                        </option>
                                    </select> 
                                </div> 
                                <div class="col-md-3">
                                    <label for="SubBrand">Brand / SubBrand</label>
                                    <select class="form-control" name="SubBrand" id="SubBrand">   
                                        <option value="">Choose a Sub Brand</option>
                                        <option v-for="(subBrand,key) in SubBrands" :value="key">
                                            @{{ subBrand }}
                                        </option>
                                    </select> 
                                </div> 
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="DesignNumber">Design Number</label>
                                        <input type="text" pattern="[0-9]+" class="form-control" name="DesignNumber" id="DesignNumber" value="" placeholder="Ex: 5526"/>
                                    </div>
                                </div>
                                 <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="DesignName">Design Name</label>
                                        <input type="text" class="form-control" name="DesignName" id="DesignName" value="" placeholder="Ex: Jane Jade"/>
                                    </div>
                                </div>
                            </div>
                            <div class="row"> 
                                <div class="col-md-3">
                                    <label for="SurfaceCategory">Surface Category</label>
                                    <select class="form-control" name="SurfaceCategory" id="SurfaceCategory">   
                                        <option value="">Select Category</option>
                                        <option v-for="surfaceMatcatg in SurfaceMatcatgs" :value="surfaceMatcatg.Id">
                                            @{{ surfaceMatcatg.Name }}
                                        </option>
                                    </select> 
                                </div> 
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="CreatedDate">Created Date</label>
                                        <div class="input-group">
                                            <div class="input-group-addon">
                                                <i class="fa fa-calendar"></i>
                                            </div>
                                            <input type="text" name="CreatedDate" id="CreatedDate" class="form-control date-picker" placeholder="Ex: 01-Jan-2017" readonly="true" />
                                            <div class="input-group-btn">
                                                <button type="button" class="btn btn-addon dropdown-toggle" id="CreatedDateButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="fa fa-filter"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-right" id="CreatedDateFilters">
                                                    <li class="active" data-filter-name="eq">
                                                        <a href="#">
                                                            <b class="mr-rt-6">=</b> equal to
                                                        </a>
                                                    </li>
                                                    <li data-filter-name="lt">
                                                        <a href="#">
                                                            <b class="mr-rt-6">&lt;</b> less than
                                                        </a>
                                                    </li>
                                                    <li data-filter-name="gt">
                                                        <a href="#">
                                                            <b class="mr-rt-6">&gt;</b> greater than
                                                        </a>
                                                    </li>
                                                    <li data-filter-name="le">
                                                        <a href="#">
                                                            <b class="mr-rt-6">&le;</b> less than or equal to
                                                        </a>
                                                    </li>
                                                    <li data-filter-name="ge">
                                                        <a href="#">
                                                            <b class="mr-rt-6">&ge;</b> greater than or equal to
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="UpdatedDate">Updated Date</label>
                                        <div class="input-group">
                                            <div class="input-group-addon">
                                                <i class="fa fa-calendar"></i>
                                            </div>
                                            <input type="text" name="UpdatedDate" id="UpdatedDate" class="form-control date-picker" placeholder="Ex: 01-Jan-2017" readonly="true" />
                                            <div class="input-group-btn">
                                                <button type="button" class="btn btn-addon dropdown-toggle" id="UpdatedDateButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="fa fa-filter"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-right" id="UpdatedDateFilters">
                                                    <li class="active" data-filter-name="eq">
                                                        <a href="#">
                                                            <b class="mr-rt-6">=</b> equal to
                                                        </a>
                                                    </li>
                                                    <li data-filter-name="lt">
                                                        <a href="#">
                                                            <b class="mr-rt-6">&lt;</b> less than
                                                        </a>
                                                    </li>
                                                    <li data-filter-name="gt">
                                                        <a href="#">
                                                            <b class="mr-rt-6">&gt;</b> greater than
                                                        </a>
                                                    </li>
                                                    <li data-filter-name="le">
                                                        <a href="#">
                                                            <b class="mr-rt-6">&le;</b> less than or equal to
                                                        </a>
                                                    </li>
                                                    <li data-filter-name="ge">
                                                        <a href="#">
                                                            <b class="mr-rt-6">&ge;</b> greater than or equal to
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mr-tp-10">
                            <div class="col-md-10 col-sm-12 text-center">
                                <input type="submit" class="btn btn-primary button-custom mr-bt-10" value="Search" id="MaterialSearchFormSubmit" />
                                <input type="reset" class="btn button-custom mr-bt-10" value="Clear" id="MaterialSearchFormReset" />
                            </div>
                        </div>
                    </form>
                </div>
                <div class="search-result hidden"></div>
                <div class="form-overlay hidden" id="SearchFormOverlay">
                    <div class="large loader"></div>
                    <div class="loader-text">Fetching Results...</div>
                </div>
            </div>
            <div id="NotificationArea"></div> 
        </div>
    </div>
</div>

@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl('/js/magnific-popup.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/js/bootstrap-datepicker.min.js"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.full.min.js') }}"></script>
<script src="{{ URL::assetUrl('/js/materials/search.js') }}"></script>
<script src="{{ URL::assetUrl('/js/materials/masters.js') }}"></script>
<script src="{{ URL::assetUrl('/js/common.js') }}"></script>
@endsection
