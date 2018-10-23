@extends('layouts/master_template')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-body">
                @if ($ratecards->isEmpty())
                    <div class="callout callout-info">
                        <h4>No Ratecards available!</h4>
                        <p>No Items Current RateCards are available for the selected City. Choose another City or <a href="{{ route("de-items.create") }}" title="Add an Item">Add an Item</a>.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table id="RatecardsList" class="table table-striped table-bordered">
                            <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                                <tr>
                                    <th rowspan="2" class="text-vertical-align text-center">S.No</th>
                                    <th rowspan="2" class="text-vertical-align text-center">Item Name</th>
                                    <th rowspan="2" class="text-vertical-align text-center" width="8%">Unit</th>
                                    @foreach ($pricePackages as $pricePackage)
                                        <th colspan="2" class="text-center text-vertical-align">{{ $pricePackage->name }}</th>
                                    @endforeach
                                </tr>
                                <tr>
                                    @foreach ($pricePackages as $pricePackage)
                                        <th class="text-center">Customer Rate (&#8377;)</th>
                                        <th class="text-center">Vendor Rate (&#8377;)</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ratecards as $ratecard)
                                    <tr>
                                        <td class="text-vertical-align text-center">{{ $loop->iteration }}</td>
                                        <td class="text-vertical-align">{{ $ratecard->name }}</td>
                                        <td class="text-vertical-align">{{ $ratecard->unit }}</td>
                                        @foreach ($ratecard->pricePackages as $pricePackage)
                                            <td class="text-vertical-align text-center">{{ money_format('%!i', $pricePackage->currentRatecard->customerRate) }}</td>
                                            <td class="text-vertical-align text-center">{{ money_format('%!i', $pricePackage->currentRatecard->vendorRate) }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section("dynamicStyles")
    <link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
@endsection

@section('dynamicScripts')
    <script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js") }}"></script>
    <script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js") }}"></script>
    <script src="{{ asset("js/ratecards/list.min.js") }}"></script>
@endsection
