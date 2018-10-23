@extends('layouts/master_template')

@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl("/css/magnific-popup.css")}}">
<link rel="stylesheet" href="{{ asset('/plugins/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/sitemeasurement/update.css') }}">
@endsection

@section('content')
<div id="EditSiteMeasurementPage" v-cloak>
    <div class="row">
        <user-information :user-info="enquiryProject"></user-information>
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="edit-sitepage">    
                    <div class="box-header with-border">
                        <div class="row">
                            <div class="col-md-12">
                                <button name="Back" class="btn btn-primary btn-custom fl-rt back-btn mr-rt-0" onclick="window.location ='{{route('sitemeasurement.list')}}'">Back</button>
                                @if($RoomCalsUrl)
                                <button name="CalsBtn" class="btn btn-primary btn-custom fl-rt" onclick="window.location ='{{$RoomCalsUrl}}'">View Calculations</button>
                                @endif
                                @if(auth()->user()->isSupervisor())
                                <input type="button" name="SubmitForReview" value="Submit For Review" class="btn btn-primary btn-custom fl-rt" id="SubmitForReview" @click.prevent="onReviewSubmit" data-review-submit-url="{{ route("sitemeasurement.review.send") }}"/>
                                @endif
                                @if(auth()->user()->isReviewer())  
                                <input type="button" name="RejectReview" value="Reject" class="btn btn-primary button-custom fl-rt" data-toggle="tooltip" title="Reject Review" id="RejectReview" @click.prevent="onRejectReview" data-review-reject-url="{{ route("sitemeasurement.review.reject") }}"/>
                                <input type="button" name="SubmitForApproval" value="Submit" data-toggle="tooltip" title="Submit For Approval" class="btn btn-primary button-custom fl-rt" id="SubmitForApproval" @click.prevent="onApproveReview" data-approve-sitem-url="{{ route("sitemeasurement.review.accept") }}"/>                        
                                @endif
                                @if(auth()->user()->isApprover())
                                <input type="button" name="RejectApproval" value="Reject" class="btn btn-primary button-custom fl-rt" data-toggle="tooltip" title="Reject Approval" id="RejectApproval" @click.prevent="onApprovalReject" data-approval-reject-url="{{ route("sitemeasurement.approval.reject") }}"/>
                                <input type="button" name="AcceptApproval" value="Approve" class="btn btn-primary button-custom fl-rt" data-toggle="tooltip" title="Accept Approval" id="AcceptApproval" @click.prevent="onApprovalAccept" data-approval-accept-url="{{ route("sitemeasurement.approval.accept") }}"/>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="box-header with-border">
                        <form action="" method="POST" accept-charset="utf-8" id="EditSiteMForm" enctype="multipart/form-data">   
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <?php $disableProjectSelect2 = "disabled='disabled'"; ?>
                            @if(auth()->user()->isManager() || auth()->user()->isSupervisor())
                            <?php $disableProjectSelect2 = ""; ?>
                            @endif
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="Projects">Project*</label>
                                        <select name="Projects" id="Projects" class="form-control" <?= $disableProjectSelect2 ?>>
                                            <option value="">Select a Project</option>
                                            @if(!$projects->isEmpty())
                                            @foreach($projects as $project)
                                            <option value="{{ $project->Id }}" {{$project->Id== $projectId ? 'selected="selected"' : ''}}>{{ $project->Name }}</option>
                                            @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="Description">Notes</label>
                                        <textarea type="text" name="Description" id="Description" rows="2" v-model="description" class="form-control no-resize-input" placeholder="Ex: Pooja room."></textarea>
                                    </div>
                                </div>
                                <div class="col-md-4 text-right">
                                    <div class="hidden form-group" id="siteInfo">
                                        <label></label>
                                        <div id="SiteDetails" class="pd-rt-5 pd-lt-5"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mr-bt-0">    
                                        <div class="input-group" data-toggle="tooltip" title="Click here to upload files">
                                            <label for="EditSMCopy" class="cursor-pointer">SM Scanned Copy*</label>
                                            <label class="input-group-addon cursor-pointer upload-addon" for="EditSMCopy">
                                                <i class="fa fa-paperclip"></i>
                                            </label>
                                        </div>
                                        <input type="file" name='EditSMCopy[]' @change.prevent="onUploadChange($event, totalScannedCopies, newScannedCopies)" class="hidden" accept="image/*" id="EditSMCopy" multiple="multiple" class="form-control"/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <scannedcopy-upload :scanned-attachments-list="totalScannedCopies" v-show="shouldRenderScannedCopies" @deletecopy="deleteFiles"></scannedcopy-upload>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mr-bt-0">
                                        <div class="input-group" data-toggle="tooltip" title="Click here to upload files">
                                            <label for="EditChecklistCopy"  class="cursor-pointer">Manual Checklist Copy*</label>
                                            <label class="input-group-addon upload-addon cursor-pointer" for="EditChecklistCopy">
                                                <i class="fa fa-paperclip"></i>
                                            </label>
                                        </div>
                                        <input type="file" name='EditChecklistCopy[]' @change.prevent="onUploadChange($event, totalChecklistCopies, newChecklistCopies)" class="hidden" accept="image/*" id="EditChecklistCopy" multiple="multiple" class="form-control"/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <checklist-upload :checklist-attachments-list="totalChecklistCopies" v-show="shouldRenderChecklistCopies" @deletechecklist="deleteFiles"></checklist-upload>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">    
                                    <div class="form-group mr-bt-0"> 
                                        <div class="input-group" data-toggle="tooltip" title="Click here to upload files">
                                            <label for="UploadFiles" class="cursor-pointer">Site Photos and Videos*</label>
                                            <label class="input-group-addon upload-addon cursor-pointer" for="UploadFiles">
                                                <i class="fa fa-paperclip"></i>
                                            </label>
                                        </div>
                                        <input type="file" name='UploadFiles[]' @change.prevent="onUploadChange($event, totalSiteAttachments, newSiteAttachments)" class="hidden" accept="image/*|video/*" id="UploadFiles" multiple="multiple" class="form-control"/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <site-attachments :site-attachments-list="totalSiteAttachments" v-show="showSiteAttachmentsBlock" @deletesiteattachment="deleteFiles"></site-attachments>
                                </div>
                            </div>
                            <div class="row site-action-buttons">
                                <div class="col-xs-12">
                                    <p>  
                                        <input type="submit" name="EditSiteMSubmit" value="Update" class="btn btn-primary button-custom" id="EditSiteMSubmit"/>
                                        @if(auth()->user()->isSupervisor())
                                        <input type="reset" class="btn button-custom" value="Undo" id="EditSiteMeasurementFormReset" onClick="history.go(0)"/>
                                        @endif
                                        @if(auth()->user()->isReviewer())                                   
                                        <input type="reset" class="btn button-custom site-reset-btn" value="Undo" id="EditSiteMeasurementFormReset" onClick="history.go(0)"/>
                                        @endif
                                        @if(auth()->user()->isApprover())                                       
                                        <input type="reset" class="btn button-custom site-reset-btn" value="Undo" id="EditSiteMeasurementFormReset" onClick="history.go(0)"/>
                                        @endif
                                        @if(auth()->user()->isManager())
                                        <input type="reset" class="btn button-custom" value="Undo" id="EditSiteMeasurementFormReset" onClick="history.go(0)"/>
                                        @endif
                                    </p>
                                </div>
                            </div> 
                        </form>
                        <div id="NotificationArea" class="hidden">
                            <div class="alert alert-dismissible"></div>
                        </div>
                        <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
                    </div>
                    @include('sitemeasurement.addRoomModal')
                    @include('sitemeasurement.addAcModal')
                    @include('sitemeasurement.editAcModal')
                    @include('sitemeasurement.deleteAcModal')
                    @include('sitemeasurement.addFireSprinklerModal')
                    @include('sitemeasurement.editFireSprinklerModal')
                    @include('sitemeasurement.deleteFireSprinkler')
                    @include('sitemeasurement.editRoomModal')
                    @include('sitemeasurement.deleteRoomModal')
                    @include('sitemeasurement.roomsView')
                </div>
                <div id="ReviewNotificationArea" class="hidden">
                    <div class="alert alert-dismissible"></div>
                </div>
                <div class="form-overlay hidden" id="EditSiteMFormOverlay">
                    <div class="large loader"></div>
                    <div class="loader-text">Updating</div>
                </div>
                <div class="form-overlay hidden" id="UpdateSiteLoader">
                    <div class="large loader"></div>
                    <div class="loader-text">Updating</div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <button name="Back" class="btn btn-primary btn-custom fl-rt back-btn mr-bt-10 mr-rt-10" onclick="window.location ='{{route('sitemeasurement.list')}}'">
                            Back
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<small class="datanotfound-legend">* N/A: Data not available</small>
@endsection

@section('dynamicScripts')
<script src="{{ asset('/js/common.js') }}"></script>
<script src="{{ asset('/js/sitemeasurement/update.js') }}"></script>
@endsection