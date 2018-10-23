@extends('layouts/master_template')
@section('content')
<div class="row">
    <div class="col-md-12 text-right addNew-block" style="padding-right: 2em;">
        <a class="btn btn-primary button-custom fl-rt AddButton" id="AddNoteCategory" data-toggle="tooltip" title="Click here to Add new Notes"> <i class="fa fa-fw fa-plus-square"></i> New Notes</a>
    </div>
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border mr-tp-10">
                <div class="row">     
                    <div class="form-group col-sm-12 col-md-5">
                        <label for="">Note Category*</label>
                        <select name="NoteCategory" id="NoteCategory" class="form-control">
                            <option value="">Select Note Category</option>
                            @foreach($NoteCategory as $Value)
                            <option value="{{$Value->Id}}" {{isset($NoteCategoryDetails)&& ($Value->Id ==$NoteCategoryDetails->Id)?'selected="selected"':""}}>{{$Value->Name}}</option>
                            @endforeach
                        </select>
                    </div>
                </div> 
            </div>
            <div id="NewNoteCategory" class="hidden">
                <div class="box-header with-border ">
                    <h3 class="box-title no-text-transform">New Note Category</h3>
                </div>
                <div class="box-body">
                    <form action="" method="POST" accept-charset="utf-8" id="NewNoteCategoryForm">
                        <div class="row">
                            <div class="mr-tp-16 mr-bt-10">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="Name">Name*</label>
                                        <input autocomplete="off" type="text" name="Name" placeholder='Ex: Pillar Placement'  id="Name" class="form-control" value=""/>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="no-text-transform">Status</label>
                                        <div class="mr-tp-6">
                                            <input type="radio" name="IsActive" id="IsActiveYes" checked="checked" value="Active" class="input-radio"/>
                                            <label for="IsActiveYes" tabindex="0"></label>
                                            <label for="IsActiveYes" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                            <input type="radio" name="IsActive" id="IsActiveNo" value="Inactive" class="input-radio">
                                            <label for="IsActiveNo" tabindex="0"></label>
                                            <label for="IsActiveNo" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> 
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="Description">Description</label>
                                    <textarea name="Description" id="Description" class="form-control no-resize-input" rows="4" placeholder='Ex: Pillar placed in between the walls'></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-5" style="text-align:center">
                                <p>
                                    <input type="submit" name="NewNoteCatSubmit" value="Save" class="btn btn-primary button-custom" id="NewNoteCatSubmit">
                                    <input type="reset" name="NewNoteCatReset" value="Clear" class="btn button-custom" id="NewNoteCatReset">
                                </p>
                            </div>
                        </div>
                    </form>
                    <div class="mr-tp-5">*:&nbsp;<small>Mandatory fields</small></div>
                </div>
            </div>
            @if ($ViewType == 'Edit' && isset($NoteCategoryDetails))
            <div id="UpdateNoteCategory">
                <div class="box-header with-border ">
                    <h3 class="box-title no-text-transform">Edit Note Category</h3>
                </div>
                <div class="box-body">
                    <form action="" method="POST" accept-charset="utf-8" id="UpdateNoteCategoryForm">
                        <input type="hidden" name="Id" value="{{$NoteCategoryDetails->Id}}">
                        <div class="row">  
                            <div class=" mr-tp-16 mr-bt-10">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="Name">Name*</label>
                                        <input autocomplete="off" type="text" name="Name" placeholder='Ex: Pillar Placement'  id="Name" class="form-control" value="{{$NoteCategoryDetails->Name}}"/>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="no-text-transform">Status</label>
                                        <div class="mr-tp-6">
                                            <input type="radio" name="IsActive" id="UpdateIsActiveYes" <?= $NoteCategoryDetails->IsActive == 1 ? 'checked="checked"' : "" ?> value="Active" class="input-radio"/>
                                            <label for="UpdateIsActiveYes" tabindex="0"></label>
                                            <label for="UpdateIsActiveYes" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                            <input type="radio" name="IsActive" id="UpdateIsActiveNo" <?= $NoteCategoryDetails->IsActive == 0 ? 'checked="checked"' : "" ?> value="Inactive" class="input-radio"/>
                                            <label for="UpdateIsActiveNo" tabindex="0"></label>
                                            <label for="UpdateIsActiveNo" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> 
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="Description">Description</label>
                                    <textarea name="Description" id="Description" class="form-control no-resize-input" rows="4" placeholder='Ex: Pillar placed in between the walls'>{{$NoteCategoryDetails->Description}}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-5" style="text-align:center">
                                <p>
                                    <input type="submit" name="" value="Update" class="btn btn-primary button-custom" id="UpdateNoteCatSubmit">
                                    <input type="reset" name="" value="Undo" class="btn button-custom" id="UpdateNoteCatReset">
                                </p>
                            </div>
                        </div>
                    </form>
                    <div class="mr-tp-5">*:&nbsp;<small>Mandatory fields</small></div>
                </div>
            </div>
            @endif
            <div class="form-loader hidden" id="FormLoader">Saving data...</div>
            <div id="NoteCategoryPageOverLay" v-cloak>
                <div class="notification-overlay" :class="{hidden: FormOverLay}" @click.prevent="clearOverLayMessage()">
                    <div style="text-align: center;" :class="'overlay-'+NotificationIcon">
                        <button type="button" class="close notificationOverlay-close" @click.prevent="clearOverLayMessage()" aria-label="Close" title="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <div :class="'notificationOverlay-icon fa fa-' + NotificationIcon">
                    </div>
                    <div class="nofitication-message">@{{NotificationMessage}}</div>
                    </div>
                </div> 
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicStyles')
<link href="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.css') }}" rel="stylesheet" />
@endsection

@section('dynamicScripts')
<script src="https://unpkg.com/vue/dist/vue.js"></script>
<script src="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.full.min.js') }}"></script>
<script src="{{ URL::assetUrl('/js/common.js') }}"></script>
<script src="{{ URL::assetUrl('/js/sitemeasurement/notecategory.js') }}"></script>
@endsection