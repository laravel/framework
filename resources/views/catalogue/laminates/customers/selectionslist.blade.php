@extends('layouts/master_template')

@section('dynamicStyles')
<link href="{{ URL::assetUrl('/AdminLTE/plugins/jQueryUI/jquery-ui.min.css') }}" rel="stylesheet"/>
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
<link rel="stylesheet" href="{{ URL::assetUrl("/css/magnific-popup.css")}}">
<link rel="stylesheet" href="{{ asset('/plugins/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/catalogue/customer/laminate.css') }}">
@endsection

@section('content')
<div id="CreateLaminatesCataloguePage" v-cloak>
    <div class="row">
        <div class="col-md-12 text-right addNew-block">
            @if ($ViewType == 'List')
            <button class="btn btn-primary button-custom AddButton" :class="{hidden:!ShowSearchLamBody}" id="AddLaminateButton" @click.prevent="showSelectLamSearch" data-toggle="tooltip" title="Click here to search laminate & create new Combination">Shortlist New Laminate</button>
            @endif        
        </div>
        <div class="col-md-12">
            <div class="box box-primary">
                @if ($ViewType == 'Select')
                <div class="box-body">
                    <div class="callout callout-info" v-if="projects.length === 0">
                        <p>
                            <i class="fa fa-fw fa-info-circle" aria-hidden="true"></i> 
                            There are no site projects to be taken.
                        </p>
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
                <div class="box-header with-border">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="Project">Project*</label>
                                <select name="Project" id="Project" v-model="ProjectId" class="form-control"> 
                                    <option value="">Select a Project</option>
                                    <option v-for="project in projects" :value="project.Id" :selected="project.Id==ProjectId">
                                        @{{ project.Name }}
                                    </option>   
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 caution">
                            @include('catalogue.laminates.customers.partials.actionlegends')
                        </div>
                    </div>
                </div>
                <!--Search laminates-->
                @include('catalogue.laminates.customers.partials.searchlaminates')
                <!--Shortlisted laminates-->
                @include('catalogue.laminates.customers.partials.selections')
                <!--Combination Notes pop up-->
                <combinations-notes-popup :combination-notes="CombinationsNotes" v-if="ShowNotesModal == true"></combinations-notes-popup>
                <!--Delete Combination pop up-->
                @include('catalogue.laminates.customers.partials.deleteselection')
                <div class="overlay hidden" id="LaminateFormOverlay">
                    <div class="large loader"></div>
                    <div class="loader-text">Fetching Selections</div>
                </div>
                <div class="overlay" id="ShortListLaminateOverlay" v-if="ShowShortListLaminateLoader">
                    <div class="large loader"></div>
                    <div class="loader-text">@{{ OverlayMessage }}</div>
                </div>
                <div id="SelectionSaveOverLay" class="overlay" :class="{hidden: FormOverLay}">
                     <div class="ion ion-checkmark-circled check-mark"></div>
                    <div>Selection @{{SuccessMessage}} Successfully!</div>
                </div>
                @endif
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
<script src="https://cdn.datatables.net/plug-ins/1.10.16/pagination/simple_incremental_bootstrap.js"></script>
<script src="{{ asset('/js/catalogue/customer/laminate.js') }}"></script>
@endsection
