@extends('layouts/master_template')

@section('dynamicStyles')
<link href="{{ asset('/css/materials/vueTable.css') }}" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/faqs/faqs.css') }}">
@endsection

@section('content')
<div id="category" v-cloak>
    <div class="col-md-12 text-right addNew-block">
        <a class="btn btn-primary button-custom fl-rt AddButton" data-toggle="tooltip" title="Click here to Add new Category" @click.prevent="addModal"> 
            <i class="fa fa-fw fa-plus-square"></i> New FAQ Category
        </a>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="callout callout-info mr-tp-6 mr-bt-6" v-if="categories.length === 0">
                        <p>No Categories found.</p>
                    </div>
                    <v-client-table :columns="columns" :data="filteredCategories" :options="options" v-else>
                        <span slot="Id" slot-scope="props">@{{props.index}}</span>
                        <template slot="IsActive" slot-scope="props">
                            <span  class="label label-success" v-if="props.row.IsActive==1">Active</span>
                            <span class="label label-danger" v-else>InActive</span>
                        </template>
                        <template slot="Actions" slot-scope="props">
                            <a class="btn btn-custom btn-edit btn-sm mr-rt-3" data-toggle="tooltip" data-original-title="Edit" role="button" @click.prevent="updateModal(props.row)">
                                <span class="glyphicon glyphicon-pencil btn-edit"></span>
                            </a>
                            <a class="btn btn-custom btn-edit btn-sm" data-toggle="tooltip" data-original-title="View" role="button" @click.prevent="viewModal(props.index, props.row)">
                                <span class="glyphicon glyphicon-eye-open btn-edit"></span>
                            </a>
                        </template>
                    </v-client-table>
                </div>
            </div>
        </div>
    </div>
    <!-- Create Category Modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="AddModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Add FAQ Category</h4>
                </div>
                <div class="modal-body pd-bt-0">
                    <form :action="Url+'faqcategory/add'" method="POST" accept-charset="utf-8" id="addForm">
                        <div class="form-group">
                            <label for="Name">Name*</label>
                            <input type="text" name="Name" id="Name" class="form-control" placeholder="Ex: Category"/>
                        </div>
                        <div class="form-group">
                            <label>Status*</label>
                            <div class="mr-tp-6">
                                <input type="radio" name="Status" id="Active" value="1" class="input-radio"/>
                                <label for="Active" tabindex="0"></label>
                                <label for="Active" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                <input type="radio" name="Status" id="Inactive" value="0" class="input-radio"/>
                                <label for="Inactive" tabindex="0"></label>
                                <label for="Inactive" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary pull-left button-custom" >Save</button>
                            <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
                <div class="form-overlay" :class="{hidden: SaveLoader}">
                     <div class="large loader"></div>
                    <div class="loader-text">Saving Category</div>
                </div>
                <div id="NotificationArea"></div>
            </div>
        </div>
    </div>
    
    <!-- Update Category Modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="UpdateModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">Update FAQ Category</h4>
                </div>
                <div class="modal-body pd-bt-0">
                    <form :action="Url+'faqcategory/update/'+selectedCategoryData.Id" method="POST" accept-charset="utf-8" id="UpdateForm">
                        <div class="form-group">
                            <label for="EditName">Name*</label>
                            <input type="text" name="Name" id="EditName" :value="selectedCategoryData.Name" class="form-control" placeholder="Ex: Category"/>
                        </div>
                        <div class="form-group">
                            <label>Status*</label>
                            <div class="mr-tp-6">
                                <input type="radio" name="Status" id="UpdateActive" value="1" class="input-radio" :checked="selectedCategoryData.IsActive==1"/>
                                <label for="UpdateActive" tabindex="0"></label>
                                <label for="UpdateActive" class="text-normal cursor-pointer mr-rt-8">Active</label>
                                <input type="radio" name="Status" id="UpdateInactive" value="0" class="input-radio" :checked="selectedCategoryData.IsActive==0"/>
                                <label for="UpdateInactive" tabindex="0"></label>
                                <label for="UpdateInactive" class="text-normal cursor-pointer mr-rt-8">Inactive</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary pull-left button-custom" >Update</button>
                            <button type="button" class="btn pull-left button-custom" data-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
                <div class="form-overlay" :class="{hidden: UpdateLoader}">
                     <div class="large loader"></div>
                    <div class="loader-text">Updating Category</div>
                </div>
                <div id="UpdateNotificationArea"></div>
            </div>
        </div>
    </div>
    
     <!-- view Category Modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="ViewModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">View</h4>
                </div>
                <div class="modal-body">
                    <table class="table table-striped table-bordered">
                        <tbody>
                            <tr>
                                <td>#</td>
                                <td>@{{SelectedCategoryIndex}}</td>
                            </tr>
                            <tr>
                                <td>Name</td>
                                <td>@{{selectedCategoryData.Name}}</td>
                            </tr>
                            <tr>
                                <td>Status</td>
                                <td>
                                    <span class="label label-success" v-if="selectedCategoryData.IsActive==1">Active</span>
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
<script src="{{ asset('js/common.js') }}"></script>
<script src="{{ asset('js/faqs/category.js') }}"></script>
@endsection
