@extends('layouts/master_template')

@section('dynamicStyles')
<link href="{{ URL::assetUrl('/AdminLTE/plugins/jQueryUI/jquery-ui.min.css') }}" rel="stylesheet"/>
<link rel="stylesheet" href="{{ URL::assetUrl("/css/magnific-popup.css")}}">
<link rel="stylesheet" href="{{ asset('css/catalogue/designer/compare.css') }}">
@endsection

@section('content')
<div id="LaminatesComparisonPage" v-cloak>
    <div class="row">
        <div class="col-md-12 text-right custom-info-block" :class="{ hidden : (CustomerDetails == null) }">
             <span class="pd-5 text-capitalize user-info">
                <i class="fa fa-user text-info" aria-hidden="true"></i>&nbsp;
                @{{ !(CustomerDetails == null) ? CustomerDetails.userName : '' }}
            </span>
            <span class="pd-5 user-info">
                <i class="fa fa-phone-square text-info" aria-hidden="true"></i>&nbsp;
                @{{ !(CustomerDetails == null) ? CustomerDetails.mobile: '' }}
            </span>
            <span class="pd-5 user-info"> 
                <i class="fa fa-envelope-square text-info" aria-hidden="true"></i>&nbsp;
                @{{ !(CustomerDetails == null) ? CustomerDetails.email: '' }}
            </span>
        </div>
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h4 class="box-title no-text-transform fl-lt comparelams-title" v-html="'Compare ' + ComparisonLaminates()"></h4>
                </div>
                <div class="box-body pd-tp-0 pd-rt-0 pd-bt-10 pd-lt-0">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="ComparisonTable">
                            <thead style="border-top: 1px solid #f4f4f4;border-left: 1px solid #f4f4f4">
                                <!--Table headings-->
                                @include('catalogue.laminates.customers.partials.comparelaminates.tableheadings')
                            </thead>
                            <tbody>
                                <tr v-if='!_.isEmpty(ComparisonLaminate)'>
                                <td width='20%'>
                                    <!--Laminate Specifications-->
                                    @include('catalogue.laminates.customers.partials.comparelaminates.laminatepecifications')
                                </td>
                                <td class="text-center" width='20%'>
                                    <!--Selected laminate info-->
                                    @include('catalogue.laminates.customers.partials.comparelaminates.selectedlaminate')
                                </td>
                                <td class="text-center text-vertical-align" width='20%'>
                                    <!--First Search box result laminate info-->
                                    @include('catalogue.laminates.customers.partials.comparelaminates.firstsearchresult')
                                </td>
                                <td class="text-center text-vertical-align" width='20%'>
                                    <!--Second Search box result laminate info-->
                                    @include('catalogue.laminates.customers.partials.comparelaminates.secondsearchresult')
                                </td>
                                <td class="text-center text-vertical-align" width='20%'>
                                    <!--Third Search box result laminate info-->
                                    @include('catalogue.laminates.customers.partials.comparelaminates.thirdsearchresult')
                                </td>
                                </tr>
                                <tr v-else>
                                <td colspan="5" class="text-center pd-12">Selected laminate not available.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <form id="CompareLaminatesForm" method="POST" action="{{route('catalogue.laminate.combination.add')}}" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-xs-12 text-center"> 
                                <button type="reset" class="btn pd-rt-25 pd-lt-25" title="Go back to Selections list" id="CancelBtn">Cancel</button>
                                <button type="submit" class="btn btn-primary pd-rt-25 pd-lt-25" id="CompareLaminatesFormSubmit" @click.prevent="submitSelections($event)">Shortlist Selection</button>
                            </div>
                        </div>
                    </form>
                </div>                 
            </div>
            <div id="NotificationArea" class="hidden">
                <div class="alert alert-dismissible"></div>
            </div> 
            <div class="overlay" v-if="ShowFinalizeCombOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Saving Selections</div>
            </div>
            <!--Notification popup-->
            <overlay-notification :form-over-lay="MessageFormOverlay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
        </div>
    </div>
</div>
@endsection

@section('dynamicScripts')
<script>
    var bootstrapTooltip = $.fn.tooltip.noConflict();
    $.fn.bstooltip = bootstrapTooltip;
</script>
<script src="{{ URL::assetUrl('/AdminLTE/plugins/jQueryUI/jquery-ui.min.js') }}"></script>
<script src="{{ asset('/js/catalogue/designer/comparelaminates.js') }}"></script>
@endsection