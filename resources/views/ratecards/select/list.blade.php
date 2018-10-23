@extends('layouts/master_template')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="City">City*</label>
                                <select name="City" id="City" class="form-control">
                                    <option value="">Choose a City</option>
                                    @foreach ($cities as $city)
                                        <option value="{{ $city->id }}">{{ $city->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="SelectionOverlay" class="overlay hidden">
                        <div class="loader-text">Fetching Ratecards...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section("dynamicStyles")
    <link href="{{ asset("css/vendor/select2.min.css") }}" rel="stylesheet"/>
@endsection

@section("dynamicScripts")
    <script src="{{ asset("js/vendor/select2.min.js") }}"></script>
    <script src="{{ asset("js/ratecards/select/list.min.js") }}"></script>
@endsection
