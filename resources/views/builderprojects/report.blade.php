@extends('layouts/master_template')
@section('dynamicStyles')
<link href="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.css') }}" rel="stylesheet" />
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
<link href="{{ URL::assetUrl('/css/builderproject/common.css') }}" rel="stylesheet" />
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">
        @if(!$Builders)
        <div class="callout callout-info">
            <div class="row">
                <div class="col-md-10 col-sm-6 col-xs-6 text-center"><span>No Projects found, Please create </span>
                    <a href="{{URL::route('project.new')}}" class="text-hover">New Project</a></div>
            </div>
        </div>
        @else
        <div class="box box-primary">
            <div class="box-header with-border mr-tp-15">
                <div class="row">     
                    <div class="form-group col-sm-12 col-md-5">
                        <select name="Builder" id="Builder" class="form-control">
                            <option value="">Select Builder</option>
                            @foreach($Builders as $Key => $Builder)
                            <option value="{{$Builder['Id']}}">{{$Builder['Name']}}</option>
                            @endforeach
                        </select>
                    </div>
                </div> 
            </div>
            <div class="box-body hidden" id="list-table">
                <div class="box-body table-responsive no-padding">
                    <table class="table table-bordered table-hover" id="ProjectReportTable">
                        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                            <tr>
                                <th class="text-center text-vertical-align pd-10">S.No</th>
                                <th class="text-center text-vertical-align">Project Name</th>
                                <th class="text-center text-vertical-align">Address</th>
                                <th class="text-center text-vertical-align pd-lt-15 pd-rt-15">IsActive</th>
                                <th class="text-center text-vertical-align pd-10">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div> 
            </div>
            <div class="form-overlay hidden" id="SearchFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Fetching Data...</div>
            </div>
        </div>
        <div id="NotificationArea"></div> 
        </div>
        @endif
    </div>
</div>
@endsection
@section('dynamicScripts')
<script src="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.full.min.js') }}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ URL::assetUrl('/js/common.js') }}"></script>
<script src="{{ URL::assetUrl('/js/BuilderProject/ProjectReport.js') }}"></script>
@endsection