@extends('layouts/master_template')

@section('dynamicStyles')
<link href="{{ URL::assetUrl('/AdminLTE/plugins/jQueryUI/jquery-ui.min.css') }}" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('/plugins/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{ URL::assetUrl("/css/magnific-popup.css")}}">
<link rel="stylesheet" href="{{ asset('css/catalogue/designer/add.css') }}">
@endsection

@section('content')
<div id="AddNewSuggestionPage" v-cloak>
    <div class="row">
        <user-information :user-info="CustomerDetails.user"></user-information>
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border" v-if="projects.length < 1">
                    <div class="callout callout-info mr-8">
                        <p><i class="fa fa-fw fa-info-circle"></i>Site project has not created yet.</p>
                    </div>
                </div>
                <div v-else>
                    <form id="CreateCombination" method="POST" action="{{route('catalogue.laminate.save')}}" enctype="multipart/form-data">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <div class="box-header with-border">
                            <div v-if="!_.isEmpty(CurrentLaminate)">
                                <div class="row">
                                    <div class="col-md-8"><h4 class="no-text-transform">@{{CurrentLaminate.BrandName}} <span class="pipe-color">|</span> @{{CurrentLaminate.DesignName}} <span class="pipe-color">|</span> @{{CurrentLaminate.DesignNo}}</h4>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <div class="form-group" id="siteInfo">
                                            <label></label>
                                            <div id="SiteDetails" class="pd-rt-5 pd-lt-5"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-1">
                                        <div class="image-div image-link">
                                            <a :href="CdnUrl+JSON.parse(CurrentLaminate.FullSheetImage)[0].Path">
                                                <img :src="CdnUrl+JSON.parse(CurrentLaminate.FullSheetImage)[0].Path" alt="Sample Laminate" data-toggle="tooltip" class="note-thumbnail cursor-zoom-in" :title="JSON.parse(CurrentLaminate.FullSheetImage)[0].UserFileName" @click.prevent="initializeFSheetThumbnailsPopup(CurrentLaminate.FullSheetImage, CdnUrl)">
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-sm-11">
                                        <div class="row">
                                            <div class="col-sm-2">
                                                <div class="form-group">
                                                    <label for="">Sub Brand</label>
                                                    <p v-html="(CurrentLaminate.SubBrand) ? CurrentLaminate.SubBrand : '<small>N/A</small>'"></p>
                                                </div>
                                            </div>
                                            <div class="col-sm-2">
                                                <div class="form-group">
                                                    <label>Category</label>

                                                    <p v-html="(CurrentLaminate.CategoryName) ? CurrentLaminate.CategoryName : '<small>N/A</small>'"></p>
                                                </div> 
                                            </div>
                                            <div class="col-sm-2">
                                                <div class="form-group">
                                                    <label>Range</label>
                                                    <p v-html="(CurrentLaminate.SurfaceRange) ? CurrentLaminate.SurfaceRange : '<small>N/A</small>'"></p>
                                                </div> 
                                            </div>
                                            <div class="col-sm-2">
                                                <div class="form-group">
                                                    <label>Surface Finish</label> 
                                                    <p v-html="(CurrentLaminate.SurfaceFinish) ? CurrentLaminate.SurfaceFinish : '<small>N/A</small>'"></p>
                                                </div> 
                                            </div>
                                            <div class="col-sm-2">
                                                <div class="form-group">
                                                    <label>Textured Surface</label> 
                                                    <p>@{{(CurrentLaminate.TexturedSurface === '1') ? "Yes" : "No"}}</p>
                                                </div> 
                                            </div>
                                            <div class="col-sm-2">
                                                <div class="form-group">
                                                    <label></label>
                                                    <p>
                                                        <a 
                                                            href="javascript:void(0);" 
                                                            class="cursor-pointer full-view-popup view-more text-decoration" 
                                                            @click="openLaminateViewPopup()" 
                                                            data-toggle="tooltip" 
                                                            title="View Laminate Details"
                                                            id="ViewLaminate"
                                                            data-api-end-point="{{ route('catalogue.laminate.get', ["id" => '']) }}"
                                                            >View more
                                                        </a>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="box-header with-border">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="Project">Projects*</label>
                                        <select name="Project" id="Project" v-model="ProjectId" @change.prevent="fetchProjectDetails(true)" class="form-control"> 
                                            <option v-for="project in projects" :value="project.Id" :selected="project.Id==ProjectId">
                                                @{{ project.Name }}
                                            </option>   
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="Rooms">Rooms</label>
                                        <select name="Rooms[]" id="Rooms" class="form-control" multiple="multiple"> 
                                            <option v-for="room in RoomArea" :value="room.Id">
                                                @{{ room.Name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6"></div>
                            </div>
                        </div>
                        <div class="box-header with-border">
                            <combination-options></combination-options>
                        </div>
                        <div class="box-header with-border" v-if="PickedOption === 'HechpeSuggs'">
                            <div class="row">
                                <div class="col-md-12">
                                    <selected-laminates :selected-laminates="Combination" @deletelaminate="deleteLamFromCombination"></selected-laminates>  
                                </div>
                            </div>
                            <div class="table-responsive" v-if="filteredSuggestions.length > 0">
                                <table class="table table-bordered table-striped">
                                    <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                                        <tr>
                                        <th class="text-center text-vertical-align pd-10" width="2%">#</th>
                                        <th class="text-center text-vertical-align pd-10" width="8%">Image</th> 
                                        <th class="text-center text-vertical-align" width="8%">Brand</th>
                                        <th class="text-center text-vertical-align" width="10%">Design Name</th>
                                        <th class="text-center text-vertical-align" width="12%">Design Number</th>
                                        <th class="text-center text-vertical-align" width="13%">Type</th>
                                        <th class="text-center text-vertical-align" width="11%">Surface Finish</th>
                                        <th class="text-center text-vertical-align" width="6%">Glossiness</th>
                                        <th class="text-center text-vertical-align" width="14%">Edgeband availibility</th>
                                        <th class="text-center text-vertical-align" width="16%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(laminate, index) in filteredSuggestions">
                                        <td class="text-center text-vertical-align" width="2%">@{{ index+1 }}</td>
                                        <td class="text-center text-vertical-align" width="8%"> 
                                            <div class="image-link">
                                                <a :href="CdnUrl+JSON.parse(laminate.FullSheetImage)[0].Path">
                                                    <img :src="CdnUrl+JSON.parse(laminate.FullSheetImage)[0].Path" 
                                                        alt="Sample Laminate" 
                                                        class="fullimage-thumbnail cursor-zoom-in" 
                                                        :title="JSON.parse(laminate.FullSheetImage)[0].UserFileName" 
                                                        @click.prevent="initializeFSheetThumbnailsPopup(laminate.FullSheetImage, CdnUrl)"
                                                        >
                                                </a>
                                            </div>
                                        </td>
                                        <td class="text-center text-vertical-align" width="8%">@{{ laminate.BrandName }}</td>
                                        <td class="text-center text-vertical-align" width="10%">@{{ laminate.DesignName }}</td>
                                        <td class="text-center text-vertical-align" width="12%">@{{ laminate.DesignNo }}</td>
                                        <td class="text-center text-vertical-align" width="13%" v-html="(laminate.CategoryName) ? laminate.CategoryName : '<small>N/A</small>'"></td>
                                        <td class="text-center text-vertical-align" width="11%" v-html="(laminate.SurfaceFinish) ? laminate.SurfaceFinish : '<small>N/A</small>'"></td>
                                        <td class="text-center text-vertical-align" width="6%">@{{laminate.Glossy === "1" ? "Yes" : "No" }}</td>
                                        <td class="text-center text-vertical-align" width="14%">@{{laminate.Edgeband === "1" ? "Yes" : "No"}}</td>
                                        <td class="text-vertical-align text-center pd-0" width="16%">
                                        <span title="Add" class="cursor-pointer" @click.prevent="addToCombination(laminate.LaminateId, laminate, HechpeSuggs)" :id="laminate.LaminateId" v-if="!laminate.Active">
                                            <i class="fa fa-fw fa-plus-square" aria-hidden="true"></i>&nbsp;Add to Combination
                                        </span>
                                        <span title="Added" class="cursor-pointer" v-else>
                                            <i class="fa fa-check check-icon" aria-hidden="true"></i>&nbsp;Added to Combination
                                        </span>
                                        </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-if="(filteredSuggestions.length < 1)"> 
                                <div class="callout callout-info mr-tp-14">
                                    <p><i class="fa fa-fw fa-info-circle"></i>No laminates found.</p>
                                </div>
                            </div>
                        </div>
                        <div class="box-header with-border" v-if="PickedOption === 'PickFromShortlist'">
                            <div class="row">
                                <div class="col-md-12">
                                    <selected-shortlisted-laminates :selected-laminates="Combination" @deletelaminate="deleteLamFromCombination"></selected-shortlisted-laminates>  
                                </div>
                            </div>
                            <div class="table-responsive" v-if="fileteredShortlistedSuggestions.length > 0">
                                <shortlisted-suggestions-list :suggestions-list="fileteredShortlistedSuggestions" @selectlaminate="addLamToCombination"></shortlisted-suggestions-list>
                            </div>
                            <div v-if="(fileteredShortlistedSuggestions.length < 1)"> 
                                <div class="callout callout-info mr-tp-14">
                                    <p><i class="fa fa-fw fa-info-circle"></i>No laminates found.</p>
                                </div>
                            </div>
                        </div>
                        <div class="box-header with-border" :class="{'hidden': !showSearchFilter}"> 
                            <div class="row">
                                <div class="col-md-12">
                                    <selected-general-laminates :selected-laminates="Combination" @deletelaminate="deleteLamFromCombination"></selected-general-laminates> 
                                </div>
                            </div> 
                            <h4>Select Laminate</h4>
                            <div class="row mr-bt-15">
                                <div class="col-md-5 col-sm-6 col-xs-12" id="SearchLaminatesBox">
                                    <input 
                                        type="text" 
                                        class="form-control search" 
                                        placeholder="Search..." 
                                        ref="SearchLaminates" 
                                        onblur="this.placeholder = 'Search...'" 
                                        name="SearchLaminates" 
                                        v-model="SearchString" 
                                        id="SearchLaminates" 
                                        @keyup.enter="searchLaminates"
                                        data-api-end-point="{{ route('catalogue.laminates.search') }}">
                                </div>
                                <div class="col-md-3 col-sm-2 col-xs-12 search-btn">
                                    <button 
                                        class="btn btn-primary button-search pd-rt-20 pd-lt-20" 
                                        id="SearchLamsBtn"
                                        @click.prevent="searchLaminates"
                                        data-api-end-point="{{ route('catalogue.laminates.search') }}"
                                        >Search
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive" v-if="fileteredLaminates.length > 0">
                                <table class="table table-bordered table-striped" id="GenerelSuggestionsTable">
                                    <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                                        <tr>
                                        <th class="text-center text-vertical-align pd-10" width="2%">#</th>
                                        <th class="text-center text-vertical-align pd-10" width="8%">Image</th> 
                                        <th class="text-center text-vertical-align" width="8%">Brand</th>
                                        <th class="text-center text-vertical-align" width="10%">Design Name</th>
                                        <th class="text-center text-vertical-align" width="12%">Design Number</th>
                                        <th class="text-center text-vertical-align" width="13%">Type</th>
                                        <th class="text-center text-vertical-align" width="11%">Surface Finish</th>
                                        <th class="text-center text-vertical-align" width="6%">Glossiness</th>
                                        <th class="text-center text-vertical-align" width="14%">Edgeband availibility</th>
                                        <th class="text-center text-vertical-align" width="16%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(laminate, index) in fileteredLaminates">
                                        <td class="text-center text-vertical-align" width="2%">@{{ index+1 }}</td>
                                        <td class="text-center text-vertical-align" width="8%"> 
                                            <div class="image-link">
                                                <a :href="CdnUrl+JSON.parse(laminate.FullSheetImage)[0].Path">
                                                    <img 
                                                        :src="CdnUrl+JSON.parse(laminate.FullSheetImage)[0].Path" 
                                                        alt="Full Sheet Image" 
                                                        class="fullimage-thumbnail cursor-zoom-in" 
                                                        :title="JSON.parse(laminate.FullSheetImage)[0].UserFileName" 
                                                        @click.prevent="initializeFSheetThumbnailsPopup(laminate.FullSheetImage, CdnUrl)"
                                                        >
                                                </a>
                                            </div>
                                        </td>
                                        <td class="text-center text-vertical-align" width="8%">@{{ laminate.BrandName }}</td>
                                        <td class="text-center text-vertical-align" width="10%">@{{ laminate.DesignName }}</td>
                                        <td class="text-center text-vertical-align" width="12%">@{{ laminate.DesignNo }}</td>
                                        <td class="text-center text-vertical-align" width="13%" v-html="(laminate.CategoryName) ? laminate.CategoryName : '<small>N/A</small>'"></td>
                                        <td class="text-center text-vertical-align" width="11%" v-html="(laminate.SurfaceFinish) ? laminate.SurfaceFinish : '<small>N/A</small>'"></td>
                                        <td class="text-center text-vertical-align" width="6%">@{{laminate.Glossy === "1" ? "Yes" : "No" }}</td>
                                        <td class="text-center text-vertical-align" width="14%">@{{laminate.Edgeband === "1                            " ? "Yes" : "No"}}</td>
                                        <td class="text-vertical-align text-center pd-0" width="16%">
                                        <span 
                                            title="Add" 
                                            class="cursor-pointer" 
                                            @click.prevent="addToCombination(laminate.LaminateId, laminate, SearchResult)" 
                                            :id="laminate.LaminateId" 
                                            v-if="!laminate.Active"
                                            >
                                            <i class="fa fa-fw fa-plus-square" aria-hidden="true"></i>&nbsp;Add to Combination
                                        </span>
                                        <span title="Added" class="cursor-pointer" v-else>
                                            <i class="fa fa-check check-icon" aria-hidden="true"></i>&nbsp;Added to Combination
                                        </span>
                                        </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-if="(fileteredLaminates.length < 1 && SearchString.length >= 3)"> 
                                <div class="callout callout-info">
                                    <p><i class="fa fa-fw fa-info-circle"></i>No search results found.</p>
                                </div>
                            </div>
                            <div v-if="SearchString.length < 3"> 
                                <div class="callout callout-warning">
                                    <p><i class="fa fa-fw fa-warning" aria-hidden="true"></i> Enter at least three letters.</p>
                                </div>
                            </div>
                        </div>
                        <div class="box-body">
                            <max-laminates-selection-alert></max-laminates-selection-alert>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="Notes">Notes:</label>
                                        <textarea type="text" name="Notes" id="Notes" rows="3" class="form-control no-resize-input" placeholder="Ex: Notes"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="Suggestion">Project Specific Suggestion:</label>
                                        <textarea type="text" name="Suggestion" id="Suggestion" rows="3" class="form-control no-resize-input" placeholder="Ex: Suggestion"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12">
                                    <input type="reset" class="btn button-custom" value="Cancel" id="CancelBtn" data-toggle="tooltip" title="Go back to Selection list"/>
                                    <input type="submit" name="addSuggestionBtn" value="Shortlist Selection" class="btn btn-primary button-custom" data-toggle="tooltip" title="Shortlist Selection" id="addSuggestionBtn"/> 
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="UserId" :value="(!(CustomerDetails == null) ?  CustomerDetails.user.userId : '' )" id="UserId">
                               <input type="hidden" name="ShortCode" :value="(!(CustomerDetails == null) ? CustomerDetails.shortCode : '' )" id="ShortCode">
                    </form>
                </div>
                <div class="overlay" id="ShortListLaminateOverlay" v-if="ShowShortListLaminateLoader">
                    <div class="large loader"></div>
                    <div class="loader-text">Saving Selection</div>
                </div>
                <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
                <!--Laminate full view pop up-->
                <div class="modal fade" id="FullViewModal" role="dialog">
                    <div class="modal-dialog modal-lg">
                        <!-- Modal content-->
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 class="modal-title">Full View</h4>
                            </div>
                            <div class="modal-body"> 
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-custom" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>         
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicScripts')
<script>
    var bootstrapTooltip = $.fn.tooltip.noConflict();
    $.fn.bstooltip = bootstrapTooltip;
</script>
<script src="{{ URL::assetUrl('/AdminLTE/plugins/jQueryUI/jquery-ui.min.js') }}"></script>
<script src="{{ asset('js/common.js') }}"></script>
<script src="{{ asset('/js/catalogue/designer/addcombination.js') }}"></script>
@endsection