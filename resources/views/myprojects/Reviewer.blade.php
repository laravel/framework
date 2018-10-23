@extends('layouts/master_template')
@section('content')
<div id="ReviewerProjectReport" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="callout callout-info mr-tp-5" v-if="Projects.length==0">
                    <h4>Information!</h4>
                    <p>No projects found.</p>
                </div>
                <div class="box-body no-padding pd-bt-10" id="ProjectsList" v-else>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="ReportTableNoAction">
                            <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                                <tr>
                                    <th class="text-center text-vertical-align pd-10" width="8%">S.No</th>
                                    <th width="30%" class="text-center text-vertical-align">Name</th>
                                    <th width="30%" class="text-center text-vertical-align">QuickEstimation</th>
                                    <th width="32%" class="text-center text-vertical-align">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(Project, key) in Projects">
                                    <td class="text-center text-vertical-align" style="text-align:center;">@{{ Project.SNo }}</td>
                                    <td class="text-vertical-align">@{{ Project.Project }}</td>
                                    <td class="text-vertical-align">@{{ Project.QuickEstimation }}</td>
                                    <td class="text-vertical-align">@{{ Project.Status }}</td>
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

@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
<link href="{{ URL::assetUrl('/css/myprojects/loader.css') }}" rel="stylesheet" />
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl("/js/Vue/vue.js")}}"></script>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ URL::assetUrl("/js/myprojects/reviewer.js")}}"></script>
<script src="{{ URL::assetUrl("/js/myprojects/tableInitialise.js")}}"></script>
@endsection