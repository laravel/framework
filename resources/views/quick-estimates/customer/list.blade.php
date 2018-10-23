@extends("layouts/master_template")

@section("content")
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                @if ($estimates->isEmpty())
                    <div class="box-header with-border">
                        <a href="{{ $createEstimateRoute }}" class="btn btn-primary btn-sm pull-right">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                            <span class="pd-lt-6">New Estimate</span>
                        </a>
                    </div>
                    <div class="box-body">
                        <div class="callout callout-info">No Quick Estimates found to list.</div>
                    </div>
                @else
                    <div class="box-header with-border">
                        <a href="{{ $createEstimateRoute }}" class="btn btn-primary btn-sm pull-right">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                            <span class="pd-lt-6">New Estimate</span>
                        </a>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table id="QuickEstimatesList" class="table table-striped table-bordered">
                                <thead>                              
                                    <tr class="bg-blue">
                                        <th width="4%" class="text-vertical-align text-center pd-rt-6">#</th>
                                        <th width="9%" class="text-vertical-align text-center">QE No</th>
                                        <th width="10%" class="text-vertical-align text-center">Enquiry</th>
                                        <th width="20%" class="text-vertical-align text-center">Site Address</th>
                                        <th width="9%" class="text-vertical-align text-center">Unit</th>
                                        @foreach ($pricePackages as $pricePackage)
                                            <th width="12%" class="text-center text-vertical-align {{ $pricePackage->class }}">
                                                {{ $pricePackage->name }}
                                            </th>
                                        @endforeach
                                        <th width="8%" class="text-vertical-align text-center">Work Type</th>
                                        <th width="4%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($estimates as $estimate)
                                        @include("quick-estimates.manager.partials.list.row")
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('dynamicStyles')
    <link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
    <style type="text/css">
        table.dataTable thead .sorting:after,
        table.dataTable thead .sorting_asc:after,
        table.dataTable thead .sorting_desc:after {
            top: 18px !important;
        }
    </style>
@endsection

@section('dynamicScripts')
    <script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
    <script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
    <script src="{{ asset("js/quick-estimates/list.min.js") }}"></script>
@endsection
