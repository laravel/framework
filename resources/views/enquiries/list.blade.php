@extends('layouts/master_template')

@section('content')
<div class="row">
    <div class="col-md-12 text-right addNew-block">
        @if(empty($userData))
        <a class="btn btn-primary button-custom fl-rt AddButton" href="{{ route('enquiry') }}" data-toggle="tooltip" title="Click here to Add New Enquiry" > <i class="fa fa-fw fa-plus-square"></i> New Enquiry</a>
        @else 
         <a class="btn btn-primary button-custom fl-rt AddButton" href="{{ route('enquiry', ['id' => $userData['enquiryKey']]) }}" data-toggle="tooltip" title="Click here to Add New Enquiry"><i class="fa fa-fw fa-plus-square"></i> New Enquiry</a>
        @endif

    </div>
    <div class="col-md-12">
        <div class="box box-primary" id="App">
            @if(isset($userData))
            <div class="box-header with-border">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Customer Name</label>
                            <div class="form-control-static">{{ $userData["FirstName"] }} {{ $userData["LastName"] }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Email</label>
                            <div class="form-control-static">{{ $userData["Email"] }}</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Mobile</label>
                            <div class="form-control-static">{{ $userData["Mobile"] }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            <div class="box-body" id="SearchResultsBody">
                @if(empty($enquiries))
                <div class="callout callout-info mr-tp-15 mr-bt-15">
                     <span>No Enquiries found.</span>
                </div>
                @else
                <div class="table-responsive">
                    <table class="table table-striped table-bordered dataTable" id="SearchResults">
                        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                            <tr>
                            <th class="SerialNo text-center text-vertical-align" id="serial-no" width="3%">#</th>
                            <th width="12%" class="text-center text-vertical-align">Enquiry</th>
                            <th width="21%" class="text-center text-vertical-align">Site Address</th>
                            <th width="9%" class="text-center text-vertical-align">Work Type</th>
                            <th width="8%" class="text-center text-vertical-align">Unit Type</th>
                            <th width="6%" class="text-center text-vertical-align">Unit</th>
                            <th width="7%" class="text-center text-vertical-align">Super Builtup Area</th>
                            <th width="12%" class="text-center text-vertical-align">Is Handover Done?/Handover Date</th>
                            <th width="9%" class="no-text-transform text-center text-vertical-align">Created on</th>
                            <th width="9%" class="no-text-transform text-center text-vertical-align">Updated on</th>
                            <th width="4%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($enquiries as $key => $enquiry)
                            <tr>
                            <td class="text-center">{{ $key + 1 }}</td>
                            <td>
                                @if($enquiry["editKey"])
                                <i class="fa fa-circle text-red header-tooltip" data-toggle="tooltip" title="Not submitted"></i>
                                @endif
                                {{ $enquiry["Reference"] }}
                                <br>
                                {{ $enquiry["Name"] }}
                            </td>
                            <td>{!! $enquiry["SiteAddress"] !!}</td>
                            <td>{!! $enquiry["WorkType"] !!}</td>
                            <td>{!! $enquiry["UnitType"] !!}</td>
                            <td>{!! $enquiry["Unit"] !!}</td>
                            <td>{!! $enquiry["SuperBuiltUpArea"] !!}</td>
                            <td>{!! $enquiry["Handover"] !!}</td>
                            <td>{{ $enquiry["CreatedAt"] }}</td>
                            <td>{{ $enquiry["UpdatedAt"] }}</td>
                            <td class="text-center">
                            <span class="dropdown">
                                <a class="dropdown-toggle" data-toggle="dropdown" href="" role="button" aria-haspopup="true" aria-expanded="false">
                                    <i class="fa fa-caret-square-o-down" aria-hidden="true"></i>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="SearchResultsDropdownMenu">
                                    @if(isset($userData))
                                    <li>
                                        <a href="{{ route('enquiries.show', [
                                                    'enquiryreference' => $enquiry['Reference'],
                                                    'userid' => $enquiry['UserId']
                                                ]) }}" class="view-enquiry-link">
                                            <i class="fa fa-eye" aria-hidden="true"></i> View Enquiry
                                        </a>
                                    </li>
                                    @else
                                    <li>
                                        <a href="{{ route('enquiries.show', [
                                                    'enquiryreference' => $enquiry['Reference']
                                                ]) }}" class="view-enquiry-link">
                                            <i class="fa fa-eye" aria-hidden="true"></i> View Enquiry
                                        </a>
                                    </li>
                                    @endif
                                    @if($enquiry["editKey"])
                                    <li>
                                        <a href="{{ route('enquiry', ['id' => $enquiry['editKey']]) }}">
                                            <i class="fa fa-pencil" aria-hidden="true"></i> Edit Enquiry
                                        </a>
                                    </li>
                                    @else
                                    <li>
                                        <a href="{{ route("enquiries.quick-estimates.index", $enquiry["Id"]) }}">
                                            <i class="fa fa-bars" aria-hidden="true"></i> List Quick Estimates
                                        </a>
                                    </li>
                                    @endif
                                </ul>
                            </span>
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
</div>
<div class="modal fade" id="EnquiryViewModal" tabindex="-1" role="dialog" aria-labelledby="EnquiryViewTitle">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content printable-area">
            <div class="modal-body"></div>
        </div>
    </div>
</div>
@endsection

@section('dynamicStyles')
<link href="{{ asset('/AdminLTE/plugins/datatables/dataTables.bootstrap.css') }}" rel="stylesheet"/>
<link href="{{ asset('/css/search/enquiries.css') }}" rel="stylesheet"/>
@endsection

@section('dynamicScripts')
<script src="{{ asset('/AdminLTE/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ asset('/js/common.js') }}"></script>
<script src="{{ asset('/js/enquiries/list.js') }}"></script>
@endsection
