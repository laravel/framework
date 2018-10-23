@extends('layouts/master_template')

@section('dynamicStyles')

<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
<link href="{{ asset('/css/materials/surfacematerials/categories.css') }}" rel="stylesheet"/>
@endsection

@section('content')
<div id="SurfaceFinishPage" v-cloak>
    <div class="col-md-12 text-right addNew-block">
        <a class="btn btn-primary fl-rt AddButton" data-toggle="tooltip" title="Click here to Add new Finish" @click.prevent="addSurfaceFinish"> <i class="fa fa-fw fa-plus-square"></i> New Surface Finish</a>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                    <div class="box-body">
                        <div class="table-responsive" v-if="filteredSurfaceFinish.length > 0">
                            <table class="table table-bordered table-striped" id="SurfaceFinishList">
                                <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                                    <tr>
                                    <th class="text-center text-vertical-align pd-10" width="10%">#</th>
                                    <th class="text-center text-vertical-align" width="30%">Name</th> 
                                     <th class="text-center text-vertical-align" width="20%">ShortCode</th> 
                                    <th class="text-center text-vertical-align" width="20%">IsActive</th>
                                    <th class="text-center text-vertical-align" width="20%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(finish, index) in filteredSurfaceFinish">
                                    <td class="text-center text-vertical-align" width="10%">@{{ index+1 }}</td>
                                    <td class="text-center text-vertical-align" width="30%">@{{ finish.Name}}</td>
                                    <td class="text-center text-vertical-align" width="20%">@{{ finish.ShortCode}}</td>
                                    <td class="text-center text-vertical-align" width="20%">
                                    <div v-if="finish.IsActive">
                                        <span class='label label-success'>Active</span>
                                    </div>
                                    <div v-else>
                                        <span class='label label-danger'>Inactive</span>
                                    </div>
                                    </td>
                                    <td class="text-vertical-align text-center" width="20%">                             
                                        <a class="btn btn-custom btn-edit btn-sm"  data-toggle="tooltip" data-original-title="Edit SurfaceFinish" role="button" @click.prevent="editSurfaceFinish(finish.Id)">
                                            <span class="glyphicon glyphicon-pencil btn-edit"></span>
                                        </a>
                                        <a class="btn btn-custom btn-edit btn-sm"  data-toggle="tooltip" data-original-title="View SurfaceFinish" role="button" @click.prevent="viewSurfaceFinish(finish.Id)">
                                            <span class="glyphicon glyphicon-eye-open btn-edit"></span>
                                        </a>
                                    </td>
                                    </tr>
                                </tbody>
                            </table> 
                        </div>
                        <div v-else class="pd-tp-14"> 
                            <div class="callout callout-info">
                                <p><i class="fa fa-fw fa-info-circle"></i>No search results found.</p>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>
    
    <!-- Create SurfaceFinish Modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="AddSurfaceFinishModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Add Surface Finish</h4>
                </div>
                <form :action="StoreSurfaceFinishRoute" method="POST" accept-charset="utf-8" id="AddSurfaceFinishForm">
                    <div class="modal-body pd-bt-0">
                        <div class="form-group">
                            <label for="Name">Name*</label>
                            <input type="text" name="Name" id="Name" class="form-control" placeholder="Ex: HD Gloss"/>
                        </div>
                        <div class="form-group">
                            <label for="ShortCode">ShortCode*</label>
                            <input type="text" name="ShortCode" id="ShortCode" class="form-control" placeholder="Ex: HDG"/>
                        </div>
                        <div class="form-group">
                            <label>Status*</label>
                            <div class="mr-tp-6">
                                <input type="radio" name="Status" id="Active" value="Active" class="input-radio"/>
                                <label for="Active" tabindex="0"></label>
                                <label for="Active" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                <input type="radio" name="Status" id="Inactive" value="Inactive" class="input-radio"/>
                                <label for="Inactive" tabindex="0"></label>
                                <label for="Inactive" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" id="AddSurfaceFinishFormSubmitBtn">Save</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <div class="form-overlay" id="AddSurfaceFinishFormOverlay" v-if="ShowSaveSurfaceFinishLoader">
                     <div class="large loader"></div>
                    <div class="loader-text">Saving Surface Finish</div>
                </div>
            </div>
            <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>

        </div>
    </div>
    
    <!-- Edit Surface Finish Modal -->
     <div class="modal fade" tabindex="-1" role="dialog" id="EditSurfaceFinishModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Edit Surface Finish</h4>
                </div>
                <form action="{{route('materials.surfacefinish.update', ["id" => ""])}}" method="POST" accept-charset="utf-8" id="EditSurfaceFinishForm">
                    <div class="modal-body pd-bt-0">
                        <input type="hidden" name="EditSurfaceFinishId" id="EditSurfaceFinishId" :value="selectedSurfaceFinishData.Id"/>
                        <div class="form-group">
                            <label for="EditSurfaceFinishName">Name*</label>
                            <input type="text" name="EditSurfaceFinishName" id="EditSurfaceFinishName" :value="selectedSurfaceFinishData.Name" class="form-control" placeholder="Ex: HD Gloss"/>
                        </div>
                         <div class="form-group">
                            <label for="EditSurfaceFinishShortCode">ShortCode*</label>
                            <input type="text" name="EditSurfaceFinishShortCode" id="EditSurfaceFinishShortCode" :value="selectedSurfaceFinishData.ShortCode"  class="form-control" placeholder="Ex: HDG"/>
                        </div>
                        <div class="form-group">
                            <label>Status*</label>
                            <div class="mr-tp-6">
                                <input type="radio" name="EditSurfaceFinishStatus" id="EditSurfaceFinishActive" value="Active" class="input-radio" :checked="selectedSurfaceFinishData.IsActive"/>
                                <label for="EditSurfaceFinishActive" tabindex="0"></label>
                                <label for="EditSurfaceFinishActive" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                <input type="radio" name="EditSurfaceFinishStatus" id="EditSurfaceFinishInActive" value="Inactive" class="input-radio" :checked="!selectedSurfaceFinishData.IsActive"/>
                                <label for="EditSurfaceFinishInActive" tabindex="0"></label>
                                <label for="EditSurfaceFinishInActive" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" id="EditSurfaceFinishFormSubmitBtn">Update</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <div class="form-overlay" id="EditSurfaceFinishFormOverlay" v-if="ShowUpdateSurfaceFinishLoader">
                     <div class="large loader"></div>
                    <div class="loader-text">Updating Surface Finish</div>
                </div>
            </div>
            <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>

        </div>
    </div>  
    
    <!-- View SurfaceFinish Modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="ViewSurfaceFinishModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title no-text-transform">View Surface Finish</h4>
            </div>
            <div class="modal-body">
                <table class="table table-striped table-bordered">
                    <tbody>
                        <tr></tr>
                        <tr>
                            <td width="40%">Id</td>
                            <td width="60%">@{{ selectedSurfaceFinishData.Id }}</td>
                        </tr>
                        <tr>
                            <td>Name</td>
                            <td>@{{ selectedSurfaceFinishData.Name }}</td>
                        </tr>
                        <tr>
                            <td>ShortCode</td>
                            <td>@{{ selectedSurfaceFinishData.ShortCode }}</td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                <span class="label label-success" v-if="selectedSurfaceFinishData.IsActive">Active</span>
                                <span class="label label-danger" v-else>InActive</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@section('dynamicScripts')

<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ asset('/js/common.js') }}"></script>
<script src="{{ asset('/js/materials/surfacematerials/finish.js') }}"></script>
@endsection
