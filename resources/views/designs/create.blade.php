@extends('layouts/master_template')
@section('content')
<div id="CreateDesignBlock" v-cloak>
    <div class="row">
        <user-information v-if="CustomerDetails" :user-info="CustomerDetails"></user-information>
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    @if(!$Customers->isEmpty())
                    <form action="{{ route('mydesigns.store') }}" method="POST" accept-charset="utf-8" id="AddDesignForm" enctype="multipart/form-data">                       
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="Customer">Customer*</label>
                                    <select name="Customer" id="Customer" class="form-control placeholder-placement">
                                        <option value="">Select a Customer</option>
                                        @foreach($Customers as $Key => $Customer)
                                        <option value="{{ $Customer->Id }}" email="{{ $Customer->Email }}">{{ ucwords(strtolower($Customer->Person->FirstName." ". $Customer->Person->LastName)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="Projects">Project*</label>
                                    <select name="Projects" id="Projects" class="form-control placeholder-placement" >
                                        <option value="">Select a Project</option>
                                        <option v-for="Project in Projects" :value="Project.Id" >@{{Project.Name}}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="Rooms">Room*</label>
                                    <select name="Rooms" id="Rooms" class="form-control placeholder-placement">
                                        <option value="" ShortCode="">Select a Room</option>
                                        <option v-for="Room in Rooms" :value="Room.Id" :Shortcode="Room.ShortCode">@{{Room.Name}}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3" id="DesignItemDiv">
                                <div class="form-group">
                                    <label for="RoomItem">Design Item*</label>
                                    <select name="RoomItem" id="RoomItem" class="form-control placeholder-placement">
                                        <option value="">Select a Item</option>
                                        @foreach($items as $item)
                                        <option value="{{ $item->Id }}" Shortcode="{{$item->Code}}">{{ $item->Name }}</option>
                                        @endforeach
                                    </select>                                         
                                </div>
                            </div>
                        </div>
                        <div v-for="Attachments in DesignAttachmentFields" class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="input-group" data-toggle="tooltip" title="Click here to upload files">
                                        <label :for="Attachments.Id" class="cursor-pointer">@{{Attachments.Label}} <span class="text-danger"> @{{Attachments.Required}}</span></label> 
                                        <label class="input-group-addon cursor-pointer upload-addon" :for="Attachments.Id">
                                            <i class="fa fa-paperclip"></i>
                                        </label>
                                    </div>
                                    <div v-if="Attachments.Id=='TwoDWithoutDFile'">
                                        <input type="file" :name="Attachments.Id+'[]'" @change.prevent="onFileUploadChange($event, AllTwoDWidthoutDFiles)" class="form-control hidden" :id="Attachments.Id" :accept="Attachments.AcceptType" multiple="multiple"/>
                                    </div>
                                    <div v-if="Attachments.Id=='TwoDFile'">
                                        <input type="file" :name="Attachments.Id+'[]'" @change.prevent="onFileUploadChange($event, AllTwoDFiles)" class="form-control hidden" :id="Attachments.Id" :accept="Attachments.AcceptType" multiple="multiple"/>
                                    </div>
                                    <div v-if="Attachments.Id=='ThreeDDesign'">
                                        <input type="file" :name="Attachments.Id+'[]'" @change.prevent="onFileUploadChange($event, AllThreeDFiles)" class="form-control hidden" :id="Attachments.Id" :accept="Attachments.AcceptType" multiple="multiple"/>
                                    </div>
                                    <div v-if="Attachments.Id=='RefImages'">
                                        <input type="file" :name="Attachments.Id+'[]'" @change.prevent="onFileUploadChange($event, AllRefImages)" class="form-control hidden" :id="Attachments.Id" :accept="Attachments.AcceptType" multiple="multiple"/>
                                    </div>
                                </div>
                                <div class="row filenamesbox">
                                    <div class="col-md-12 ">
                                        <div v-if="Attachments.Id=='TwoDWithoutDFile'">
                                            <filenames-list :attachment-list="AllTwoDWidthoutDFiles" :attachment-name="'TwoDWithoutDFile'" @deletefile="deleteFiles"></filenames-list>
                                        </div>
                                        <div v-if="Attachments.Id=='TwoDFile'">
                                            <filenames-list :attachment-list="AllTwoDFiles" :attachment-name="'TwoDFile'" @deletefile="deleteFiles"></filenames-list>
                                        </div>
                                        <div v-if="Attachments.Id=='ThreeDDesign'">
                                            <filenames-list :attachment-list="AllThreeDFiles" :attachment-name="'ThreeDDesign'" @deletefile="deleteFiles"></filenames-list>
                                        </div>
                                        <div v-if="Attachments.Id=='RefImages'">
                                            <filenames-list :attachment-list="AllRefImages" :attachment-name="'RefImages'" @deletefile="deleteFiles"></filenames-list>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="note-color"><small>Total size for all files should not exceed 10 MB | PDF file created by designer should not be greater than 2 MB</small></p>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="DesignHeading">Design Name*</label>
                                    <input type="text" v-model="DisplayDesignName" readonly name="DesignHeading" id="DesignHeading" class="form-control" placeholder="Ex: LDV: D603: ASC: HY: Design for MBR - Wardrobe" data-entity-existence-url="{{ route('check.designshortcode') }}"/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label for="Notes">Designer's Notes to be added to Email</label>
                                    <a href="#" id="EmailTemplateView" @click.prevent="showEmailTemplatePopUP()" class="pull-right">Click here to view complete email</a>
                                    <textarea name="Notes" id="Notes" v-model="DesignerNotes" class="form-control no-resize-input" rows="5" placeholder="Ex:- Please note that there is little space in South-west corner in your apartment and we have designed considering the same."></textarea>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="note-color"><small>Note: On "Submit" an email will be sent to Customer with above notes</small></p>
                        </div>
                        <div class="row">
                            <div class="col-md-8 col-sm-12">
                                <p>
                                    <input type="reset" name="DesignFormReset" value="Clear" class="btn button-custom" id="DesignFormReset"/>
                                    <input type="submit" name="DesignFormFormSubmit" value="Submit" class="btn btn-primary button-custom" id="DesignFormFormSubmit"/>                                   
                                </p>
                            </div>
                        </div>
                    </form>
                    @else
                    <div class="callout callout-info">
                        @if(Auth::User()->IsManager())  
                        <p>No Projects found!. Click here to <a href="{{route('pnintegration.add.project')}}" title="Add a Project">Add a Project</a>.</p>
                        @else 
                        <p>It seems projects are not assigned (or) you may not create design for the projects at this stage.</p>
                        @endif
                    </div>
                    @endif
                </div>             
            </div>
            <div class="form-overlay" :class="{hidden: DesignFormOverlay}" id="DesignFormOverlay">
                 <div class="large loader"></div>
                <div class="loader-text">@{{OverLayMessage}}</div>
            </div>
            <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
        </div>
    </div>
    <div class="modal fade" id="EmailTemplate" tabindex="-1" role="dialog" >
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Email</h4>
                </div>
                <div class="modal-body">To : <br>
                    Subject :  @{{TemplateSubject}}<br>
                    <br>
                    Dear @{{CustomerName}},
                    <br>
                    <br>
                    Greetings from HECHPE Spaces team. <b>@{{TemplateFrom}}</b> has submitted the Design for approval.
                    <br>
                    <br>
                    <p>Click the button below to view  the Design.<br>
                        VIEW<br>
                        OR<br>
                        Copy and paste the following link into your browser:<br>
                        @{{UpdateDesignUrl}}
                    <p v-if="DesignerNotes.length>0"><br><strong>Designer Notes: </strong>@{{DesignerNotes}}</p>
                    <br>
                    <br>
                    Regards,<br>
                    Support Team<br>
                    @{{TemplateTeam}}<br>
                    @{{TemplateUrl}}</p>
                    <p>
                        <small>
                            <b>Note:</b>
                            <i>Please do not reply to this email. It has been sent from an email account that is not monitored.</i><br>
                            <i>To ensure that you receive communication related to your request from http://www.hechpe.com, please add&nbsp;Pawan Kumar Sadvaleto your contact list/address book.</i>
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicStyles')
<link rel="stylesheet" href="{{ asset('/plugins/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/designs/common.css') }}">
<link rel="stylesheet" href="{{ asset('css/designs/create.css') }}">
<link rel="stylesheet" href="{{ asset('css/designs/attachmentfilelist.css') }}">
<link rel="stylesheet" href="{{ asset('css/designs/overlay.css') }}">
@endsection

@section('dynamicScripts')
<script src="{{ asset('js/designs/create.js') }}"></script>
<script src="{{ asset('js/common.js') }}"></script>
@endsection
