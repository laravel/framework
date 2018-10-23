@extends('layouts/master_template')
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-body table-responsive no-padding">
                @if(sizeof($List) === 0)
                <div class="callout callout-info mr-tp-15 mr-bt-15">
                    <span>No Ideas found.</span>
                </div>
                @else              
                <table class="table table-bordered" id="ListTable">
                    <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                        <tr>
                            <th class="text-center text-vertical-align pd-10">S.No</th>
                            <th class="text-center text-vertical-align">Project Name</th>
                            <th class="text-center text-vertical-align">Room - Item</th>
                            <th class="text-center text-vertical-align">IdeaBy</th>
                            <th class="text-center text-vertical-align">Idea</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody> 
                        @foreach($List as $key => $value)   
                        <tr>
                            <td class="text-center text-vertical-align">{{$key + 1}}</td>
                            <td class="text-vertical-align"> {!! $value->SiteProject !!} </td>
                            <td class="text-vertical-align"> {{ $value->RoomItem }} </td>
                            <td class="text-vertical-align"> {{ $value->CommentUser }} </td>
                            <td class="text-vertical-align"> {{ $value->Comment }} </td>
                            <td class="text-vertical-align text-center">
                                <a href="{{ route('ideas.create.fetch',[
                                    'projectId' => $value->DesignProjectId,
                                    'roomId' => $value->DesignRoomId,    
                                    'itemId' => $value->DesignItemId,
                                    'userId' => $value->DesignUserId
                                        ]) }}" 
                                   class="btn btn-custom btn-edit btn-sm" 
                                   data-toggle="tooltip" data-original-title="reply" role="button">
                                    <i class="glyphicon glyphicon-pencil btn-edit" aria-hidden="true"></i>
                                </a>
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
@endsection

@section('dynamicScripts')
<script src="{{ asset('/AdminLTE/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ asset('js/ideas/ideasList.js') }}"></script>
@endsection