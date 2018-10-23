@extends('layouts/master_template')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <form action="{{ $currentItem->getUpdateRoute() }}" method="POST" id="UpdateItemForm">
                        {{ method_field("PATCH") }}
                        <input type="hidden" name="ItemId" value="{{ $currentItem->id }}">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="Code">Code*</label>
                                    <input type="text" name="Code" id="Code" class="form-control" placeholder='24IN_DEP_PLYBOX' value="{{ $currentItem->code }}" autofocus="autofocus"/>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="Name">Name*</label>
                                    <input type="text" name="Name" id="Name" class="form-control" placeholder='24 Inch Depth Plywood Box with Shutters' value="{{ $currentItem->name }}"/>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="Unit">Unit*</label>
                                    <div class="form-control-static">{{ $currentItem->unitName }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label for="Description">Description*</label>
                                    <textarea name="Description" id="ItemDescription" class="form-control" rows="4" placeholder='A box made of plywood with 24-26" depth, 6 mm plywood with lamination back and doors made with plywood laminated on both sides with edges edge banded with high quality PVC strips or polished wooden strips, based on the design, and with drawers, hanger rods and shelves as per the design' style="resize:none">{{ $currentItem->description }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <p>
                                    <input type="submit" id="UpdateItemFormSubmit" value="Update" class="btn btn-primary button-custom"/>
                                    <input type="reset" id="UpdateItemFormReset" value="Undo Changes" class="btn button-custom" />
                                </p>
                            </div>
                        </div>
                    </form>
                    <div id="UpdateItemFormOverlay" class="overlay hidden">
                        <div class="large loader"></div>
                        <div class="loader-text">Updating Item...</div>
                    </div>
                </div>
                <div id="UpdateItemFormNotificationArea" class="notification-area hidden">
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
    <link href="{{ asset('css/vendor/select2.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('css/item/overlay.css') }}" rel="stylesheet"/>
@endsection

@section('dynamicScripts')
    <script src="{{ asset('js/common.js')}}"></script>
    <script src="{{ asset('js/vendor/select2.min.js') }}"></script>
    <script src="{{ URL::assetUrl('/js/NotificationOverlay.js') }}"></script>
    <script src="{{ asset('js/items/edit.min.js') }}"></script>
@endsection
