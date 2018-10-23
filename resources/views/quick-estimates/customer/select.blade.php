@extends("layouts/master_template")

@section("content")
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    @if ($enquiries->isEmpty())
                        <div class="callout callout-info">
                            <h4>Information!</h4>
                            <p>No Enquiries are avaiable to create a quick estimate.</p>
                        </div>
                    @else
                        <form action="{{ $estimateRoute }}" method="GET" id="SelectEnquiryForm">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="Enquiry">Enquiry*</label>
                                        <select name="Enquiry" id="Enquiry" class="form-control" data-rooms-route="{{ $enquiryRoomsRoute }}">
                                            <option value="">Select an Enquiry</option>
                                            @foreach ($enquiries as $enquiry)
                                                <option value="{{ $enquiry->id }}">{{ $enquiry->reference }}({{ $enquiry->name }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 hidden-item hidden">
                                    <div class="form-group">
                                        <label for="City">City</label>
                                        <select name="City" id="City" class="form-control" disabled="disabled">
                                            <option value="">Select an City</option>
                                            @foreach ($cities as $city)
                                                <option value="{{ $city->id }}">{{ $city->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row hidden-item hidden">
                                <div class="col-md-12">
                                    <label for="Rooms">Rooms*</label>
                                    <span class="mr-lt-20"><span id="RoomsCount">0</span> Rooms selected</span>
                                    <div class="help-block">Please select the room for which you want to do interiors.</div>
                                </div>
                            </div>
                            <div class="row hidden-item hidden">
                                <div class="col-md-12">
                                    @foreach ($rooms as $room)
                                        <input type="checkbox" name="Rooms[]" value="{{ $room->id }}" id="{{ $room->id }}" class="input-checkbox-tile room hidden"/>
                                        <label for="{{ $room->id }}" class="pd-lt-6 pd-tp-6 text-hover">{{ $room->name }}</label>
                                    @endforeach
                                </div>
                                <div class="col-md-12 has-error" id="RoomsErrorBlock"></div>
                            </div>
                            <div class="row hidden-item hidden">
                                <div class="col-md-8 mr-tp-20">
                                    <input type="submit" id="SelectEnquiryFormSubmit" value="Submit" class="btn btn-primary button-custom"/>
                                </div>
                            </div>
                        </form>
                        <div id="SelectEnquiryFormOverlay" class="overlay hidden">
                            <div class="loader-text no-padding-top">Fetching Quick Estimate Form...</div>
                        </div>
                    @endif
                </div>
            </div>
            <div id="SelectEnquiryFormNotificationArea" class="notification-area hidden"></div>
        </div>
    </div>
@endsection

@section("dynamicStyles")
    <link rel="stylesheet" type="text/css" href="{{ asset("css/vendor/select2.min.css") }}"/>
@endsection

@section("dynamicScripts")
    <script src="{{ asset("js/common.js") }}"></script>
    <script src="{{ asset("js/vendor/select2.min.js") }}"></script>
    <script src="{{ asset("js/quick-estimates/select.min.js") }}"></script>
@endsection