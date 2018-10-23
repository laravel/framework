@extends('layouts/master_template')

@section('dynamicStyles')
<link rel="stylesheet" href="{{ asset('/plugins/select2/select2.min.css') }}">
<link href="{{ asset('/AdminLTE/plugins/datatables/dataTables.bootstrap.css') }}" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('/css/recommendations.css') }}" />
@endsection

@section('content')
<div class="row" id="RecommendationsPage" v-cloak>
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <form method="POST" action="" accept-charset="utf-8" id="RecommendationForm">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="Project">Project*</label>
                                <select 
                                    name="Project" 
                                    id="Project" 
                                    class="form-control" 
                                    data-api-end-point="{{ route("category.recommendations.qerooms", ["id" => '']) }}" 
                                    >
                                    <option value="">Select</option>
                                    @foreach($projects as $project)
                                    <option value="{{ $project["id"] }}" data-quick-estimation-id="{{ $project["quickestimateid"] }}">{{ $project["name"] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group" :class="{hidden: _.isNull(rooms)}">
                                 <label for="Room">Room*</label>
                                <select 
                                    name="Room" 
                                    id="Room" 
                                    class="form-control" 
                                    data-api-end-point="{{ route('category.recommendations.category') }}"  
                                    > 
                                    <option value="">Select</option> 
                                    <option v-for="room in rooms" :value="room.Id">
                                        @{{ room.Name }}
                                    </option>   
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group" :class="{ hidden: _.isNull(rooms && categories) }">
                                 <label for="Category">Category*</label>
                                <select 
                                    name="Category" 
                                    id="Category" 
                                    class="form-control"
                                    data-api-end-point="{{ route('category.recommendations.selected.items') }}"
                                    > 
                                    <option value="">Select</option> 
                                    <option v-for="category in categories" 
                                            :value="category.id"
                                            :data-category-slug="category.slug"
                                            :data-formtemplate-id="category.formtemplateid"
                                            >
                                        @{{ category.name }}
                                    </option>   
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group" :class="{ hidden: !isCategorySelected }">
                                 <label for=""></label>
                                <span 
                                    class="ion ion-clipboard cursor-pointer pd-tp-33 open-note-modal" 
                                    title="Click here to add / update Note"
                                    style="font-size: 18px;"
                                    >
                                </span>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="row">
                    <div class="col-md-12" :class="{ hidden: !isCategorySelected }">
                        <h4 class="pull-left">@{{ isCustomer ? 'Shortlist / Finalize'+selectedCategory : 'Recommend / Finalize'+selectedCategory }}</h4> 
                        <button 
                            type="button" 
                            class="btn btn-default pull-right" 
                            @click.prevent="fetchSelectedItemsCall('Category')">
                            <i class="fa fa-repeat pd-rt-7" aria-hidden="true"></i>Refresh
                        </button>
                        <input 
                            class="form-control input-lg search-input" 
                            placeholder="Type Design Name, Number, Brand, Sub Brand..." 
                            onfocus="this.placeholder = ''" 
                            onblur="this.placeholder = 'Type Design Name, Number, Brand, Sub Brand...'" 
                            name="SearchItems" 
                            v-model="searchString"
                            style="height:42px;">
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12 category-items" :class="{ hidden: !isCategorySelected }">
                         <category-items 
                            :headers="dataTableHeaders" 
                            :items="items" 
                            v-if="items.length > 0"
                            >
                        </category-items>
                        <div class="callout callout-info mr-tp-15 mr-bt-15" v-else>
                            No materials found for provided Category.
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12 selected-items" :class="{ hidden: !isCategorySelected }">
                         <h4>@{{ isCustomer ? 'Shortlisted / Finalized' + selectedCategory : 'Recommended / Finalized'+selectedCategory }}</h4> 
                        <selected-items 
                            :headers="dataTableHeaders" 
                            :selected-items="selectedItems" 
                            v-if="selectedItems.length > 0"
                            >   
                        </selected-items>
                        <div class="callout callout-info mr-tp-15 mr-bt-15" v-else>
                            No @{{ isCustomer ? 'Shortlisted or Finalized' : 'Recommended or Finalized' }} materials found.
                        </div>
                    </div>
                </div>
                <div 
                    class="overlay" style="height: 123%;" 
                    :class="{hidden: projectFormOverlay}"
                    >
                    <div class="large loader"></div>
                    <div class="loader-text">@{{overLayMessage}}</div>
                </div>
                <note-modal
                    :note="updatedNote" 
                    :note-loader="noteLoader" 
                    :note-over-lay="noteOverLay"
                    :note-notification-Icon="noteNotificationIcon"
                    :note-notification-message="noteNotificationMessage"
                    @closeNoteOverlay="clearAddNoteOverLayMessage()"
                    >                        
                </note-modal>
                <overlay-notification 
                    :form-over-lay="formOverLay" 
                    :notification-icon="notificationIcon" 
                    :notification-message="notificationMessage" 
                    @clearmessage="clearOverLayMessage()" 
                    >   
                </overlay-notification>
            </div>
            <!-- Modal -->
            <div class="modal fade" id="QuickViewModal" role="dialog">
                <div class="modal-dialog">
                    <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-header pd-8">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title text-primary">@{{ selectedCategory }}</h4>
                        </div>
                        <div class="modal-body">

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js") }}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js") }}"></script>
<script src="{{ asset("js/common.js") }}"></script>
<script src="{{ asset('/js/categoryrecommendations/recommendations.js') }}"></script>
@endsection
