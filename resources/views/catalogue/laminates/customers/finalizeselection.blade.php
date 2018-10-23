@extends('layouts/master_template')

@section('dynamicStyles')
<link href="{{ URL::assetUrl('/AdminLTE/plugins/jQueryUI/jquery-ui.min.css') }}" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/catalogue/customer/shortlistlaminate.css') }}">
<link rel="stylesheet" href="{{ URL::assetUrl("/css/magnific-popup.css")}}">
<link rel="stylesheet" href="{{ asset('/plugins/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/catalogue/customer/finalizecombination.css') }}">
@endsection

@section('content')
<div id="FinalizeCimbinationPage" v-cloak>
    <div class="row">
        <div class="col-md-12 text-right back-btn">
            <button name="Back" class="btn btn-primary btn-custom" title="Go back to Selections list" onclick="$('#CancelBtn').trigger('click');">
                <i class='fa fa-fw fa-arrow-left'></i>Back
            </button>
        </div>
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title no-text-transform fl-lt">Selected Laminates</h3>
                </div>
                <div class="box-header with-border pd-bt-0">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="callout callout-info mr-tp-14" v-if="(SelectedCombLaminates.length < 1)">
                                <p>
                                    <i class="fa fa-fw fa-info-circle" aria-hidden="true"></i> No laminates found.
                                </p>
                            </div>
                            <!--Selected laminates list-->
                            <selected-combination-laminates :selected-combination="SelectedCombLaminates" @deletelaminate="deleteLamFromCombination" v-else></selected-combination-laminates>
                        </div>
                    </div>
                </div>
                <form id="FinalizeCombination" method="POST" action="{{route('catalogues.laminates.combination.finalize.update', ["id" => ""])}}" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="box-header with-border pd-bt-0">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="Project">Project*</label>
                                    <select name="Project" id="Project" v-model="ProjectId" @change.prevent="fetchRooms(true)" class="form-control"> 
                                        <option v-for="project in projects" :value="project.Id" :selected="project.Id==ProjectId">
                                            @{{ project.Name }}
                                        </option>   
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="Rooms">Room*</label>
                                    <select name="Rooms" id="Rooms" class="form-control" v-model="RoomId">
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
                        <div class="row">
                            <div class="col-md-12 col-xs-12">
                                <div class="form-group">
                                    <label>
                                        Combination
                                        <i class="fa fa-question-circle cursor-pointer help-icon" data-toggle="tooltip" data-original-title="Please select laminates from any option below to create / update combination">
                                        </i>
                                    </label>
                                    <p>
                                        <input type="radio" name="SuggestionType" value="HechpeSuggs" v-model="PickedOption" class="input-radio" id="HechpeSuggs"/>
                                        <label for="HechpeSuggs" tabindex="0"></label>
                                        <label for="HechpeSuggs" class="text-normal cursor-pointer mr-rt-20">HECHPE Suggestions</label>
                                        <input type="radio" name="SuggestionType" value="PickFromShortlist" v-model="PickedOption" class="input-radio" id="PickFromShortlist" />
                                        <label for="PickFromShortlist" tabindex="-1"></label>
                                        <label for="PickFromShortlist" class="text-normal cursor-pointer mr-rt-20">Pick from shortlist</label>
                                        <input type="radio" name="SuggestionType" value="GenSuggs" v-model="PickedOption" class="input-radio" id="GenSuggs"/> 
                                        <label for="GenSuggs" tabindex="-1"></label>
                                        <label for="GenSuggs" class="text-normal cursor-pointer">Search</label> 
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-header with-border pd-bt-0" v-if="PickedOption === 'HechpeSuggs'">
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
                    <div class="box-header with-border pd-bt-0" v-if="PickedOption === 'PickFromShortlist'">
                        <div class="table-responsive pd-tp-9" v-if="fileteredShortlistedLaminates.length > 0">
                            <shortlisted-suggestions-list :suggestions-list="fileteredShortlistedLaminates" @selectlaminate="addLamToCombination"></shortlisted-suggestions-list>
                        </div>
                        <div v-if="(fileteredShortlistedLaminates.length < 1)"> 
                            <div class="callout callout-info mr-tp-14">
                                <p><i class="fa fa-fw fa-info-circle" aria-hidden="true"></i> No laminates found.</p>
                            </div>
                        </div>
                    </div>
                    <div class="box-header with-border pd-bt-0" :class="{'hidden': !showSearchFilter}"> 
                        <h4>Select Laminate</h4>
                        <div class="row mr-bt-15">
                            <div class="col-md-5 col-sm-6 col-xs-12" id="SearchLaminatesBox">
                                <input 
                                    type="text" 
                                    class="form-control search" 
                                    placeholder="Type Design Name, Number, Brand..." 
                                    ref="SearchLaminates" 
                                    v-model="SearchString" 
                                    onblur="this.placeholder = 'Type Design Name, Number, Brand...'" 
                                    name="SearchLaminates" 
                                    id="SearchLaminates"
                                    @keyup.enter="searchLaminates"
                                    data-api-end-point="{{ route('catalogues.laminates.search.compare') }}"
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
                        <div v-if="fileteredLaminates.length < 1"> 
                            <div class="callout callout-info">
                                <p><i class="fa fa-fw fa-info-circle" aria-hidden="true"></i> No search results found.</p>
                            </div>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="Notes">Notes</label>
                                    <textarea type="text" name="Notes" id="Notes" rows="3" class="form-control no-resize-input" placeholder="Ex: Notes"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <input type="reset" class="btn no-border-radius pd-rt-20 pd-lt-20" value="Cancel" id="CancelBtn" title="Go back to Selections list"/>
                                <input type="button" name="FinalizeCombSubmitBtn" value="Finalize Selection" class="btn btn-primary no-border-radius pd-rt-20 pd-lt-20" id="FinalizeCombSubmitBtn"/>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div id="NotificationArea" class="hidden">
                <div class="alert alert-dismissible"></div>
            </div> 
            <!--Max combination create limit exceeded warning alert-->
            <max-laminates-selection-alert></max-laminates-selection-alert>
            <!--Rooms other shortlisted selections detach warning alert before finalize selection-->
            <rooms-detach-alert :combination-id="CombinationId" :room="selectedRoomArea" @deleteselections='deleteRemSelections'></rooms-detach-alert>
            <div class="overlay project-loader" id="FetchRoomsLoader" v-if="ShowOverlay">
                <div class="large loader"></div>
                <div class="loader-text">@{{ OverlayMessage }}</div>
            </div>
            <div class="overlay project-loader" v-if="ShowFinalizeCombOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Finalizing Selection</div>
            </div> 
            <!--Notification popup-->
            <overlay-notification :form-over-lay="MessageFormOverlay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
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
<script src="{{ asset('/js/catalogue/customer/finalizecombination.js') }}"></script>
@endsection