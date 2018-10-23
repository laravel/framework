@extends('layouts/master_template')
@section('dynamicStyles')
<link href="{{ asset('/css/magnific-popup.css') }}" rel="stylesheet"/>
<link href="{{ asset('/css/designs/view.css') }}" rel="stylesheet"/>
<link href="{{ asset('/css/designs/overlay.css') }}" rel="stylesheet"/>
<link rel="stylesheet" href="{{ URL::assetUrl("/css/designs/attachmentfilelist.css")}}">
<link rel="stylesheet" href="{{ URL::assetUrl('/css/designs/common.css')}}"/>
@endsection
@section('content')
<div id="DesignBlock" v-cloak>
    <div class="box box-primary">
        <div class="box-body">
            <div class="row">                
                <div class="form-group col-sm-12 col-md-8">
                    <b>Design Name</b>
                    <h5>@{{DesignDetails.ShortCode}} &nbsp;&nbsp;&nbsp;&nbsp;
                        <strong 
                            v-bind:class="[DesignDetails.Status==3 ? 'label-success' : 'label-primary', 'label']">
                            @{{ DesignStatuses[DesignDetails.Status]}}
                        </strong>
                    </h5>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <site-information :site-info="SiteDetails"></site-information>
                    </div>
                </div>
            </div>
            <input type="hidden" :value="DesignDetails.Id" id="DesignId"/>
            <div v-if="Attachments.length>0" class="attachment_container" id="Attachments">                        
                <attachment-section :attachments="Attachments" :attachment-count="AttachmentCount" :page-url="PageUrl" :design-id="DesignDetails.Id" @showmore="getAttachments" @showlatest="showLatestAttachments"></attachment-section>
            </div>
            <div class="row mr-tp-20 mr-bt-10">
                <div class="col-md-12">
                    <form action="{{route('mydesigns.usercomments')}}" id="commentForm" method="post">
                        <div class="row mr-tp-10" :class="{hidden: SubmitTypeBox}" id="DesignStatus">
                             <div id="Submit-type-box">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div>
                                            <input type="radio" name="SubmitType" value="DesignApprove" class="input-radio" id="DesignApprove"/>
                                            <label for="DesignApprove" tabindex="0"></label>
                                            <label for="DesignApprove" class="text-normal cursor-pointer mr-rt-20">Approve</label>
                                            <input type="radio" name="SubmitType" value="ChangeRequest" class="input-radio" id="ChangeRequest" />
                                            <label for="ChangeRequest" tabindex="-1"></label>
                                            <label for="ChangeRequest" class="text-normal cursor-pointer">Request for Change</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='mr-tp-20' :class="{hidden: CommentBox}" id="comment-box">
                             <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="input-group" data-toggle="tooltip" title="Click here to upload files">
                                            <label for="UploadFiles" class="cursor-pointer">Upload Images&nbsp;&nbsp; <small>(Optional)</small></label> 
                                            <label class="input-group-addon cursor-pointer upload-addon" for="UploadFiles">
                                                <i class="fa fa-paperclip"></i>
                                            </label>
                                        </div>
                                        <input type="file" name="UploadFiles[]" id="UploadFiles" @change.prevent="onFileUploadChange($event, AllUploadFiles)" class="form-control hidden" multiple="multiple" accept="image/*"/>
                                    </div>
                                    <div class="row filenamesbox">
                                        <div class="col-md-12 ">
                                            <filenames-list :attachment-list="AllUploadFiles" :attachment-name="'UploadFiles'" @deletefile="deleteFiles"></filenames-list>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label id="DescriptionLabel" class="label-control" for="Description" >Add Comment*</label>
                                        <textarea name="Description" rows="4" id="Description" class="form-control no-resize-input" placeholder="Ex: Design attachments not getting."></textarea>
                                    </div>
                                </div>
                                <div class="col-md-4 mr-tp-25">
                                    <input type="submit" name="DesignCommentFormSubmit" value="Submit" class="btn btn-primary button-custom" id="DesignCommentFormSubmit"/> 
                                </div>
                            </div>
                            <input type="hidden" name="DesignId" :value="DesignDetails.Id" id="DesignId">
                            <input type="hidden" name="ShortCode" :value="DesignDetails.ShortCode" id="ShortCode">
                        </div>
                    </form>
                </div>
            </div>
            <div id="NotificationArea" class="hidden">
                <div class="alert alert-dismissible"></div>
            </div>
            <!-- box-body --> 
            <div v-if="CommentsData.CommentsCount>0">
                <comment-section :comments="CommentsData.Comments" :customer-role="Customer" :short-code="DesignDetails.ShortCode" @delcomment="confirmDeleteComment"></comment-section>
                <div class="row">
                    <div class="col-md-12" style="text-align:center;">
                        <button v-if="CommentsData.Comments.length<CommentsData.CommentsCount" @click.prevent="fetchData('/designs/comments/'+DesignDetails.Id+'/'+Offset, 'Comments')" class="btn btn btn-primary btn-flat mr-tp-10 mr-rt-12 ShowMore">Show More</button>
                        <button v-if="CommentsData.Comments.length>10" @click.prevent="showLatestComments()" class="btn btn btn-primary btn-flat mr-tp-10 mr-lt-12 ShowMore">Show Latest</button>
                    </div>
                </div>
            </div>
            <div class="form-overlay" :class="{hidden: Loader}" id="DesignFormOverlay">
                 <div class="large loader"></div>
                <div class="loader-text">@{{OverLayMessage}}</div>
            </div>
            <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
        </div>
    </div>
    <div class="modal fade" id="ConfirmationModal" tabindex="-1" role="dialog" >
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Confirm</h4>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the Idea?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary pull-left" @click="deleteComment">Yes</button>
                    <button type="button" class="btn pull-left mr-lt-10" data-dismiss="modal">No</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ asset('/js/designs/customerview.js') }}"></script>
<script src="{{ asset('/js/common.js') }}"></script>
@endsection