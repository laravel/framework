@include('designs/getAttachment')
@extends('layouts/master_template')
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-body table-responsive no-padding">
                @if(sizeof($DesignsList) === 0)
                <div class="callout callout-info mr-tp-15 mr-bt-15">
                    <span>No designs found.</span>
                </div>
                @else              
                    <table class="table table-bordered" id="DesignsTable">
                        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                            <tr>
                                <th class="text-center text-vertical-align pd-10">S.No</th>
                                <th class="text-center text-vertical-align">Project Name</th>
                                <th class="text-center text-vertical-align">Room - Item</th>
                                <th class="text-center text-vertical-align">Attachments </th>
                                <th class="text-center text-vertical-align">Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody> 
                            @foreach($DesignsList as $key => $design)   
                            <tr>
                                <td class="text-center text-vertical-align">{{$key + 1}}</td>
                                <td class="text-vertical-align"> {!! $design["Project"] !!} </td>
                                <td class="text-vertical-align"> {!! $design["RoomItem"] !!} </td>
                                <td class="Attachment text-vertical-align">{!!$design["LatestAttachments"] !!}</td>
                                <td class="text-vertical-align text-center"> {{ $design["Status"] }} </td>
                                <td class="text-center text-vertical-align">
                                    <a href="{{ route('designs.show', ['designId' => $design["DesignId"]]) }}" class="btn btn-custom btn-edit btn-sm" data-toggle="tooltip" data-original-title="Comment" role="button"><i class="glyphicon glyphicon-pencil btn-edit" aria-hidden="true"></i></a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div>
                        @endif
                    </div>                
            </div>
        </div>
        <small>* N/A: Data Not Available</small>
    </div>
</div>
@endsection

@section('dynamicStyles')
<link href="{{ asset('/AdminLTE/plugins/datatables/dataTables.bootstrap.css') }}" rel="stylesheet"/>
<link rel="stylesheet" href="{{ URL::assetUrl("/css/magnific-popup.css")}}">
<link href="{{ asset('/css/designs/view.css') }}" rel="stylesheet"/>
<link href="{{ asset('/css/designs/common.css') }}" rel="stylesheet"/>
@endsection

@section('dynamicScripts')
<script src="{{ asset('/AdminLTE/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ asset('/js/common.js') }}"></script>
<script src="{{ asset('/js/designs/list.js') }}"></script>
<!-- Magnific Popup core JS file -->
<script src="{{ asset('/js/magnific-popup.js') }}"></script>
@endsection