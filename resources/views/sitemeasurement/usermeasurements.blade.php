@extends('layouts/master_template')

@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
<link href="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.css') }}" rel="stylesheet" />
<link rel="stylesheet" href="{{ URL::assetUrl("/css/sitemeasurement/report.css")}}">
@endsection

@section('content')
@if(auth()->user()->isSupervisor())
<div class="col-md-12 text-right addNew-block">
    <a class="btn btn-primary button-custom fl-rt AddButton" href="{{ route('sitemeasurement.add') }}" data-toggle="tooltip" title="Click here to Add New Measurement" > <i class="fa fa-fw fa-plus-square"></i> New Site Measurement</a>
</div>
@endif
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-body no-padding pd-bt-10" id="SiteMeasureListBox">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="UserMeasurementsTable">
                        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                            <tr>
                                <th class="text-center text-vertical-align pd-10" width="8%">S.No</th>
                                <th class="text-center text-vertical-align" width="22%">Project</th>
                                <th class="text-center text-vertical-align" width="22%">Description</th>
                                <th class="text-center text-vertical-align" width="20%">Rooms</th>
                                <th class="text-center text-vertical-align" width="18%">Status</th>
                                <th class="text-center text-vertical-align pd-10" width="10%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($Measurements as $key => $measurement)
                            <tr>
                                <td class="text-center text-vertical-align">{{$key+1}}</td>
                                <td class="text-center text-vertical-align">{{$measurement["Project"]}}</td>
                                <td class="text-center text-vertical-align">{!!$measurement["Description"]!!}</td>
                                <td class="text-center text-vertical-align">{!!$measurement["Rooms"]!!}</td>
                                <td class="text-center text-vertical-align">{{$measurement["Status"]}}</td>
                                <td class="text-center text-vertical-align">
                                    @if($measurement["Url"]["ShowEdit"] === "Yes")
                                    <a href='{{ $measurement["Url"]["Edit"]}}' title="Edit Measurement">
                                        <i class="fa fa-pencil text-black mr-rt-4" aria-hidden="true"></i>
                                    </a>
                                    @endif
                                    <a href='{{ $measurement["Url"]["View"]}}' title="View Measurement">
                                        <i class="fa fa-eye text-black mr-rt-4" aria-hidden="true"></i>
                                    </a>
                                    @if($measurement["Url"]["ShowRoomCals"] === "Yes")
                                    <a href='{{ $measurement["Url"]["RoomCalsUrl"]}}' title="View Calculations">
                                        <i class="fa fa-fw fa-calculator text-black" aria-hidden="true"></i>
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>      
    </div>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ URL::assetUrl('/js/sitemeasurement/usermeasurements.js') }}"></script>
@endsection