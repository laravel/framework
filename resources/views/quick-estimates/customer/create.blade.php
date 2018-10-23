@extends("layouts/master_template")

@section("content")
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                @if ($enquiries->isEmpty())
                    <div class="box-header">
                        <div class="callout callout-info">No Enquiries found to create a new Quick Estimate.</div>
                    </div>
                @else
                    <div id="CreateQuickEstimateApplication">
                        <form id="CreateQuickEstimateForm" method="POST" action="{{ $storeRoute }}" data-bootstrap-url="{{ $bootstrapRoute }}">
                            @include("quick-estimates.customer.partials.create.box-header")
                            <div class="box-body pd-0 table-responsive">
                                @include("quick-estimates.customer.partials.create.sticky-header")
                                <div id="CreateQuickEstimate">
                                    @include("quick-estimates.customer.partials.create.table")
                                </div>
                                <p class="text-center pd-8">
                                    <button type="submit" id="QuickEstimationFormSubmit" class="btn btn-primary button-custom" :disabled="showNoItemsWarning">Submit</button>
                                    <button type="reset" id="QuickEstimationFormReset" class="btn btn-default">Undo Changes</button>
                                    <button type="button" id="QuickEstimationGoToTop" class="btn btn-default pull-right">Top</button>
                                </p>
                            </div>
                            <div class="callout callout-warning mr-tp-5 mr-bt-15" v-if="showNoItemsWarning">
                                <i class="fa fa-warning" aria-hidden="true"></i>
                                <span class="mr-lt-6">Please select atleast one item in any room for which you want to do interiors.</span>
                            </div>
                            <div id="CreateQuickEstimateFormNotificationArea" class="notification-area hidden"></div>
                            @include("quick-estimates.customer.partials.create.notes")
                            <div id="CreateQuickEstimateFormOverlay" class="overlay hidden">
                                <div class="large loader"></div>
                                <div class="loader-text">Fetching Quick Estimation Items...</div>
                            </div>
                        </form>
                        <div id="success-notify" class="hidden mr-8">
                            <div class="callout callout-success">
                                <h4>Checklist submitted</h4>
                                <p>{{ trans('miscellaneous.quickestimatecreated') }}</p>
                            </div>
                        </div>
                        {{-- Update rooms modal --}}
                        @include("quick-estimates.customer.partials.create.rooms")
                        {{-- Specifications and ratecards modals --}}
                        @include("quick-estimates.manager.partials.create.specifications")
                        @include("quick-estimates.manager.partials.create.ratecards")
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section("dynamicStyles")
    <link rel="stylesheet" href="{{ asset("css/vendor/select2.min.css") }}">
    <link rel="stylesheet" href="{{ asset("css/quickestimate/create.css") }}">
@endsection

@section("dynamicScripts")
    <script src="{{ asset("js/common.js") }}"></script>
    <script src="{{ asset("js/vendor/select2.min.js") }}"></script>
    <script src="https://unpkg.com/vue/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/lodash/4.17.4/lodash.min.js"></script>
    <script src="{{ asset("js/quick-estimates/create.min.js") }}"></script>
@endsection
