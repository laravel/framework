@extends('layouts/master_template')
@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.css') }}"/>
<link rel="stylesheet" href="{{ URL::assetUrl("/css/magnific-popup.css")}}">
<link rel="stylesheet" href="{{ URL::assetUrl("/css/designs/update.css")}}">
<link rel="stylesheet" href="{{ URL::assetUrl('/css/designs/overlay.css') }}"/>
<link rel="stylesheet" href="{{ URL::assetUrl('/css/designs/attachmentfilelist.css')}}"/>
<link rel="stylesheet" href="{{ URL::assetUrl('/css/designs/common.css')}}"/>
@endsection
@section('content')
<div class="row" id="UpdateDesignBlock" v-cloak>
    <user-information v-if="CustomerDetails" :user-info="CustomerDetails"></user-information>
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-body">
                <div class="row">     
                    <div class="form-group col-sm-12 col-md-5">
                        <label>Design</label>
                        <select name="Designs" id="Designs" class="form-control">
                            @foreach($DropDown as $Key => $Design)
                            <option value="{{$Design->Id}}" {{($Design->Id == $DesignData['DesignDetails']->Id) ? 'selected="selected"' :  ''}}>{{$Design->ShortCode}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <p v-if="Attachments.length>0" class="text-blue pd-tp-30">
                            <strong 
                                v-bind:class="[DesignDetails.Status==3 ? 'label-success' : 'label-primary', 'label']">
                                @{{ DesignStatuses[DesignDetails.Status]}}
                            </strong>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <site-information :site-info="SiteDetails"></site-information>
                        </div>
                    </div>
                </div> 
                <div v-if="Attachments.length>0" id="AttachmentFileBox" class="mr-bt-30">
                    <attachment-section :attachments="Attachments" :attachment-count="AttachmentCount" :page-url="PageUrl" :design-id="DesignDetails.Id" @showmore="getAttachments" @showlatest="showLatestAttachments"></attachment-section>
                </div>
                <div class="submit-to-customer" :class="{hidden: ChangeRequestblock}" id="change-request-block">
                     <div class="row">
                        <form action="" method="POST" accept-charset="utf-8" id="Updateform">                
                            <div v-if="DesignDetails.design_attachment.length>0" class="col-md-12 mr-bt-10">
                                <div class="form-group">
                                    <input v-if="Designer && DesignDetails.Status==2" type="radio" name="UpdateType" value="ReplyWidExp" v-model="PickedOption" class="input-radio" id="ReplyWidExp"/>
                                    <label v-if="Designer && DesignDetails.Status==2" for="ReplyWidExp" tabindex="0"></label>
                                    <label v-if="Designer && DesignDetails.Status==2" for="ReplyWidExp" class="text-normal cursor-pointer mr-rt-20">Reply with Explanation</label>
                                    <input type="radio" name="UpdateType" value="UpdateRevDesig" v-model="PickedOption" class="input-radio" id="UpdateRevDesig" />
                                    <label for="UpdateRevDesig" tabindex="-1"></label>
                                    <label for="UpdateRevDesig" class="design-label cursor-pointer mr-rt-20">@{{RadioButtonLabel}}</label>
                                    <input :class="{hidden: ThreeDUpdate}" type="radio" name="UpdateType" value="Update3DDesign" v-model="PickedOption" class="input-radio" id="Update3DDesign" />
                                        <label :class="{hidden: ThreeDUpdate}" for="Update3DDesign" tabindex="-1"></label>
                                    <label :class="{hidden: ThreeDUpdate}" for="Update3DDesign" class="text-normal cursor-pointer">Add 3D Design to Existing Version</label>
                                </div>
                            </div>
                            <div :class="{hidden: UpdateRevisedDesignBox}" id="UpdateRevisedDesignBox">
                                <div v-for="Attachments in AttachmentFields">
                                    <div v-if="Attachments.Id!=='RefImages'" class="col-md-12">
                                        <div class="form-group">
                                            <div class="input-group" data-toggle="tooltip" title="Click here to upload files">
                                                <label :for="Attachments.Id" class="cursor-pointer">@{{Attachments.Label}} <span class="text-danger"> @{{Attachments.Required}}</span></label> 
                                                <label class="input-group-addon cursor-pointer upload-addon" :for="Attachments.Id">
                                                    <i class="fa fa-paperclip"></i>
                                                </label>
                                            </div>
                                            <div v-if="Attachments.Id=='2DWithoutDimensions'">
                                                <input type="file" :name="Attachments.Id+'[]'" @change.prevent="onFileUploadChange($event, AllTwoDWidthoutDFiles)" class="form-control hidden" :id="Attachments.Id" :accept="Attachments.AcceptType" multiple="multiple"/>
                                            </div>
                                            <div v-if="Attachments.Id=='2DWidDimensions'">
                                                <input type="file" :name="Attachments.Id+'[]'" @change.prevent="onFileUploadChange($event, AllTwoDFiles)" class="form-control hidden" :id="Attachments.Id" :accept="Attachments.AcceptType" multiple="multiple"/>
                                            </div>
                                            <div v-if="Attachments.Id=='3D'">
                                                <input type="file" :name="Attachments.Id+'[]'" @change.prevent="onFileUploadChange($event, AllThreeDFiles)" class="form-control hidden" :id="Attachments.Id" :accept="Attachments.AcceptType" multiple="multiple"/>
                                            </div>
                                        </div>
                                        <div class="row filenamesbox">
                                            <div class="col-md-12 ">
                                                <div v-if="Attachments.Id=='2DWithoutDimensions'">
                                                    <filenames-list :attachment-list="AllTwoDWidthoutDFiles" :attachment-name="'2DWithoutDimensions'" @deletefile="deleteFiles"></filenames-list>
                                                </div>
                                                <div v-if="Attachments.Id=='2DWidDimensions'">
                                                    <filenames-list :attachment-list="AllTwoDFiles" :attachment-name="'2DWidDimensions'" @deletefile="deleteFiles"></filenames-list>
                                                </div>
                                                <div v-if="Attachments.Id=='3D'">
                                                    <filenames-list :attachment-list="AllThreeDFiles" :attachment-name="'3D'" @deletefile="deleteFiles"></filenames-list>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="update-comment-box" :class ="{hidden: UpdateCommentBox}">
                                 <div v-for="Attachments in AttachmentFields">
                                    <div v-if="Attachments.Id==='RefImages'" class="col-md-12">
                                        <div class="form-group">
                                            <div v-if="Attachments.Id==='RefImages'"  class="input-group" data-toggle="tooltip" title="Click here to upload files">
                                                <label :for="Attachments.Id" class="cursor-pointer">@{{Attachments.Label}} <span class="text-danger"> @{{Attachments.Required}}</span></label> 
                                                <label class="input-group-addon cursor-pointer upload-addon" :for="Attachments.Id">
                                                    <i class="fa fa-paperclip"></i>
                                                </label>
                                            </div>
                                            <div v-if="Attachments.Id=='RefImages'">
                                                <input type="file" :name="Attachments.Id+'[]'" @change.prevent="onFileUploadChange($event, AllRefImages)" class="form-control hidden" :id="Attachments.Id" :accept="Attachments.AcceptType" multiple="multiple"/>
                                            </div>
                                        </div>
                                        <div class="row filenamesbox">
                                            <div class="col-md-12 ">
                                                <div v-if="Attachments.Id=='RefImages'">
                                                    <filenames-list :attachment-list="AllRefImages" :attachment-name="'RefImages'" @deletefile="deleteFiles"></filenames-list>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label class="label-control" for="" id="Update-Description-Label">Comment*</label>
                                                <textarea rows="5" id="AttachmentComment" name="AttachmentComment" class="form-control no-resize-input" placeholder="Comment for the attachments."></textarea>
                                            </div>
                                        </div> 
                                        <div class="col-md-2">
                                            <div class="form-group mr-tp-25">
                                                <input type="submit" name="submit" value="Submit" class="btn btn-primary button-custom" id="AttachmentSubmit" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>    
                        </form>
                        <div class="col-md-12" id="ThreeDDesignUpdateBox" :class ="{hidden: ThreeDDesignUpdateBox}">
                             <form action="" method="POST" accept-charset="utf-8" id="ThreeDDesignUpdate">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <div class="input-group" data-toggle="tooltip" title="Click here to upload files">
                                                <label for="ThreeDDesign" class="cursor-pointer">3D Design*</label> 
                                                <label class="input-group-addon cursor-pointer upload-addon" for="ThreeDDesign">
                                                    <i class="fa fa-paperclip"></i>
                                                </label>
                                            </div>
                                            <input type="file" name="ThreeDDesign[]" @change.prevent="onFileUploadChange($event, UpdateThreeDFiles)" class="form-control hidden" id="ThreeDDesign" accept=".jpeg, .jpg, .png, .bmp, .pdf" multiple="multiple"/>
                                        </div>
                                    </div>
                                    <div class="col-md-12  filenamesbox">
                                        <filenames-list :attachment-list="UpdateThreeDFiles" :attachment-name="'ThreeDDesign'" @deletefile="deleteFiles"></filenames-list>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="ThreeDVersion">Version*</label>
                                            <select name="ThreeDVersion" id="ThreeDVersion" class="form-control">
                                                <option v-for="value in ThreeDVerArray" :value="value">@{{value}}</option>
                                                <option :value="AttachmentCount" selected="selected">Latest Version</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group mr-tp-5">
                                            <input type="submit" name="submit" value="Submit" class="btn btn-primary button-custom" />
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div> 
                <h4 class="header-color" :class ="{hidden: AddCommentBox}">Add Comment</h4>                        
                <div class="normal-comment-block" :class ="{hidden: AddCommentBox}">
                     <form action="" method="POST" accept-charset="utf-8" id="CommentForm">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="input-group" data-toggle="tooltip" title="Click here to upload files">
                                        <label for="CommentAttachments" class="cursor-pointer">Upload Images&nbsp;&nbsp; <small>(Optional)</small></label> 
                                        <label class="input-group-addon cursor-pointer upload-addon" for="CommentAttachments">
                                            <i class="fa fa-paperclip"></i>
                                        </label>
                                    </div>
                                    <input type="file" name="CommentAttachments[]" id="CommentAttachments" @change.prevent="onFileUploadChange($event, CommentAttachments)" class="form-control hidden" multiple="multiple" accept="image/*"/>

                                </div>
                                <div class="row filenamesbox">
                                    <div class="col-md-12 ">
                                        <filenames-list :attachment-list="CommentAttachments" :attachment-name="'CommentAttachments'" @deletefile="deleteFiles"></filenames-list>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <textarea rows="5" id="CommentText" name="CommentText" class="form-control no-resize-input" placeholder="Click on submit to post the comment"></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-md-12 ">
                                            <input type="submit" name="submit" value="Submit" class="btn btn-primary button-custom" id="CommentSubmit" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>               
                    </form>
                </div>
                <div v-if="CommentsData.CommentsCount>0" >
                    <comment-section :comments="CommentsData.Comments" :customer-role="Customer" :short-code="DesignDetails.ShortCode" :reply-statuses="ReplyStatuses" @delcomment="confirmDeleteComment" @changestatus="onChnageStatus"></comment-section>
                    <div class="row">
                        <div class="col-md-12" style="text-align:center;">
                            <button v-if="CommentsData.Comments.length<CommentsData.CommentsCount" @click.prevent="fetchData('/designs/comments/'+DesignDetails.Id+'/'+Offset, 'Comments')" class="btn btn btn-primary btn-flat mr-tp-10 mr-rt-12 ShowMore">Show More</button>
                            <button v-if="CommentsData.Comments.length>10" @click.prevent="showLatestComments()" class="btn btn btn-primary btn-flat mr-tp-10 mr-lt-12 ShowMore">Show Latest</button>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="DesignId" value="{{$DesignData['DesignDetails']->Id}}" id="DesignId">
                <input type="hidden" name="ShortCode" value="{{$DesignData['DesignDetails']->ShortCode}}" id="ShortCode">
                <input type="hidden" name="CustomerId" value="{{$DesignData['DesignDetails']->UserId}}" id="CustomerId">
            </div>
        </div>
        <div class="form-overlay" :class="{hidden: Loader}" id="Loader">
             <div class="large loader"></div>
            <div class="loader-text">@{{LoaderMessage}}</div>
        </div>
        <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
        <small>* N/A: Data Not Available</small>
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
<script src="{{ URL::assetUrl('/js/designs/update.js') }}"></script>
<script src="{{ URL::assetUrl('/js/common.js') }}"></script>
@endsection