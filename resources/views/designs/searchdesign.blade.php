@extends('layouts/master_template')
@section('dynamicStyles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/css/bootstrap-datepicker3.min.css"/>
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
<link rel="stylesheet" href="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.css') }}"/>
<link rel="stylesheet" href="{{ URL::assetUrl("/css/magnific-popup.css")}}">
<link rel="stylesheet" href="{{ URL::assetUrl("/css/designs/update.css")}}">
<link rel="stylesheet" href="{{ URL::assetUrl("/css/designs/overlay.css")}}">
<link rel="stylesheet" href="{{ URL::assetUrl('/css/designs/common.css')}}"/>
@endsection
@section('content')
<div class="row">
    @if(auth()->user()->isDesigner())
    <div class="col-md-12 text-right custom-info-block">
        <a href="{{URL::route('mydesigns.add')}}" class="btn btn-primary" data-toggle="tooltip" title="Click here to Add New Design" > <i class="fa fa-fw fa-plus-square"></i> New Design </a>
    </div>
    @endif
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <form id="DesignSearchForm" method="POST" action="">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="Project">Project</label>
                                <select name="Project" id="Project" class="form-control">
                                    <option value="">Select Project</option>
                                    @foreach($Projects as $Project)
                                    <option value="{{$Project["Id"]}}" >{{$Project["Name"]}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="Room">Room</label>
                                <select name="Room" id="Room" class="form-control">
                                    <option value="">Select Room</option>
                                    @foreach($Rooms as $Room)
                                    <option value="{{$Room->Id}}" >{{$Room->Name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="Item">Item</label>
                                <select name="Item" id="Item" class="form-control">
                                    <option value="">Select Item</option>
                                    @foreach($Items as $Item)
                                    <option value="{{$Item->Id}}" >{{$Item->Name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="User">Customer</label>
                                <select name="User" id="User" class="form-control">
                                    <option value="">Select User</option>
                                    @foreach($Users as $User)
                                    <option value="{{$User->Id}}" >{{$User->Person->FirstName." ".$User->Person->LastName}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
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
                        <div class="col-md-4">
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
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="Status">Status</label>
                                <select name="Status" id="Status" class="form-control">
                                    <option value="">Select Status</option>
                                    @foreach($Statuses as $Key => $Status)
                                    <option value="{{$Key}}" >{{$Status}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @if(isset($Designers))
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="Designer">Designer</label>
                                    <select name="Designer" id="Designer" class="form-control">
                                        <option value="">Select Designer</option>
                                        @foreach($Designers as $Key => $Designer)
                                        <option value="{{$Designer->Id}}" >{{$Designer->Person->FirstName." ".$Designer->Person->LastName}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="row mr-tp-10">
                        <div class="col-md-4">
                            <input type="submit" class="btn btn-primary button-custom mr-bt-10" value="Search" id="DesignSearchFormSubmit" />
                            <input type="reset" class="btn button-custom mr-bt-10" value="Clear" id="DesignSearchFormReset" />
                        </div>
                    </div>
                </form>
            </div>
            <div class="box-body hidden mr-tp-10 no-padding table-responsive" id="DesignListBox">
                <div class="box-header">
                    <h3 class="box-title no-text-transform">List of Designs</h3>
                </div>
                <table class="table table-bordered table-hover" id="UpdateDesignView">
                    <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                        <tr>
                            <th class="text-center text-vertical-align pd-10">#</th>
                            <th class="text-center text-vertical-align">Project</th>
                            <th class="text-center text-vertical-align">Room - Item</th>
                            <th class="text-center text-vertical-align">Latest Version Attachments</th>
                            <th class="text-center text-vertical-align">Status</th>
                            <th  class="text-center text-vertical-align pd-10">Actions</th>
                        </tr>
                    </thead>

                </table>
            </div>
            <div class="form-overlay hidden" id="DesignSearchFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Fetching Results...</div>
            </div>
        </div>
        <div id="NotificationArea"></div>         
    <small>* N/A: Data Not Available</small>
    </div>
</div>

@endsection

@section('dynamicScripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/js/bootstrap-datepicker.min.js"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.full.min.js') }}"></script>
<script src="{{ URL::assetUrl('/js/magnific-popup.js') }}"></script>
<script src="{{ URL::assetUrl('/js/common.js') }}"></script>
<script src="{{ URL::assetUrl('/js/designs/search.js') }}"></script>
@endsection
