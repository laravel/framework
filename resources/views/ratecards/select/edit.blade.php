@extends('layouts/master_template')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    @if($items->isEmpty())
                        <div class="callout callout-info">
                            <h4>Information!</h4>
                            <p>No Items are avaiable to update RateCards. Click here to <a href="{{ route('items.create') }}" title="Add an Item">Add an Item</a>.</p>
                        </div>
                    @else
                        <form id="SelectItemForm">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="Item">Item*</label>
                                        <select name="Item" id="Item" class="form-control" style="width:100%">
                                            <option value="">Select an Item</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="City">City*</label>
                                        <select name="City" id="City" class="form-control" style="width:100%">
                                            <option value="">Select a City</option>
                                            @foreach($cities as $city)
                                                <option value="{{ $city->id }}">{{ $city->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section("dynamicStyles")
    <link href="{{ asset('css/vendor/select2.min.css') }}" rel="stylesheet"/>
@endsection

@section('dynamicScripts')
    <script src="{{ asset('js/vendor/select2.min.js') }}"></script>
    <script src="{{ asset('js/ratecards/select/edit.min.js') }}"></script>
@endsection
