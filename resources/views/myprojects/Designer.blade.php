@extends('layouts/master_template')
@section('content')
<div id="DesignerProjectReport" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="callout callout-info mr-tp-5" v-if="Projects.length==0">
                    <h4>Information!</h4>
                    <p>No projects found.</p>
                </div>
                <div class="box-body no-padding pd-bt-10" id="ProjectsList" v-else>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="ProjectReportTable">
                            <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                                <tr>
                                    <th class="text-center text-vertical-align pd-10" width="8%">S.No</th>
                                    <th width="25%" class="text-center text-vertical-align">Name</th>
                                    <th width="27%" class="text-center text-vertical-align">Quick Estimation</th>
                                    <th width="32%" class="text-center text-vertical-align">Status</th>
                                    <th width="8%" class="text-center text-vertical-align"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(Project, key) in Projects">
                                    <td class="text-center text-vertical-align">@{{ Project.SNo }}</td>
                                    <td class="text-vertical-align">@{{ Project.Project }}</td>
                                    <td class="text-vertical-align">@{{ Project.QuickEstimation }}</td>
                                    <td class="text-vertical-align">@{{ Project.Status }}</td>
                                    <td class="text-center text-vertical-align">
                                        <span class="dropdown" v-if="Project.Action.EditName == 'Close' || Project.Action.CreateName == 'Create' || Project.Action.CreateName == 'Add'">
                                            <a class="dropdown-toggle" data-toggle="dropdown" href="" role="button" aria-haspopup="true" aria-expanded="false">
                                                <i class="fa fa-caret-square-o-down" aria-hidden="true"></i>
                                            </a>
                                            <ul class="dropdown-menu" aria-labelledby="SearchResultsDropdownMenu">
                                                <li v-if="(Project.Action.CreateName == 'Create' || Project.Action.CreateName == 'Add') && Project.Action.CreateUrl.length>0">
                                                    <a target="_self" :href="Project.Action.CreateUrl">
                                                        <span><i class="fa fa-plus" aria-hidden="true"></i>&nbsp;&nbsp;@{{Project.Action.CreateName}} Design</span>
                                                    </a>
                                                </li>
                                                <li v-if="Project.Action.EditName == 'Close' && Project.Action.EditUrl.length>0">
                                                    <a target="_self" v-on:click="confirmDesignClose(Project.Action.EditUrl, key)">
                                                        <span><i class="fa fa-close" aria-hidden="true"></i>&nbsp;&nbsp;@{{Project.Action.EditName}} Design</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="form-overlay" v-if="loader" id="Loader">
                <div class="large loader"></div>
                <div class="loader-text"></div>
            </div>
            <div id="NotificationArea"></div> 
        </div>
    </div>
    <div class="modal fade" id="ConfirmationModal" tabindex="-1" role="dialog" >
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title no-text-transform">Confirm</h4>
            </div>
            <div class="modal-body">
                Are you sure you want to close the design? You will not be able to update the design anymore.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary pull-left" @click="submitDesign">Yes</button>
                <button type="button" class="btn pull-left mr-lt-10" data-dismiss="modal">No</button>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
<link href="{{ URL::assetUrl('/css/myprojects/loader.css') }}" rel="stylesheet" />
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl("/js/Vue/vue.js")}}"></script>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ URL::assetUrl("/js/myprojects/notification.js")}}"></script>
<script src="{{ URL::assetUrl("/js/myprojects/designer.js")}}"></script>
<script src="{{ URL::assetUrl("/js/myprojects/tableInitialise.js")}}"></script>
@endsection