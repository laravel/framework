@extends('layouts/master_template')

@section('dynamicStyles')

<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
<link href="{{ asset('/css/materials/surfacematerials/categories.css') }}" rel="stylesheet"/>
@endsection

@section('content')
<div id="SurfaceRangePage" v-cloak>
    <div class="col-md-12 text-right addNew-block">
        <a class="btn btn-primary fl-rt AddButton" data-toggle="tooltip" title="Click here to Add new Surface Range" @click.prevent="addSurfaceRange"> <i class="fa fa-fw fa-plus-square"></i> New Surface Range</a>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="table-responsive" v-if="filteredSurfaceRange.length > 0">
                        <table class="table table-bordered table-striped" id="SurfaceRangeList">
                            <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                                <tr>
                                    <th class="text-center text-vertical-align pd-10" width="8%">#</th>
                                    <th class="text-center text-vertical-align" width="62%">Name</th> 
                                    <th class="text-center text-vertical-align" width="15%">IsActive</th>
                                    <th class="text-center text-vertical-align" width="15%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(range, index) in filteredSurfaceRange">
                                    <td class="text-center text-vertical-align" width="8%">@{{ index+1 }}</td>
                                    <td class="text-center text-vertical-align" width="62%">@{{ range.Name}}</td>
                                    <td class="text-center text-vertical-align" width="15%">
                                        <div v-if="range.IsActive">
                                            <span class='label label-success'>Active</span>
                                        </div>
                                        <div v-else>
                                            <span class='label label-danger'>Inactive</span>
                                        </div>
                                    </td>
                                    <td class="text-vertical-align text-center" width="15%">                             
                                        <a class="btn btn-custom btn-edit btn-sm"  data-toggle="tooltip" data-original-title="Edit Surface Range" role="button" @click.prevent="editSurfaceRange(range.Id)">
                                            <span class="glyphicon glyphicon-pencil btn-edit"></span>
                                        </a>
                                        <a class="btn btn-custom btn-edit btn-sm"  data-toggle="tooltip" data-original-title="View Surface Range" role="button" @click.prevent="viewSurfaceRange(range.Id)">
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

    <!-- Create SurfaceRange Modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="AddSurfaceRangeModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Add Surface Range</h4>
                </div>
                <form :action="StoreSurfaceRangeRoute" method="POST" accept-charset="utf-8" id="AddSurfaceRangeForm">
                    <div class="modal-body pd-bt-0">
                        <div class="form-group">
                            <label for="Name">Name*</label>
                            <input type="text" name="Name" id="Name" class="form-control" placeholder="Ex: Woodgrain"/>
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
                        <button type="submit" class="btn btn-primary pull-left button-custom" id="AddSurfaceRangeFormSubmitBtn">Save</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <div class="form-overlay" id="AddSurfaceRangeFormOverlay" v-if="ShowSaveSurfaceRangeLoader">
                     <div class="large loader"></div>
                    <div class="loader-text">Saving Surface Range</div>
                </div>
            </div>
            <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
        </div>
    </div>

    <!-- Edit Surface Range Modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="EditSurfaceRangeModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Edit Surface Range</h4>
                </div>
                <form action="{{route('materials.surfacerange.update', ["id" => ""])}}" method="POST" accept-charset="utf-8" id="EditSurfaceRangeForm">
                    <div class="modal-body pd-bt-0">
                        <div class="form-group">
                            <label for="EditSurfaceRangeName">Name*</label>
                            <input type="text" name="EditSurfaceRangeName" id="EditSurfaceRangeName" :value = "selectedSurfaceRangeData.Name" class="form-control" placeholder="Ex: Woodgrain"/>
                        </div>
                        <div class="form-group">
                            <label>Status*</label>
                            <div class="mr-tp-6">
                                <input type="radio" name="EditSurfaceRangeStatus" id="EditSurfaceRangeActive" value="Active" class="input-radio" :checked="selectedSurfaceRangeData.IsActive"/>
                                <label for="EditSurfaceRangeActive" tabindex="0"></label>
                                <label for="EditSurfaceRangeActive" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                <input type="radio" name="EditSurfaceRangeStatus" id="EditSurfaceRangeInActive" value="Inactive" class="input-radio" :checked="!selectedSurfaceRangeData.IsActive"/>
                                <label for="EditSurfaceRangeInActive" tabindex="0"></label>
                                <label for="EditSurfaceRangeInActive" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" id="EditSurfaceRangeFormSubmitBtn">Update</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <div class="form-overlay" id="EditSurfaceRangeFormOverlay" v-if="ShowUpdateSurfaceRangeLoader">
                     <div class="large loader"></div>
                    <div class="loader-text">Updating Surface Range</div>
                </div>
            </div>
            <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
        </div>
    </div>  

    <!-- View SurfaceRange Modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="ViewSurfaceRangeModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">View Surface Range</h4>
                </div>
                <div class="modal-body">
                    <table class="table table-striped table-bordered">
                        <tbody>
                            <tr></tr>
                            <tr>
                                <td width="40%">Id</td>
                                <td width="60%">@{{ selectedSurfaceRangeData.Id }}</td>
                            </tr>
                            <tr>
                                <td>Name</td>
                                <td>@{{ selectedSurfaceRangeData.Name }}</td>
                            </tr>
                            <tr>
                                <td>Status</td>
                                <td>
                                    <span class="label label-success" v-if="selectedSurfaceRangeData.IsActive">Active</span>
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
<script src="{{ asset('/js/materials/surfacematerials/range.js') }}"></script>
@endsection
