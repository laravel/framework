@extends('layouts/master_template')

@section('dynamicStyles')
<link rel="stylesheet" href="{{ asset('/plugins/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/sitemeasurement/create.css') }}">
@endsection

@section('content')
<div id="CreateSiteMeasurementPage" v-cloak>
    <div class="row">
        <div class="col-md-12 text-right custom-info-block" :class="{ hidden : (enquiryProject == null) }">
             <span class="pd-5 text-capitalize user-info">
                <i class="fa fa-user text-info" aria-hidden="true"></i>&nbsp;
                @{{ !(enquiryProject == null) ? enquiryProject.userName : '' }}
            </span>
            <span class="pd-5 user-info">
                <i class="fa fa-phone-square text-info" aria-hidden="true"></i>&nbsp;
                @{{ !(enquiryProject == null) ? enquiryProject.mobile: '' }}
            </span>
            <span class="pd-5 user-info"> 
                <i class="fa fa-envelope-square text-info" aria-hidden="true"></i>&nbsp;
                @{{ !(enquiryProject == null) ? enquiryProject.email: '' }}
            </span>
        </div>
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body" v-if="!isRolesExists">
                    <div class="callout callout-info mr-8">
                        <p>It seems Reviewer and Approver roles have not been added yet.</p>
                    </div>
                </div>
                <div class="box-body" v-else>
                    <div class="callout callout-info mr-8" v-if="projects.length === 0">
                        @if(Auth::User()->IsManager())  
                        <p>No Projects found!. Click here to <a href="{{route('pnintegration.add.project')}}" title="Add a Project">Add a Project</a>.</p>
                        @else 
                        <p>It seems projects are not assigned (or) you may not add site measurement to the projects at this stage.</p>
                        @endif
                    </div>
                    <div v-else>
                        <form id="CreateSiteMeasurement" method="POST" action="{{ route('sitemeasurement.store')}}" enctype="multipart/form-data">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="Projects">Project*</label>
                                        <select name="Projects" id="Projects" class="form-control">
                                            <option value="">Select a Project</option>                                        
                                            <option v-for="project in projects" :value="project.Id">@{{ project.Name }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group has-feedback">
                                        <label for="Description">Notes</label>
                                        <textarea name="Description" id="Description" class="form-control" rows="2" placeholder='Ex: Pooja Room' style="resize:none"></textarea>
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
                                <div class="col-md-12">
                                    <p class="caution mr-bt-8">
                                        (
                                        <span>Upload photo/scanned copy of SM and Checklist</span>
                                        )
                                    </p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mr-bt-0">
                                        <div class="input-group" data-toggle="tooltip" title="Click here to upload files">
                                            <label for="SMCopy" class="cursor-pointer">SM Scanned Copy*</label> 
                                            <label class="input-group-addon cursor-pointer upload-addon" for="SMCopy">
                                                <i class="fa fa-paperclip"></i>
                                            </label>
                                        </div>
                                        <input type="file" name='SMCopy[]' @change.prevent="onFileUploadChange($event, totalScannedCopies)" class="hidden" accept="image/*" id="SMCopy" multiple="multiple" class="form-control"/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <site-scannedcopy-upload :scanned-attachments-list="totalScannedCopies" v-show="shouldRenderScannedCopies" @deletescannedcopies="deleteFiles"></site-scannedcopy-upload>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mr-bt-0">         
                                        <div class="input-group" data-toggle="tooltip" title="Click here to upload files">
                                            <label for="ChecklistCopy" class="cursor-pointer">Manual Checklist Copy*</label> 
                                            <label class="input-group-addon cursor-pointer upload-addon" for="ChecklistCopy">
                                                <i class="fa fa-paperclip"></i>
                                            </label>             
                                        </div>
                                        <input type="file" name='ChecklistCopy[]' @change.prevent="onFileUploadChange($event, totalChecklistCopies)" class="hidden" accept="image/*" id="ChecklistCopy" multiple="multiple" class="form-control"/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <site-checklist-upload :checklist-attachments-list="totalChecklistCopies" v-show="shouldRenderChecklistCopies" @deletechecklistcopies="deleteFiles"></site-checklist-upload>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <p class="caution mr-bt-8">
                                        (
                                        <span>Please upload photos and videos common for complete site here</span> <span class="pipe-color">|</span> 
                                        <span>Room specific photos and videos should be added at Room level</span> <span class="pipe-color">|</span>
                                        <span>Video for complete site should be added here</span>
                                        )
                                    </p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <div class="input-group" data-toggle="tooltip" title="Click here to upload files">
                                            <label for="SiteImages" class="cursor-pointer">Site Photos and Videos*</label> 
                                            <label class="input-group-addon cursor-pointer upload-addon" for="SiteImages">
                                                <i class="fa fa-paperclip"></i>
                                            </label>
                                        </div>
                                        <input type="file" name='SiteImages[]' @change.prevent="onFileUploadChange($event, totalSitePhotos)" class="hidden" accept="image/*|video/*" id="SiteImages" multiple="multiple" class="form-control"/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 site-images">
                                    <site-attachments-upload :site-attachments-list="totalSitePhotos" v-show="shouldRenderFiles" @deletefile="deleteFiles"></site-attachments-upload>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12">
                                    <p>
                                        <input type="submit" class="btn btn-primary button-custom" value="Save" id="SiteMeasurementFormSubmit" />
                                        <input type="reset" class="btn button-custom" value="Clear" id="SiteMeasurementFormReset" />
                                    </p>
                                </div>
                            </div>
                            <input type="hidden" name="ShortCode" id="ShortCode"/>
                        </form>
                        <div class="form-loader" id="SiteMeasurementFormOverlay" v-if="showOverlay">Saving</div>
                        <div class="form-loader" id="FetchLoader" v-if="showFetchDataOverlay">Fetching</div>
                    </div>
                </div>
            </div>
            <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
        </div>
    </div>
</div>
@endsection

@section('dynamicScripts')
<script src="{{asset('/js/sitemeasurement/create.js')}}"></script>
<script src="{{ asset('js/common.js') }}"></script>
@endsection
