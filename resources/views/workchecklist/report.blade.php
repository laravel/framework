@extends('layouts/master_template')
@section('content')
<div id="WorkChecklistReportsPage" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <form id="WorkChecklistsSearchForm" method="POST" action="" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="User">User</label>
                                    <select name="User" id="User" class="form-control">
                                        <option value="">Select User</option>
                                        @foreach($CustomerUsers as $User)
                                        <option value="{{$User->Id}}">{{$User->Email}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
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
                        </div>
                        <div class="row mr-tp-10">
                            <div class="col-md-12">
                                <input type="submit" class="btn btn-primary button-custom mr-bt-10" value="Search" id="SubmitBtn" />
                                <input type="reset" class="btn button-custom mr-bt-10" value="Clear" id="ResetBtn" />
                            </div>
                        </div>
                    </form>
                </div>
                <div class="box-body no-padding hidden pd-bt-10" id="ChecklistBox">
                    <div class="table-responsive pd-tp-10">
                        <table class="table table-bordered table-striped" id="ChecklistReport">
                            <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                                <tr>
                                    <th class="text-center text-vertical-align pd-10" width="5%">S.No</th>
                                    <th class="text-center text-vertical-align" width="15%">Project</th>
                                    <th class="text-center text-vertical-align" width="14%">Checklist Type</th>
                                    <th class="text-center text-vertical-align" width="14%">CreatedBy</th>
                                    <th class="text-center text-vertical-align" width="14%">UpdatedBy</th>
                                    <th class="text-center text-vertical-align" width="12%">CreatedOn</th>
                                    <th class="text-center text-vertical-align" width="12%">UpdatedOn</th>
                                    <th class="text-center text-vertical-align" width="4%"></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <small>* N/A: Data Not Available</small>
                </div>
                <div class="form-overlay" v-if="showFormOverlay">
                    <div class="large loader"></div>
                    <div class="loader-text">Fetching Checklists</div>
                </div>
            </div>
            <div id="NotificationArea"></div>         
        </div>
    </div>
</div>
<div class="modal fade" id="ChecklistViewModal" tabindex="-1" role="dialog" aria-labelledby="EnquiryViewTitle">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content printable-area">
            <div class="modal-body"></div>
        </div>
    </div>
</div>
@endsection

@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
<link href="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.css') }}" rel="stylesheet" />
<link rel="stylesheet" href="{{ URL::assetUrl("/css/workchecklist/report.css")}}">
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ URL::assetUrl('/js/workchecklist/report.js') }}"></script>
@endsection