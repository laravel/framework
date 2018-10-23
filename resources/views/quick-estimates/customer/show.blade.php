@extends("layouts/master_template")

@section("content")
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                @include("quick-estimates.customer.partials.show.box-header")
                <div class="box-body pd-0 table-responsive">
                    @include("quick-estimates.customer.partials.show.sticky-header")
                    @include("quick-estimates.customer.partials.show.table")
                    <p class="text-center pd-8">
                        <button type="button" id="QuickEstimationGoToTop" class="btn btn-default pull-right">Top</button>
                    </p>
                    @include("quick-estimates.customer.partials.show.notes")
                </div>
                {{-- Specifications and ratecards modals --}}
                @include("quick-estimates.customer.partials.show.specifications")
                @include("quick-estimates.customer.partials.show.ratecards")
            </div>
        </div>
    </div>
@endsection

@section("dynamicStyles")
    <link rel="stylesheet" href="{{ asset("css/vendor/select2.min.css") }}">
    <link rel="stylesheet" href="{{ asset("css/quickestimate/create.css") }}">
@endsection

@section("dynamicScripts")
    <script src="{{ asset("js/quick-estimates/show.min.js") }}"></script>
@endsection
