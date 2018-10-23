@extends('layouts/master_template')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="Item">Item*</label>
                                <select name="Item" id="Item" class="form-control">
                                    <option value="">Select</option>
                                    @foreach ($items as $item)
                                        <option value="{{ $item->id }}">{{ $item->description }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="overlay hidden" id="SelectionOverlay">
                    <div class="loader-text">Fetching item...</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section("dynamicStyles")
    <link rel="stylesheet" href="{{ asset("css/vendor/select2.min.css") }}">
@endsection

@section("dynamicScripts")
    <script src="{{ asset("js/vendor/select2.min.js") }}"></script>
    <script src="{{ asset("js/qeitems/select.min.js") }}"></script>
@endsection
