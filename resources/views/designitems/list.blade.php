@extends('layouts/master_template')
@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">       
        <div class="box box-primary">
            @if(sizeof($designItems) === 0)
            <div class="callout callout-info">
                <h4>Information!</h4>
                <p>No Items are avaiable. Click here to <a href="{{route('designitems.create')}}" title="Add a Item">Add a Item</a>.</p>
            </div>
            @else
            <div class="box-body table-responsive">
                <table class="table table-bordered" id="DesignItemTable">
                    <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                        <tr>
                            <th width="5%" class="text-center text-vertical-align pd-rt-8">S.No</th>
                            <th width="40%" class="text-center text-vertical-align pd-rt-8">Name</th>
                            <th width="30%" class="text-center text-vertical-align pd-rt-8">Code</th>
                            <th width="20%" class="text-center text-vertical-align pd-rt-8">Status</th>
                            <th width="5%" class="text-center text-vertical-align pd-rt-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($designItems as $Key => $designItem)
                        <tr>
                            <td class="text-center text-vertical-align">{{ $Key + 1 }}</td>
                            <td class="text-center text-vertical-align">{{$designItem->Name}}</td>
                            <td class="text-center text-vertical-align">{{$designItem->Code}}</td>
                            <td class="text-center text-vertical-align">
                                @if($designItem->IsActive)
                                <span class='label label-success'>Active</span>
                                @else
                                <span class='label label-danger'>Inactive</span>
                                @endif
                                </td>
                            <td class="text-center text-vertical-align">
                                <a href="/designitems/{{$designItem->Id}}/edit" class="btn btn-custom btn-edit btn-sm"  data-toggle="tooltip" data-original-title="Edit" role="button"><span class="glyphicon glyphicon-pencil btn-edit"></span></a>
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
<script src="{{ URL::assetUrl('/js/common.js') }}"></script>
<script src="{{ URL::assetUrl('/js/designitems/list.js') }}"></script>
@endsection