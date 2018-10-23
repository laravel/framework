@extends('layouts/master_template')
@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">
       <div class="box box-primary">
           @if(sizeof($RoomData) == 0)
           <div class="callout callout-info">
               <h4>Information!</h4>
               <p>No Rooms are avaiable. Click here to <a href="{{route('rooms.create')}}" title="Add a Room">Add a Room</a>.</p>
           </div>
           @else        
            <div class="box-body table-responsive">
                <table class="table table-bordered" id="RoomReportTable">
                    <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                        <tr>
                            <th width="3%" class="text-center text-vertical-align pd-rt-8">S.No</th>
                            <th width="15%" class="text-center text-vertical-align pd-rt-8">Name</th>
                            <th width="12%" class="text-center text-vertical-align pd-rt-8">Short Code</th>
                            <th width="30%" class="text-center text-vertical-align pd-rt-8">Description</th>
                            <th width="30%" class="text-center text-vertical-align pd-rt-8">Comment</th>
                            <th width="8%" class="text-center text-vertical-align pd-rt-8">Status</th>
                            <th width="2%" class="text-center text-vertical-align pd-rt-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($RoomData as $Key => $Room)
                        <tr>
                            <td class="text-center text-vertical-align">{{ $Key + 1 }}</td>
                            <td class="text-center text-vertical-align">{{$Room->Name}}</td>
                            <td class="text-center text-vertical-align">{{$Room->ShortCode}}</td>
                            <td class="text-center text-vertical-align">{{$Room->Description}}</td>
                            <td class="text-center text-vertical-align">{{$Room->Comment}}</td>
                            <td class="text-center text-vertical-align">
                                @if($Room->IsActive)
                                <span class='label label-success'>Active</span>
                                @else
                                <span class='label label-danger'>Inactive</span>
                                @endif
                            </td>
                            <td class="text-center text-vertical-align">
                                <a href="/rooms/{{$Room->Id}}/edit" class="btn btn-custom btn-edit btn-sm"  data-toggle="tooltip" data-original-title="Edit" role="button"><span class="glyphicon glyphicon-pencil btn-edit"></span></a>
                            </td>                            
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>           
        @endif
        </div>
    </div>
</div>
@endsection
@section('dynamicScripts')
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.full.min.js') }}"></script>
<script src="{{ URL::assetUrl('/js/common.js') }}"></script>
<script src="{{ URL::assetUrl('/js/roommaster/list.js') }}"></script>
@endsection