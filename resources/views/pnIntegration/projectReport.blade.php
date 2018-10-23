@extends('layouts/master_template')
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <form id="ProjectSearchForm" method="POST" action="">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="Project">Project</label>
                                <select name="Project" id="Project" class="form-control">
                                    <option value="">Select Project</option>
                                    @foreach($Projects as $Project)
                                    <option value="{{$Project->Id}}">{{$Project->Name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="User">User</label>
                                <select name="User" id="User" class="form-control">
                                    <option value="">Select User</option>
                                    @foreach($Users as $User)
                                    <option value="{{$User->Id}}">{{$User->Email}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="Status">Status</label>
                                <select name="Status" id="Status" class="form-control">
                                    <option value="">Select Status</option>
                                    <option value="0">All Projects</option>
                                    @foreach($Status as $Key => $Value)
                                    <option value="{{$Key}}">{{$Value}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mr-tp-10">
                        <div class="col-md-4">
                            <input type="submit" class="btn btn-primary button-custom mr-bt-10" value="Search" id="ProjectSubmit" />
                            <input type="reset" class="btn button-custom mr-bt-10" value="Clear" id="ProjectReset" />
                        </div>
                    </div>
                </form>
            </div>
            <div class="box-body no-padding hidden pd-bt-10" id="ProjectListBox">
              <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="ProjectReport">
                        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                            <tr>
                                <th class="rate-text-center pd-10">S.No</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>CreatedAt</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
              </div>
              <small>* N/A: Data Not Available</small>
            </div>
            <div class="form-overlay hidden" id="SearchFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Fetching Results...</div>
            </div>
        </div>
        <div id="NotificationArea"></div>         
    </div>
</div>
@endsection

@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
<link href="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.css') }}" rel="stylesheet" />
<link rel="stylesheet" href="{{ URL::assetUrl("/css/pnIntegration/report.css")}}">
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.full.min.js') }}"></script>
<script src="{{ URL::assetUrl('/js/pnIntegration/report.js') }}"></script>
@endsection