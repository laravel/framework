@extends('layouts/master_template')

@section('dynamicStyles')
<link href="{{ URL::assetUrl('/AdminLTE/plugins/jQueryUI/jquery-ui.min.css') }}" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('/plugins/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{ URL::assetUrl("/css/magnific-popup.css")}}">
<link rel="stylesheet" href="{{ asset('css/catalogue/designer/edit.css') }}">
@endsection

@section('content')
<div id="EditSMCatalogPage" v-cloak>
    <div class="row">
        <user-information :user-info="CustomerDetails"></user-information>
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <div class="row">
                        <div class="col-md-8"></div>
                        <div class="col-md-4 text-right">
                            <div class="hidden form-group" id="siteInfo">
                                <label></label>
                                <div id="SiteDetails" class="pd-rt-5 pd-lt-5"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="callout callout-info mr-tp-14" v-if="(SelectedCombLaminates.length < 1)"> 
                                <p><i class="fa fa-fw fa-info-circle"></i>No laminates found.</p>
                            </div>
                            <div class="table-responsive" v-if="SelectedCombLaminates.length > 0">
                                <selected-combination-laminates :selected-combination="SelectedCombLaminates" @deletelaminate="deleteLamFromCombination"></selected-combination-laminates>
                            </div>
                        </div>
                    </div>
                </div>
                <form id="EditCombination" method="POST" action="{{route('catalogue.combination.update', ["id" => ""])}}" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="box-header with-border">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="Project">Projects*</label>
                                    <select name="Project" id="Project" v-model="ProjectId" @change.prevent="fetchRooms(true);fetchShortlistCombination(true)" class="form-control"> 
                                        <option v-for="project in projects" :value="project.Id" :selected="project.Id==ProjectId">
                                            @{{ project.Name }}
                                        </option>   
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="Rooms">Rooms</label>
                                    <select name="Rooms" id="Rooms" v-model="RoomId" class="form-control">
                                        <option value="">Select Room</option>
                                        <option v-for="room in rooms" :value="room.Id" :selected="room.Id==RoomId">
                                            @{{ room.Name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6"></div>
                        </div>
                    </div>
                    <div class="box-header with-border pd-bt-0">
                        <combination-options></combination-options>
                    </div>
                    <div class="box-header with-border" v-if="PickedOption === 'HechpeSuggs'">
                        <div class="table-responsive pd-tp-9" v-if="fileteredHechpeLaminates.length > 0">
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
                                    <tr v-for="(laminate, index) in fileteredHechpeLaminates">
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
                                    <span title="Add" class="cursor-pointer" @click.prevent="addToCombination(laminate.LaminateId, laminate, HechpeSuggestions)" :id="laminate.LaminateId" v-if="!laminate.Active">
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
                        <div v-if="(fileteredHechpeLaminates.length < 1)"> 
                            <div class="callout callout-info mr-tp-14">
                                <p><i class="fa fa-fw fa-info-circle" aria-hidden="true"></i> No laminates found.</p>
                            </div>
                        </div>
                    </div>
                    <div class="box-header with-border" v-if="PickedOption === 'PickFromShortlist'">
                        <div class="table-responsive pd-tp-9" v-if="fileteredShortlistedLaminates.length > 0">
                           <!--<shortlisted-suggestions-list :suggestions-list="fileteredShortlistedLaminates" @selectlaminate="addLamToCombination"></shortlisted-suggestions-list>-->
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
                                    <tr v-for="(laminate, index) in fileteredShortlistedLaminates">
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
                                    <td class="text-center text-vertical-align" width="8%">@{{getBrand(laminate.Brand)}}</td>
                                    <td class="text-center text-vertical-align" width="10%">@{{ laminate.DesignName }}</td>
                                    <td class="text-center text-vertical-align" width="12%">@{{ laminate.DesignNo }}</td>
                                    <td class="text-center text-vertical-align" width="13%" v-html="getCategory(laminate.SurfaceCategory)"></td>
                                    <td class="text-center text-vertical-align" width="11%" v-html="getFinish(laminate.SurfaceFinish)"></td>
                                    <td class="text-center text-vertical-align" width="6%">@{{laminate.Glossy === "1" ? "Yes" : "No" }}</td>
                                    <td class="text-center text-vertical-align" width="14%">@{{laminate.Edgeband === "1" ? "Yes" : "No"}}</td>
                                    <td class="text-vertical-align text-center pd-0" width="16%">
                                    <span title="Add" class="cursor-pointer" @click.prevent="addToCombination(laminate.LaminateId, laminate, ShortlistedCombinations)" :id="laminate.LaminateId" v-if="!laminate.Active">
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
                        <div v-if="(fileteredShortlistedLaminates.length < 1)"> 
                            <div class="callout callout-info mr-tp-14">
                                <p><i class="fa fa-fw fa-info-circle"></i>No laminates found.</p>
                            </div>
                        </div>
                    </div>
                    <div class="box-header with-border"  :class="{'hidden': !showSearchFilter}"> 
                        <h4>Select Laminate</h4>
                        <div class="row mr-bt-15">
                            <div class="col-md-5 col-sm-6 col-xs-12" id="SearchLaminatesBox">
                                <input 
                                    type="text" 
                                    class="form-control search" 
                                    placeholder="Search..." 
                                    onblur="this.placeholder = 'Search...'" 
                                    name="SearchLaminates" 
                                    v-model="SearchString" 
                                    id="SearchLaminates" 
                                    ref="SearchLaminates" 
                                    @keyup.enter="searchLaminates"
                                    data-api-end-point="{{ route('catalogues.laminates.search') }}"
                                    >
                            </div>
                            <div class="col-md-3 col-sm-2 col-xs-12 search-btn">
                                <button 
                                    class="btn btn-primary button-search pd-rt-20 pd-lt-20" 
                                    id="SearchLamsBtn"
                                    @click.prevent="searchLaminates"
                                    data-api-end-point="{{ route('catalogues.laminates.search') }}"
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
                                <i class="fa fa-fw fa-info-circle"></i>No search results found.</p>
                            </div>
                        </div>
                        <div v-if="SearchString.length < 3"> 
                            <div class="callout callout-warning">
                                <p><i class="fa fa-fw fa-warning" aria-hidden="true"></i> Enter at least three letters.</p>
                            </div>
                        </div>
                    </div>
                    <div class="box-body">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="Notes">Add New Notes</label>
                                    <textarea type="text" name="Notes" id="Notes" rows="3" class="form-control no-resize-input" placeholder="Ex: Notes"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="Suggestion">Project Specific Suggestion:</label>
                                    <textarea type="text" name="Suggestion" id="Suggestion" rows="3" class="form-control no-resize-input" placeholder="Ex: Suggestion">@{{Suggestion!= null ? Suggestion[0].Suggestion : null}}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <input type="reset" class="btn button-custom" value="Cancel" data-toggle="tooltip" id="CancelBtn" title="Go back to Selection list"/>
                                <input type="submit" name="EditCombSubmitBtn" value="Update Selection" data-toggle="tooltip" title="Update Shortlisted Selections" class="btn btn-primary button-custom" id="EditCombSubmitBtn"/> 
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="UserId" :value="(!(CustomerDetails == null) ? CustomerDetails.userId : '' )" id="UserId">
                           <input type="hidden" name="ShortCode" :value="(!(CustomerDetails == null) ? CustomerDetails.shortCode : '' )" id="ShortCode">
                </form>  
            </div>
            <div id="NotificationArea" class="hidden">
                <div class="alert alert-dismissible"></div>
            </div> 
            <!--Max combination create limit exceeded warning alert-->
            <max-laminates-selection-alert></max-laminates-selection-alert>
            <div class="overlay project-loader" id="FetchRoomsLoader" v-if="ShowOverlay">
                <div class="large loader"></div>
                <div class="loader-text">@{{OverlayMessage}}</div>
            </div>
            <div class="overlay" id="ShortListLaminateOverlay" v-if="ShowEditCombOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Updating Selection</div>
            </div>
            <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>

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
<script src="{{asset('/plugins/select2/select2.min.js')}}"></script>
<script src="{{ asset('js/common.js') }}"></script>
<script src="{{ asset('/js/catalogue/designer/edit.js') }}"></script>
@endsection