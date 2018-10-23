@extends('layouts/master_template')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <form action="{{ $currentItem->getRatecardStoreRoute($cityId) }}" method="POST" id="CreateRatecardForm">
                    <div class="box-body">
                        @if (is_bool($pricePackages))
                            <div class="callout callout-info">
                                <h4>Information!</h4>
                                <p>There are Current RateCards available for the selected Item and City. Choose another Item and City or <a href="{{ $currentItem->getRatecardUpdateRoute($cityId) }}" title="Update RateCards">Update RateCards</a> for the selected Item and City or <a href="{{ route("de-items.create") }}" title="Add an Item">Add an Item</a>.</p>
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
                                    <table class="table table-bordered table-hover">
                                        <thead style="border-top: 1px solid #f4f4f4">
                                            <tr>
                                                <th width="19%"></th>
                                                <th width="27%" class="text-center">Customer Rate (&#8377;)*</th>
                                                <th width="27%" class="text-center">Vendor Rate (&#8377;)*</th>
                                                <th width="27%" class="text-center">Start Date*</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($pricePackages as $package)
                                                <tr>
                                                    <td style="vertical-align:middle"><b>{{ $package->name }}</b></td>
                                                    <td>
                                                        <input type="number" name="CustomerRate{{ $package->id }}" class="form-control" id="CustomerRate{{ $package->id }}" max="99999.00" autocomplete="off" data-msg-name="Customer Rate"/>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="VendorRate{{ $package->id }}" class="form-control" id="VendorRate{{ $package->id }}" max="99999.00" autocomplete="off" data-msg-name="Vendor Rate"/>
                                                    </td>
                                                    <td>
                                                        <div class="has-feedback">
                                                            <input type="text" name="StartDate{{ $package->id }}" class="form-control date-picker" id="StartDate{{ $package->id }}" readonly="true" data-msg-name="Start Date"/>
                                                            <i class="fa fa-calendar form-control-feedback"></i>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <input type="submit" value="Save" class="btn btn-primary button-custom" id="CreateRatecardFormSubmit"/>
                                    <input type="reset" value="Undo Changes" class="btn button-custom" id="CreateRatecardFormReset"/>
                                </div>
                            </div>
                        @endif
                    </div>
                </form>
                <div id="CreateRatecardFormOverlay" class="overlay hidden">
                    <div class="large loader"></div>
                    <div class="loader-text">Creating Ratecard...</div>
                </div>
            </div>
            <div id="CreateRatecardFormNotificationArea" class="notification-area hidden">
                <div class="alert alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <p class="body"></p>
                </div>
            </div>
        </div>
    </div>
@include('notificationOverlay')
@endsection

@section("dynamicStyles")
    <link href="{{ asset('/plugins/datepicker/bootstrap-datepicker.min.css') }}" rel="stylesheet"/>
@endsection

@section('dynamicScripts')
    <script src="{{ asset('/js/common.js') }}"></script>
    <script src="{{ asset('/plugins/datepicker/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ URL::assetUrl('/js/NotificationOverlay.js') }}"></script>
    <script src="{{ asset('js/ratecards/create.min.js') }}"></script>
@endsection
