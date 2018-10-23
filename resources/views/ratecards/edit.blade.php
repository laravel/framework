@extends('layouts/master_template')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <form action="{{ $currentItem->getRatecardUpdateRoute($cityId) }}" method="POST" id="UpdateRatecardForm">
                    {{ method_field("PATCH") }}
                    <div class="box-body">
                        @if (is_bool($pricePackages))
                            <div class="callout callout-info">
                                <h4>Information!</h4>
                                <p>No Current RateCards are available for the selected Item and City. Choose another Item and City or <a href="{{ $currentItem->getRatecardStoreRoute($cityId) }}" title="Add RateCards">Add RateCards</a> for the selected Item and City or <a href="{{ route("de-items.create") }}" title="Add an Item">Add an Item</a>.</p>
                            </div>
                        @else
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Code</label>
                                        <div class="form-control-static break-text">{{ $currentItem->code }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Unit</label>
                                        <div class="form-control-static">{{ $currentItem->unit }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Description</label>
                                        <div class="form-control-static">{{ $currentItem->description }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                                            <tr>
                                                <th rowspan="2" width="20%" style="vertical-align:middle">Price Package</th>
                                                <th colspan="2" width="24%" class="text-center">Existing Rates</th>
                                                <th colspan="2" width="30%" class="text-center text-primary-align">New Rates</th>
                                                <th rowspan="2" width="11%" class="text-center text-vertical-align">Start Date</th>
                                                <th rowspan="2" width="15%" class="text-center text-vertical-align">New Start Date</th>
                                            </tr>
                                            <tr>
                                                <th class="text-center" width="12%">Customer Rate (&#8377;)</th>
                                                <th class="text-center" width="12%">Vendor Rate (&#8377;)</th>
                                                <th class="text-center" width="15%">Customer Rate (&#8377;)</th>
                                                <th class="text-center" width="15%">Vendor Rate (&#8377;)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @include("ratecards.partials.create.pricepackages")
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <input type="submit" value="Update" class="btn btn-primary button-custom" id="UpdateRatecardFormSubmit"/>
                                    <input type="reset" value="Undo Changes" class="btn button-custom" id="UpdateRatecardFormReset"/>
                                </div>
                            </div>
                        @endif
                    </div>
                </form>
                <div id="UpdateRatecardFormOverlay" class="overlay hidden">
                    <div class="large loader"></div>
                    <div class="loader-text">Updating Ratecard...</div>
                </div>
            </div>
            <div id="UpdateRatecardFormNotificationArea" class="notification-area hidden">
                <div class="alert alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <p class="body"></p>
                </div>
            </div>
        </div>
    </div>
    @if (! is_bool($pricePackages))
        @include("ratecards.partials.create.futureratecards.modal")
    @endif
@include('notificationOverlay')
@endsection

@section("dynamicStyles")
    <link href="{{ asset('/plugins/datepicker/bootstrap-datepicker.min.css') }}" rel="stylesheet"/>
@endsection

@section('dynamicScripts')
    <script src="{{ asset('/js/common.js') }}"></script>
    <script src="{{ asset('/plugins/datepicker/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ URL::assetUrl('/js/NotificationOverlay.js') }}"></script>
    <script src="{{ asset('js/ratecards/edit.min.js') }}"></script>
@endsection
