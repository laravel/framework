@extends('layouts/master_template')
@section('dynamicStyles')
<link href="{{ asset('/css/magnific-popup.css') }}" rel="stylesheet"/>
<link rel="stylesheet" href="{{ URL::assetUrl("/css/designs/update.css")}}">
<link rel="stylesheet" href="{{ URL::assetUrl("/css/designs/overlay.css")}}">
<link rel="stylesheet" href="{{ URL::assetUrl('/css/designs/common.css')}}"/>
@endsection
@section('content')
<div class="row" id="DesignViewBlock" v-cloak>
    <user-information v-if="CustomerDetails" :user-info="CustomerDetails"></user-information>
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-body">
                <div class="row">                
                    <div class="form-group col-sm-12 col-md-8">
                        <b>Design Name</b>
                        <input type="hidden" :value="DesignDetails.Id" id="DesignId"/>
                        <h5>@{{DesignDetails.ShortCode}}&nbsp;&nbsp;&nbsp;&nbsp;
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
                <div v-if="Attachments.length>0" class="attachment_container mr-bt-30" id="Attachments">                        
                    <attachment-section :attachments="Attachments" :attachment-count="AttachmentCount" :page-url="PageUrl" :design-id="DesignDetails.Id" @showmore="getAttachments" @showlatest="showLatestAttachments"></attachment-section>
                </div>
                <div id="NotificationArea"></div>
                <!-- box-body --> 
                <div v-if="CommentsData.CommentsCount>0" class="mr-tp-20">
                    <comment-section :comments="CommentsData.Comments" :short-code="DesignDetails.ShortCode"></comment-section>
                    <div class="row">
                        <div class="col-md-12" style="text-align:center;">
                            <button v-if="CommentsData.Comments.length<CommentsData.CommentsCount" @click.prevent="fetchData('/designs/comments/'+DesignDetails.Id+'/'+Offset, 'Comments')" class="btn btn btn-primary btn-flat mr-tp-10 mr-rt-12 ShowMore">Show More</button>
                            <button v-if="CommentsData.Comments.length>10" @click.prevent="showLatestComments()" class="btn btn btn-primary btn-flat mr-tp-10 mr-lt-12 ShowMore">Show Latest</button>
                        </div>
                    </div>
                </div>
                <div class="form-overlay" :class="{hidden: Loader}" id="ShowMoreFormOverlay">
                     <div class="large loader"></div>
                    <div class="loader-text"></div>
                </div>
                <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
            </div>
        </div>
    </div>       
</div>
@endsection

@section('dynamicScripts')
<!-- Magnific Popup core JS file -->
<script src="{{ asset('/js/common.js') }}"></script>
<script src="{{ asset('/js/designs/managerNDesigner.js') }}"></script>
@endsection