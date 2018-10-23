@extends('layouts/master_template')

@section('dynamicStyles')
<link href="{{ URL::assetUrl('/AdminLTE/plugins/jQueryUI/jquery-ui.min.css') }}" rel="stylesheet"/>
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
<link rel="stylesheet" href="{{ URL::assetUrl("/css/magnific-popup.css")}}">
<link rel="stylesheet" href="{{ asset('/plugins/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('/css/catalogue/designer/laminate.css') }}">
@endsection

@section('content')
<div id="DesignerLaminatesCataloguePage" v-cloak>
    <div class="row">
        <!--Customer primary information-->
        @include("catalogue.laminates.designer.partials.customerinfo")
        <div class="col-md-12">
            <div class="box box-primary">
                @if ($ViewType == 'search')
                <div class="box-body">
                    <div class="callout callout-info" v-if="projects.length === 0">
                        <p><i class="fa fa-fw fa-info-circle"></i> There are no site projects to be taken.</p>
                    </div>
                    <div class="box-body" v-else>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="ProjectSearch">Project*</label>
                                    <select name="ProjectSearch" id="ProjectSearch" class="form-control"> 
                                        <option value="">Select a Project</option>
                                        <option v-for="project in projects" :value="project.Id">
                                            @{{ project.Name }}
                                        </option>   
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif 
                @if ($ViewType == 'List')
                @if(!auth()->user()->isSales())
                <div class="box-header with-border" :class="{hidden:!ShowSearchLamBody}">
                    <div>
                        <button class="btn btn-primary button-custom fl-rt mr-rt-1" id="AddLaminateButton" data-toggle="tooltip" title="Click here to Add new Selection" @click.prevent="showSelectLamSearch">Add New Suggestion</button>
                    </div>
                </div>
                @endif
                <div class="box-header with-border">
                    <div class="row">   
                        <div class="col-md-3">
                            <div class="form-group mr-tp-6 mr-bt-6">
                                <label for="ProjectSearch">Project*</label>
                                <select name="Project" id="Project" class="form-control">
                                    <option value="">Select Project</option>   
                                    <option v-for="project in projects" :value="project.Id" :selected="project.Id==ProjectId">
                                        @{{ project.Name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <!--Legends-->
                    @include("catalogue.laminates.designer.partials.legends")
                </div>
                <!--Search  Laminates Form-->
                @include("catalogue.laminates.designer.partials.searchlaminates")
                <!--Shortlisted laminates-->
                @include("catalogue.laminates.designer.partials.selections")
               <!--Combination Notes pop up-->
                <combinations-notes-popup :combination-notes="CombinationsNotes" v-if="ShowNotesModal == true"></combinations-notes-popup>
                <!--Delete Combination pop up-->
                @include("catalogue.laminates.designer.partials.deleteselection")
                @endif
                <div class="overlay" id="LaminateFormOverlay" v-if="LaminateFormOverlay">
                    <div class="large loader"></div>
                    <div class="loader-text">Fetching Laminates...</div>
                </div>
                <div class="overlay" id="ShortListLaminateOverlay" v-if="ShowShortListLaminateLoader">
                    <div class="large loader"></div>
                    <div class="loader-text">Saving Selection</div>
                </div>
                <!--Notification popup-->
                <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
            </div>
        </div>
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
@endsection

@section('dynamicScripts')
<script>
    var bootstrapTooltip = $.fn.tooltip.noConflict();
    $.fn.bstooltip = bootstrapTooltip;
</script>
<script src="{{ URL::assetUrl('/AdminLTE/plugins/jQueryUI/jquery-ui.min.js') }}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="//cdn.rawgit.com/ashl1/datatables-rowsgroup/v1.0.0/dataTables.rowsGroup.js"></script>
<script src="{{asset('/plugins/select2/select2.min.js')}}"></script>
<script src="{{ asset('/js/catalogue/designer/laminate.js') }}"></script>
<script src="https://cdn.datatables.net/plug-ins/1.10.16/pagination/simple_incremental_bootstrap.js"></script>
@endsection
