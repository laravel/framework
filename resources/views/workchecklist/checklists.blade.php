@extends('layouts/master_template')
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <form action="" method="get" id="ChecklistForm">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="Project">Project*</label>
                                <select name="Project" id="Project" class="form-control">
                                    <option value="">Select Project</option>
                                    @foreach($Projects as $Project)
                                    <option value="{{$Project['Id']}}" {{ ($Project['Id'] == $ProjectId) ? 'selected' : '' }}>{{$Project['Name']}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="Type">Checklist Type</label>
                                <select name="Type" id="Type" class="form-control">
                                    <option value="">Select Type</option>
                                    @foreach($Types as $Type)
                                    <option value="{{$Type->Id}}" {{ ($Type->Id == $TypeId) ? 'selected' : '' }}>{{$Type->Name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            @if(!is_null($ProjectId) || !is_null($TypeId))
            @if($Checklists->isEmpty()) 
            <div class="box-body">
                <div class="callout callout-info mr-8" style="font-size:17px;">
                    <p><i class="fa fa-fw fa-info-circle" aria-hidden="true"></i>Checklists not available. Click here to <a href="{{route("work.checklist", ["project" => $ProjectId, "type" => $TypeId])}}" id="NoChecklistsFound">Create new</a> Checklist.</p>
                </div>
            </div>
            @else
            <div class="box-body pd-bt-10" id="ChecklistResultBody">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="CheckListsTable">
                        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                            <tr>
                                <th class="text-center text-vertical-align pd-10" width="6%">S.No</th>
                                <th class="text-center text-vertical-align" width="18%">Checklist Type</th>
                                <th class="text-center text-vertical-align" width="16%">CreatedBy</th>
                                <th class="text-center text-vertical-align" width="16%">UpdatedBy</th>
                                <th class="text-center text-vertical-align" width="15%">CreatedOn</th>
                                <th class="text-center text-vertical-align" width="15%">UpdatedOn</th>
                                <th class="text-center text-vertical-align" width="4%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($Checklists as $key => $checklist)
                            <tr>
                                <td class="text-center text-vertical-align">{{ $key + 1 }}</td>
                                <td class="text-center text-vertical-align">{{ $checklist->FormTemplate->FormCategory->Name }}</td>
                                <td class="text-center text-vertical-align">{{ $checklist->CreatedByUser->Person->FirstName." ".  $checklist->CreatedByUser->Person->LastName }}</td>
                                <td class="text-center text-vertical-align"><?=($checklist["UpdatedBy"]) ? $checklist->UpdatedByUser->Person->FirstName." ". $checklist->UpdatedByUser->Person->LastName : "<small>N/A</small>" ?></td>
                                <td class="text-center text-vertical-align">{{ Carbon\Carbon::parse($checklist["CreatedAt"])->addHours(5)->addMinutes(30)->format("d-M-Y h:i A") }}</td>
                                <td class="text-center text-vertical-align"><?= $checklist["UpdatedAt"] ? Carbon\Carbon::parse($checklist["UpdatedAt"])->addHours(5)->addMinutes(30)->format("d-M-Y h:i A") : "<small>N/A</small>" ?></td>
                                <td class="text-center text-vertical-align">
                                    <span class="dropdown">
                                        <a class="dropdown-toggle" data-toggle="dropdown" href="" role="button" aria-haspopup="true" aria-expanded="false">
                                            <i class="fa fa-caret-square-o-down" aria-hidden="true"></i>
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="SearchResultsDropdownMenu">
                                            <li>
                                                <a href="{{ route('work.checklist', ['project' => $checklist["SiteProjectId"], "type" => $checklist->FormTemplate->FormCategory->Id, "id" => $checklist["Id"]]) }}">
                                                    <i class="fa fa-pencil" aria-hidden="true"></i> Edit Checklist
                                                </a>
                                            </li>  
                                            <li>
                                                <a href="{{ route('work.checklist.view', ["id" => $checklist["Id"]]) }}" class="view-checklist-link">
                                                    <i class="fa fa-eye" aria-hidden="true"></i> View Checklist
                                                </a>
                                            </li>  
                                        </ul>
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
            @endif
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
<link rel="stylesheet" href="{{ asset('/plugins/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/workchecklist/checklist.css') }}">
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.full.min.js') }}"></script>
<script src="{{ asset('js/workchecklist/view.js') }}"></script>
@endsection