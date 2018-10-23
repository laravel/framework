@extends('layouts/master_template')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <form action="{{ route("de-items.store") }}" method="POST" id="CreateItemForm">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="Code">Code*</label>
                                    <input type="text" name="Code" id="Code" class="form-control" placeholder='24IN_DEP_PLYBOX' autofocus="autofocus"/>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="Name">Name*</label>
                                    <input type="text" name="Name" id="Name" class="form-control" placeholder='24 Inch Depth Plywood Box with Shutters'/>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="Unit">Unit*</label>
                                    <select name="Unit" id="Unit" class="form-control" data-placeholder="Select Unit from Dropdown" style="width:100%">
                                        <option value="">Select Units</option>
                                        @foreach ($units as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label for="Description">Description*</label>
                                    <textarea name="Description" id="Description" class="form-control" rows="4" placeholder='A box made of plywood with 24-26" depth, 6 mm plywood with lamination back and doors made with plywood laminated on both sides with edges edge banded with high quality PVC strips or polished wooden strips, based on the design, and with drawers, hanger rods and shelves as per the design' style="resize:none"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <p>
                                    <input type="submit" id="CreateItemFormSubmit" value="Save" class="btn btn-primary button-custom"/>
                                    <input type="reset" id="CreateItemFormReset" value="Clear" class="btn button-custom"/>
                                </p>
                            </div>
                        </div>
                    </form>
                    <div id="CreateItemFormOverlay" class="overlay hidden">
                        <div class="large loader"></div>
                        <div class="loader-text">Creating Item...</div>
                    </div>
                </div>
                <div id="CreateItemFormNotificationArea" class="notification-area hidden">
                    <div class="alert alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <p class="body"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@include('notificationOverlay')
@endsection

@section("dynamicStyles")
    <link rel="stylesheet" type="text/css" href="{{ asset("css/vendor/select2.min.css") }}"/>
    <link href="{{ asset('css/item/overlay.css') }}" rel="stylesheet"/>
@endsection

@section('dynamicScripts')
    <script src="{{ asset('/js/common.js') }}"></script>
    <script src="{{ URL::assetUrl('/js/NotificationOverlay.js') }}"></script>
    <script src="{{ asset("js/vendor/select2.min.js") }}"></script>
    <script src="{{ asset('js/items/create.min.js') }}"></script>
@endsection