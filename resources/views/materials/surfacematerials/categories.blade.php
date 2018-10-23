@extends('layouts/master_template')

@section('dynamicStyles')

<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
<link href="{{ asset('/css/materials/surfacematerials/categories.css') }}" rel="stylesheet"/>
@endsection

@section('content')
<div id="SurfaceMaterialCategoryPage" v-cloak>
    <div class="col-md-12 text-right addNew-block">
        <a class="btn btn-primary fl-rt AddButton" data-toggle="tooltip" title="Click here to Add new Category" @click.prevent="addCategory"> <i class="fa fa-fw fa-plus-square"></i> New Category</a>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="table-responsive" v-if="filteredCategories.length > 0">
                        <table class="table table-bordered table-striped" id="CategoriesList">
                            <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                                <tr>
                                <th class="text-center text-vertical-align pd-10" width="8%">#</th>
                                <th class="text-center text-vertical-align" width="62%">Name</th> 
                                <th class="text-center text-vertical-align" width="15%">IsActive</th>
                                <th class="text-center text-vertical-align" width="15%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(category, index) in filteredCategories">
                                <td class="text-center text-vertical-align" width="8%">@{{ index+1 }}</td>
                                <td class="text-center text-vertical-align" width="62%">@{{ category.Name}}</td>
                                <td class="text-center text-vertical-align" width="15%">
                                    <div v-if="category.IsActive">
                                        <span class='label label-success'>Active</span>
                                    </div>
                                    <div v-else>
                                        <span class='label label-danger'>Inactive</span>
                                    </div>
                                </td>
                                <td class="text-vertical-align text-center" width="15%">                             
                                    <a class="btn btn-custom btn-edit btn-sm"  data-toggle="tooltip" data-original-title="Edit Category" role="button" @click.prevent="editCategory(category.Id)">
                                        <span class="glyphicon glyphicon-pencil btn-edit"></span>
                                    </a>
                                    <a class="btn btn-custom btn-edit btn-sm"  data-toggle="tooltip" data-original-title="View Category" role="button" @click.prevent="viewCategory(category.Id)">
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

    <!-- Create Category Modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="AddCategoryModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Add Surface Category</h4>
                </div>
                <form :action="StoreSurfaceCategoryRoute" method="POST" accept-charset="utf-8" id="AddCategoryForm">
                    <div class="modal-body pd-bt-0">
                        <div class="form-group">
                            <label for="Name">Name*</label>
                            <input type="text" name="Name" id="Name" class="form-control" placeholder="Ex: Laminate"/>
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
                        <button type="submit" class="btn btn-primary pull-left button-custom" id="AddCategoryFormSubmitBtn">Save</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <div class="form-overlay" id="AddCategoryFormOverlay" v-if="ShowSaveSurfaceCategoryLoader">
                    <div class="large loader"></div>
                    <div class="loader-text">Saving Category</div>
                </div>
            </div>
            <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>

        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="EditCategoryModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Edit Surface Category</h4>
                </div>
                <form action="{{route('materials.surfacecategory.update', ["id" => ""])}}" method="POST" accept-charset="utf-8" id="EditCategoryForm">
                    <div class="modal-body pd-bt-0">
                        <div class="form-group">
                            <label for="EditCategoryName">Name*</label>
                            <input type="text" name="EditCategoryName" id="EditCategoryName" :value="selectedCategoryData.Name" class="form-control" placeholder="Ex: Laminate"/>
                        </div>
                        <div class="form-group">
                            <label>Status*</label>
                            <div class="mr-tp-6">
                                <input type="radio" name="EditCategoryStatus" id="EditCategoryActive" value="Active" class="input-radio" :checked="selectedCategoryData.IsActive"/>
                                <label for="EditCategoryActive" tabindex="0"></label>
                                <label for="EditCategoryActive" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                <input type="radio" name="EditCategoryStatus" id="EditCategoryInActive" value="Inactive" class="input-radio" :checked="!selectedCategoryData.IsActive"/>
                                <label for="EditCategoryInActive" tabindex="0"></label>
                                <label for="EditCategoryInActive" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary pull-left button-custom" id="EditCategoryFormSubmitBtn">Update</button>
                        <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
                <div class="form-overlay" id="EditCategoryFormOverlay" v-if="ShowUpdateSurfaceCategoryLoader">
                     <div class="large loader"></div>
                    <div class="loader-text">Updating Category</div>
                </div>
            </div>
            <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>

        </div>
    </div>  

    <!-- View Category Modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="ViewCategoryModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">View Surface Category</h4>
                </div>
                <div class="modal-body">
                    <table class="table table-striped table-bordered">
                        <tbody>
                            <tr></tr>
                            <tr>
                            <td width="40%">Id</td>
                            <td width="60%">@{{ selectedCategoryData.Id }}</td>
                            </tr>
                            <tr>
                            <td>Name</td>
                            <td>@{{ selectedCategoryData.Name }}</td>
                            </tr>
                            <tr>
                            <td>Status</td>
                            <td>
                            <span class="label label-success" v-if="selectedCategoryData.IsActive">Active</span>
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
<script src="{{ asset('/js/materials/surfacematerials/categories.js') }}"></script>
@endsection
