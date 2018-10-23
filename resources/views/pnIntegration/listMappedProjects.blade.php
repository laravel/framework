@extends('layouts/master_template')
@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
@endsection
@section('content')
<div id="MappedProjectsList">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                    <div class="callout callout-info mr-tp-5" v-if="Projects.length == 0">
                        <h4>Information!</h4>
                        <p>No projects found.</p>
                    </div>
                    
                        <div class="box-body table-responsive no-padding" v-else>
                            <table class="table table-bordered table-hover" id="MappedProjectsTable">
                                <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                                    <tr>
                                        <th class="text-center pd-10" width="5%">S.No</th>
                                        <th class="text-center text-vertical-align" width="20%">QucikEst</th>
                                        <th class="text-center text-vertical-align" width="20%">PN Project Name</th>
                                        <th class="text-center text-vertical-align" width="35%">Assigned Users</th>
                                        <th class="text-center text-vertical-align" width="12%">Mapped on</th>
                                        <th class="text-center text-vertical-align" width="8%">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(Project, key) in Projects">
                                        <td class="text-center" style="text-align:center;">@{{ key+1 }}</td>
                                        <td class="text-center">@{{ Project.quick_estimate.Name }}</td>
                                        <td class="text-center">@{{ Project.PNProjectName }}</td>
                                        <td class="text-center">@{{ Project.GroupUser }}</td>
                                        <td class="text-center">@{{ Project.CreatedAt | giveDate }}</td>
                                        <td class="text-center">@{{ Status[Project.MappingStatus] }}</td>
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
<script src="{{ asset('/js/pnIntegration/mappedProjectsList.js') }}"></script>
<script src="{{ asset('/js/pnIntegration/mappedProjectsReady.js') }}"></script>
@endsection