@extends('layouts/master_template')

@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl("/css/magnific-popup.css")}}">
<link rel="stylesheet" href="{{ URL::assetUrl("/css/sitemeasurement/view.css")}}">
@endsection

@section('content')
<div id="ViewSiteMPage" v-cloak>
    <div class="row">
        <user-information :user-info="enquiryProject.user"></user-information>
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <div class="row">
                        <div class="col-md-12">
                            <button name="Back" class="btn btn-primary btn-custom fl-rt back-btn mr-rt-0" onclick="window.location ='{{route('sitemeasurement.list')}}'">
                                Back
                            </button>
                            @if($RoomCalsUrl)
                            <button name="CalsBtn" class="btn btn-primary btn-custom fl-rt" onclick="window.location ='{{$RoomCalsUrl}}'">View Calculations</button>
                            @endif
                            @if((auth()->user()->isSupervisor() && $SuperVisorBtns))
                            <input type="button" name="SubmitForReview" value="Submit For Review" class="btn btn-primary btn-custom fl-rt" id="SubmitForReview" @click.prevent="onReviewSubmit" data-review-submit-url="{{ route("sitemeasurement.review.send") }}"/>
                            @endif
                            @if((auth()->user()->isReviewer() && $ReviewerBtns))  
                            <input type="button" name="RejectReview" value="Reject" class="btn btn-primary button-custom fl-rt" id="RejectReview" @click.prevent="onRejectReview" data-review-reject-url="{{ route("sitemeasurement.review.reject") }}"/>
                            <input type="button" name="SubmitForApproval" value="Review" class="btn btn-primary button-custom fl-rt" id="SubmitForApproval" @click.prevent="onApproveReview" data-approve-sitem-url="{{ route("sitemeasurement.review.accept") }}"/>                        
                            @endif
                            @if((auth()->user()->isApprover() && $ApproverBtns))
                            <input type="button" name="RejectApproval" value="Reject" class="btn btn-primary button-custom fl-rt" id="RejectApproval" @click.prevent="onApprovalReject" data-approval-reject-url="{{ route("sitemeasurement.approval.reject") }}"/>
                            <input type="button" name="AcceptApproval" value="Approve" class="btn btn-primary button-custom fl-rt" id="AcceptApproval" @click.prevent="onApprovalAccept" data-approval-accept-url="{{ route("sitemeasurement.approval.accept") }}"/>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="box-header with-border">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Notes</label>
                                <div v-html="description ? description : '<small>N/A</small>'"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Status</label>
                                <div>  
                                    <span :class="'label label-' + statusLabels[status.code]">@{{status.text}}</span> 
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-right">
                            <site-information :site-info="enquiryProject.siteInfo"></site-information>
                        </div>
                    </div>    
                    <div class="row">
                        <div class="col-md-12">
                            <strong>SM Scanned Copy</strong>
                            <div id="ScannedGallery">
                                <span v-for="(file, index) in siteAttachments['SMScannedCopies']">
                                    <a :href="file.src" class="mfp-iframe" v-if="file['type'] === 'iframe'">
                                        <span><i class="fa fa-file-video-o videoIcon"></i></span>
                                    </a>
                                    <a :href="file.src" v-else>
                                        <img :src="file.src" class="note-thumbnail" :title="file.title">
                                    </a>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <strong>Manual Checklist Copy</strong>
                            <div id="ChecklistGallery">
                                <span v-for="(file, index) in siteAttachments['SMChecklistCopies']">
                                    <a :href="file.src" class="mfp-iframe" v-if="file['type'] === 'iframe'">
                                        <span><i class="fa fa-file-video-o videoIcon"></i></span>
                                    </a>
                                    <a :href="file.src" v-else>
                                        <img :src="file.src" class="note-thumbnail" :title="file.title">
                                    </a>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <strong>Site Photos and Videos</strong>
                            <div id="SiteGallery">
                                <span v-for="(file, index) in siteAttachments['SMPhotos']">
                                    <a :href="file.src" class="mfp-iframe" v-if="file['type'] === 'iframe'">
                                        <span><i class="fa fa-file-video-o videoIcon"></i></span>
                                    </a>
                                    <a :href="file.src" v-else>
                                        <img :src="file.src" class="note-thumbnail" :title="file.title">
                                    </a>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-header">
                    <div class="row">
                        <div class="col-md-5">
                            <h3 class="mr-tp-10 pd-tp-0 room-measure-title">Rooms</h3>
                        </div>
                    </div>
                </div>
                <!-- Legends -->
                <p class="caution measurement-caution" style="text-align: right;">
                    <span class="text-center no-text-transform">All measurements are in Inches</span>&nbsp;
                    <span class="pipe-color">|</span>&nbsp;
                    <i class="ion ion-images text-black" aria-hidden="true"></i>&nbsp; 
                    <span class="text-center no-text-transform">View Room Attachments</span>&nbsp;
                    <span class="pipe-color">|</span>&nbsp;
                    <i class="ion ion-clipboard text-black" aria-hidden="true"></i>&nbsp; 
                    <span class="text-center no-text-transform">View Notes</span>&nbsp;
                    <span class="pipe-color">|</span>&nbsp;
                    <i class="fa fa-eye text-black" aria-hidden="true"></i>&nbsp; 
                    <span class="text-center no-text-transform">View Item</span>&nbsp;
                </p>
                <div class="alert alert-info alert-dismissable" id="NoRoomFoundAlert" v-if="roomsData.length === 0">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                    <p>No Room Measurements found!.</p>
                </div>
                <div class="table-responsive" v-else>
                    <table class="table table-striped table-bordered" id="RoomsTableView">
                        <thead class="bg-light-blue text-center">
                            <tr>
                                <th width="23%" class="text-vertical-align text-center">
                                    Room <small data-toggle="tooltip" class="cursor-pointer room-legends" data-original-title="W: Width,  L: Length, H: Height">(W × L × H)</small>
                                </th>
                                <th width="18%" class="text-vertical-align text-center">Windows
                                    <small data-toggle="tooltip" class="cursor-pointer room-legends" data-original-title="W: Width, H: Height, HFF: Window height from floor">(W × H × HFF)</small>
                                </th>
                                <th width="12%" class="text-vertical-align text-center">Doors
                                    <small data-toggle="tooltip" class="cursor-pointer room-legends" data-original-title="W: Width, H: Height">(W × H)</small>
                                </th>
                                <th width="25%" class="text-vertical-align text-center">Furnitures
                                    <small data-toggle="tooltip" class="cursor-pointer room-legends" data-original-title="W: Width, H: Height, D: Depth">(W × H × D)</small>
                                </th>
                                <th width="9%" class="text-vertical-align text-center">Attachments</th>
                                <th width="6%" class="text-vertical-align text-center">AC<i class="fa fa-question-circle cursor-help info-icon mr-lt-2" data-toggle="tooltip" data-original-title="Add / Update Air Conditioners here"></i></th>
                                <th width="8%" class="text-vertical-align text-center">FSP<i class="fa fa-question-circle cursor-help info-icon mr-lt-2" data-toggle="tooltip" data-original-title="Add / Update Fire Sprinklers here"></i>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(room, index) in roomsData">
                                <td width="23%" class="text-vertical-align pd-tp-0 pd-bt-0">
                                    <h5 class="mr-tp-0 mr-bt-0"><b>@{{ room.roomarea }}</b><br>
                                        (@{{room.Width}} x @{{room.Length}} x @{{room.Height}})
                                        <span class="pull-right" id="RoomNotesSection" v-if="(room.roomnotes.length > 0 && room.roomnotes[0].Id !== '')">
                                            <a href="javascript:void(0);" data-toggle="tooltip" data-original-title="View notes" @click.prevent="openNotesPopup(index)">
                                                <i class="ion ion-clipboard text-black"></i>
                                            </a>    
                                            <room-notes :notes="room.roomnotes" :roomno="index"></room-notes>
                                        </span>
                                    </h5>
                                </td>
                                <td width="18%" class="text-vertical-align pd-bt-0">
                                    <p class="text-center" v-if="room.windows.windows.length === 0">
                                        <small>N/A</small>
                                    </p>
                                    <p v-for="(window, windownumber) in room.windows.windows" v-else>
                                        <strong>@{{windownumber+1}}: </strong>
                                        <span>@{{window.w}} x @{{window.h}} x @{{window.whf}}</span>
                                    </p>
                                </td>
                                <td width="12%" class="text-vertical-align pd-bt-0">
                                    <p class="text-center" v-if="room.doors.doors.length === 0"><small>N/A</small></p> 
                                    <p v-for="(door, doornumber) in room.doors.doors" v-else>
                                        <strong>@{{doornumber+1}}: </strong>
                                        <span>@{{door.w}} x @{{door.h}}</span>
                                    </p>
                                </td>
                                <td width="25%" class="text-vertical-align pd-bt-0 pd-tp-0">
                                    <p class="text-center" v-if="_.isEmpty(room.furnitures)"><small>N/A</small></p>
                                    <p v-else>
                                        <p class="text-center" v-if="room.furnitures.quantity < 1" style="margin-top: -8px;">
                                            <small>N/A</small>
                                        </p>
                                        <p v-for="(item, itemnumber) in room.furnitures.furnitures" v-else>
                                            <b v-html="getDesignItem(item.item)"></b>
                                            <span>@{{item.w}} x @{{item.h}} x @{{item.d}}</span>
                                        </p>
                                    </p>
                                </td>
                                <td width="9%" class="text-center text-vertical-align pd-tp-0 pd-bt-0">
                                    <span class="text-center" v-if="room.roomattachments.length === 0">
                                        <small>N/A</small>
                                    </span>
                                    <span data-toggle="tooltip" class="attachment-gallery" title="" data-original-title="View Room Attachments" v-else>
                                        <a
                                            :href="room.roomattachments[0].src" 
                                            :class="room.roomattachments[0].type" 
                                            @click.prevent="initializeRoomThumbnailsPopup(room.roomattachments)"
                                            >
                                            <i class="ion ion-images"></i>
                                        </a>
                                    </span>
                                </td>
                                <td width="6%" class="text-center text-vertical-align pd-tp-0 pd-bt-0">
                                    <a class="view-ic cursor-pointer" :href="GetRoomAcUrl+'/'+room.id" data-toggle="tooltip" data-original-title="View Ac" v-if="room.acspecifications">
                                       <i class="fa fa-eye" aria-hidden="true"></i>
                                    </a>
                                    <small v-else>N/A</small>
                                </td>
                                <td width="8%" class="text-center text-vertical-align pd-tp-0 pd-bt-0">
                                    <a class="view-fire-sp cursor-pointer" :href="GetFireSprinklersUrl+'/'+room.id" data-toggle="tooltip" data-original-title="View Fire Sprinkler(s)" v-if="room.firespspecifications">
                                       <i class="fa fa-eye" aria-hidden="true"></i>
                                    </a>
                                    <small v-else>N/A</small>
                                </td>                            
                            </tr>
                        </tbody>
                    </table>
                    <div class="row">
                        <div class="col-md-12">
                            <button name="Back" class="btn btn-primary btn-custom fl-rt back-btn mr-rt-10 mr-bt-10" onclick="window.location ='{{route('sitemeasurement.list')}}'">
                                Back
                            </button>
                        </div>
                    </div>
                </div>
                <div class="form-overlay hidden" id="UpdateSiteLoader">
                    <div class="large loader"></div>
                    <div class="loader-text">Updating Site Details</div>
                </div>
            </div>
        </div>
        <!-- Overlay component -->
        <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
    </div>
</div>
<small>* N/A: Data Not Available</small>
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl('/js/sitemeasurement/view.js') }}"></script>
@endsection