@extends('layouts/master_template')

@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
<link href="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.css') }}" rel="stylesheet" />
<link rel="stylesheet" href="{{ URL::assetUrl("/css/sitemeasurement/report.css")}}">
@endsection

@section('content')
<div id="SMReportsPage" v-cloak>
    @if(auth()->user()->isSupervisor())
    <div class="col-md-12 text-right addNew-block">
        <a class="btn btn-primary button-custom fl-rt AddButton" href="{{ route('sitemeasurement.add') }}" data-toggle="tooltip" title="Click here to Add New Measurement" > <i class="fa fa-fw fa-plus-square"></i> New Site Measurement</a>
    </div>
    @endif
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <form id="SiteMeasrSearchForm" method="POST" action="">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="Project">Project</label>
                                    <select name="Project" id="Project" class="form-control">
                                        <option value="">Select Project</option>
                                        @foreach($SiteProjects as $Project)
                                        <option value="{{$Project->Id}}">{{$Project->Name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @if(isset($Users))
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="CreatedBy">Created By</label>
                                    <select name="CreatedBy" id="CreatedBy" class="form-control">
                                        <option value="">Select User</option>
                                        @foreach($Users as $User)
                                        <option value="{{$User->Users->Id}}">{{$User->Users->Email}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="row mr-tp-10">
                            <div class="col-md-4">
                                <input type="submit" class="btn btn-primary button-custom mr-bt-10" value="Search" id="SiteMeasureSubmit" />
                                <input type="reset" class="btn button-custom mr-bt-10" value="Clear" id="SiteMeasureReset" />
                            </div>
                        </div>
                    </form>
                </div>
                <div class="box-body no-padding hidden pd-bt-10" id="SiteMeasureListBox">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="SiteMeasurementReport">
                            <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                                <tr>
                                    <th class="text-center text-vertical-align pd-10">S.No</th>
                                    <th class="text-center text-vertical-align">Project</th>
                                    <th class="text-center text-vertical-align">Description</th>
                                    <th class="text-center text-vertical-align">Rooms</th>
                                    <th class="text-center text-vertical-align">Status</th>
                                    <th class="text-cent text-vertical-align pd-10">Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <small>* N/A: Data Not Available</small>
                </div>
                <div class="form-overlay" v-if="showFormOverlay">
                    <div class="large loader"></div>
                    <div class="loader-text">Fetching Results</div>
                </div>
            </div>
            <div id="NotificationArea"></div>         
        </div>
    </div>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ URL::assetUrl('/js/sitemeasurement/report.js') }}"></script>
@endsection