@extends('layouts/master_template')

@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
<link rel="stylesheet" href="{{ URL::assetUrl("/css/sitemeasurement/activeprojects.css")}}">
@endsection

@section('content')
<div id="ActiveProjectsPage" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="callout callout-info" v-if="projects.length === 0">
                    <p>There are no site measurements to {{$Action}}.</p>
                </div>
                <div class="box-body no-padding" v-else>
                    <div class="table-responsive pd-10" id="TableResponsive">
                        <table class="table table-bordered table-striped" id="ActiveProjectTable">
                            <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                                <tr>
                                    <th class="text-center text-vertical-align pd-10" width="8%">S.No</th>
                                    <th width="25%" class="text-center text-vertical-align">Name</th>
                                    <th width="27%" class="text-center text-vertical-align">QuickEstimation</th>
                                    <th width="32%" class="text-center text-vertical-align">Status</th>
                                    <th width="8%" class="text-center text-vertical-align pd-10">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(project, key) in projects">
                                    <td class="text-center text-vertical-align" style="text-align:center;">@{{ project.sNo }}</td>
                                    <td class="text-vertical-align">@{{ project.project }}</td>
                                    <td class="text-vertical-align">@{{ project.quickEstimation }}</td>
                                    <td class="text-vertical-align">@{{ project.status }}</td>
                                    <td class="text-vertical-align text-center" v-html="project.ActionDropdown">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ URL::assetUrl('/js/sitemeasurement/activeprojects.js') }}"></script>
@endsection