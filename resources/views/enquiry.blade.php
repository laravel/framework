@extends('layouts/master_template')

@section('dynamicStyles')
<link href="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.css') }}" rel="stylesheet" />
<link href="{{ URL::assetUrl('/AdminLTE/plugins/jQueryUI/jquery-ui.min.css') }}" rel="stylesheet" />
<link rel="stylesheet" href="{{ URL::assetUrl('/plugins/datepicker/bootstrap-datepicker.min.css') }}" />
<link rel="stylesheet" href="{{ asset('/css/enquiries/create.css') }}" />
<link rel="stylesheet" href="{{ URL::assetUrl('/plugins/timepicker/bootstrap-timepicker.min.css') }}">
@endsection

<?php

function profileImage($dbPictureName) {
    $defaultImage = URL::CDN("public/images/user-160x160.png");
    if ($dbPictureName) {
        $splittedFileName = explode(".", $dbPictureName);
        if ($splittedFileName[0] === $dbPictureName) {
            $profilePictureURL = $defaultImage;
        } else {
            $profilePicture = $splittedFileName[0] . "-160x160." . $splittedFileName[1];
            $profilePicture = str_replace('/source/', '/thumbnails/', $profilePicture);
            if (Storage::has($profilePicture)) {
                $profilePictureURL = URL::CDN($profilePicture);
            } else {
                $profilePictureURL = $defaultImage;
            }
        }
    } else {
        $profilePictureURL = $defaultImage;
    }
    return $profilePictureURL;
}
?>
@section('content')
<div class="row">
    <div class="col-md-12 text-right custom-info-block">
        <span class="pd-5 text-capitalize user-info">
            <i class="fa fa-user text-info" aria-hidden="true"></i>&nbsp;
            {{ $user->Person->FirstName }}   {{ $user->Person->LastName }}
        </span>
        <span class="pd-5 user-info">
            <i class="fa fa-phone-square text-info" aria-hidden="true"></i>&nbsp;
            {{ $user->Phone }}
        </span>
        <span class="pd-5 user-info"> 
            <i class="fa fa-envelope-square text-info" aria-hidden="true"></i>&nbsp;
            {{ $user->Email }}
        </span>
    </div>
    <div class="col-md-12">
        <div class="box box-primary clearfix">
            <div id="FormContainer">
                {!! form($form) !!}
                <div id="NotificationArea">
                    <div class="alert alert-dismissible hidden"></div>
                </div>
                <div class="pd-15">
                    <label>You can mark your site location below</label>
                    <div id="customerSiteMap"></div>
                </div>
                <div class="pd-15">*:&nbsp;<small>Mandatory fields</small></div>
            </div>
        </div>
        <div class="modal fade" id="ConfirmModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Confirmation</h4>
                    </div>
                    <div class="modal-body">
                        <h5>Form has modified. Would you like to save it before moving to the other step?</h5>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-sm EnquiryButtons mr-2" data-dismiss="modal" id="ConfirmationNo">No</button>
                        <button type="button" class="btn btn-primary btn-sm EnquiryButtons" id="ConfirmationYes">Yes</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="OldNotesActions" tabindex="-1" role="dialog" aria-labelledby="OldNotesActions">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title">
                            <i class="fa fa-history reverse-icon" aria-hidden="true"></i>&nbsp;&nbsp;Notes & Actions History
                        </h4>
                    </div>
                    <div class="modal-body">
                        <div class="nav-tabs-custom">
                            <ul class="nav nav-tabs">
                                @if(isset($notesTypes))
                                @foreach($notesTypes as $Key => $type)                                
                                <?php $ActiveTab = ""; ?>
                                @if ($loop->first)
                                <?php $ActiveTab = "active"; ?>
                                @endif
                                <li class="{{$ActiveTab}}">
                                    <a href="#{{$type["Id"]}}" data-toggle="tab"><strong>{{$type["Name"]}}</strong></a>
                                </li>
                                @endforeach
                            </ul>
                            <div class="tab-content">
                                @foreach($notesTypes as $Key => $type)                               
                                <?php $ActiveTab = ""; ?>
                                @if ($loop->first)
                                <?php $ActiveTab = "active"; ?>
                                @endif
                                <div class="tab-pane {{$ActiveTab}}" id="{{$type["Id"]}}">
                                    @if(isset($notesActions[$type["Id"]]))       
                                    <div class="box-footer box-comments">
                                        <div id="CommentsBox">
                                            <div class="pd-tp-8">
                                                @foreach($notesActions[$type["Id"]] as $values)
                                                <?php $ColWidth = "col-md-6"; ?>
                                                @if($values->Type == 1)
                                                <?php $ColWidth = "col-md-10"; ?>
                                                @endif
                                                <div class="box-comment">
                                                    <div class="row">
                                                        <div class="{{$ColWidth}}">
                                                            <img class="img-circle img-sm" src="{{profileImage($values->AddedUserPhoto)}}" alt="User Image">
                                                            <span class="username comment-text">{{$values->AddedBy}}</span>
                                                            <div class="pd-lt-39" style="color:#555;">{{$values->Description}}</div>
                                                        </div>
                                                        @if($values->Type == 2)
                                                        <div class="col-md-4 added-time">
                                                            <span>
                                                                <strong>Due Date: </strong>{{$values->DueDate}}
                                                            </span><br>                         
                                                            <span>
                                                                <strong>Assigned To: </strong> {{$values->AssignedTo}}
                                                            </span><br>
                                                            <span>
                                                                <strong>Status: </strong>
                                                                @if($values->Status)
                                                                <span class="label label-{{$StatusLabel[$values->Status]}}">{{$Status[$values->Status]}}</span>
                                                                @else
                                                                <small>N/A</small>
                                                                @endif
                                                            </span>
                                                        </div>
                                                        @endif
                                                        <div class="col-md-2">
                                                            <span class="text-muted pull-right">
                                                                {{Carbon\Carbon::parse($values->CreatedAt)->addHours(5)->addMinutes(30)->format("d-M-Y h:i A")}}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>                 
                                        </div>
                                    </div>
                                    @else  
                                    <div class="alert alert-info" style="margin-top:0.5em;margin-bottom:0.8em;">No history available.</div>
                                    @endif
                                </div>
                                @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-loader hidden" id="EnquiryFormLoader">
            <div class="overlay">
                <i class="fa fa-refresh fa-spin"></i>
            </div>
        </div>
        <div id="latest-action-notes">
            <div class="notes-block {{ ($latestNote) ? '':'hidden'}}">
                <div class="row pd-rt-15 pd-lt-15">
                    <div class="form-group col-xs-12 mr-bt-0">
                        <strong class="pull-left pd-rt-6">Recent Note: </strong>
                        <div class="form-control-static enquiry-note pd-tp-0">{{$latestNote}}</div>
                    </div>
                </div>
            </div>
            <div class="actions-block {{ ($latestAction) ? '':'hidden'}}">
                <div class="row pd-rt-15 pd-lt-15">
                    <div class="form-group col-xs-12 mr-bt-0">
                        <strong class="pull-left pd-rt-6">Recent Action: </strong>
                        <div class="form-control-static enquiry-action-text pd-tp-0">{{$latestAction}}</div>
                    </div>
                </div>
                <div class="row pd-rt-15 pd-lt-15">
                    <div class="form-group col-md-3 mr-bt-0">
                        <strong class="pull-left pd-rt-6">Due Date: </strong>
                        <div class="form-control-static enquiry-duedate pd-tp-0">{{$actionDueDate}}</div>
                    </div>
                    <div class="form-group col-md-3 mr-bt-0">
                        <strong class="pull-left pd-rt-6">Assigned To: </strong>
                        <div class="form-control-static enquiry-assigneduser pd-tp-0">{{$actionAssignedTo}}</div>
                    </div>
                    <div class="form-group col-md-3 mr-bt-0">
                        <strong class="pull-left pd-rt-6">Status : </strong>
                        <div class="form-static enquiry-status pd-tp-0">
                            @if($latestStatus)
                            <span class="label label-{{$StatusLabel[$latestStatus]}}">{{$Status[$latestStatus]}}</span>
                            @else
                            <small>N/A</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="row pd-rt-15 pd-lt-15 note-history-btn {{ ($latestNote || $latestAction) ? '':'hidden'}}">
                <div class="form-group col-md-3 mr-bt-0">
                    <div class="form-control-static pd-tp-0">
                        <div>
                            <i class="fa fa-history reverse-icon" data-toggl e="modal" data-target="#OldNotesActions" aria-hidden="true"></i>
                            <a href="javascript:void(0);" class="view-histroy" id="ViewNoteActionHistory" data-toggle="tooltip" data-original-title="View all notes &amp; actions">&nbsp;&nbsp; View all notes &amp; actions</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicScripts')
<script>
    var bootstrapTooltip = $.fn.tooltip.noConflict();
    $.fn.bstooltip = bootstrapTooltip;
</script>
<script src="{{ URL::assetUrl('/js/common.js') }}"></script>
<script src="{{ URL::assetUrl('/js/FormGenerator.js') }}"></script>
<script src="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.js') }}"></script>
<script src="{{ URL::assetUrl('/AdminLTE/plugins/jQueryUI/jquery-ui.min.js') }}"></script>
<script src="{{ URL::assetUrl('/plugins/datepicker/bootstrap-datepicker.min.js') }}"></script>
<script src="{{ URL::assetUrl('/plugins/timepicker/bootstrap-timepicker.min.js') }}"></script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}"></script>
<script src="{{ asset('/js/enquiries/create.js') }}"></script>
@endsection
