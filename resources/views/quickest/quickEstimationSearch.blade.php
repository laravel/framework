@extends('layouts/master_template')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Browse Estimations</h3>
            </div>
            <div class="box-header with-border">
                <form id="EstimationSearchForm" method="GET" action="{{ route('searchquickestimation') }}">
                    <div class="row">
                        <div class="form-group col-md-5 col-sm-8">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="(898) 989-9898 or user@example.com" autofocus="true" id="Username" name="Username" value="{{ isset($SearchString) ?  $SearchString : '' }}"/>
                                <span class="input-group-btn">
                                    <button type="submit" id="SearchEstimations" class="btn btn-primary btn-flat">Search</button>
                                </span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="box-body {{ isset($SearchString) ?  '' : 'hidden' }}" id="EstimationBox">
                <caption class="SearchCaption"><i>Search results for the given Search Term - <u id="EstimationSearchTerm">{{ isset($SearchString) ?  $SearchString : '' }}</u></i></caption>
                
                <div id="CalloutsArea">
                    @if(isset($SearchString) && empty($EstimationInfo["data"]["Estimations"]))
                    <div class="callout callout-{{ $EstimationInfo['data']['alertType'] }}">
                        <h4 id="CalloutTitle">{{ $EstimationInfo['data']['alertTitle'] }}</h4>
                        <p id="CalloutBody">{!! $EstimationInfo['data']['alertMessage'] !!}</p>
                    </div>
                    @endif
                </div>
                
                <div class="table-responsive {{ (isset($SearchString) && empty($EstimationInfo['data']['Estimations'])) ? 'hidden' : '' }}" id="EstimationList">
                    <table class="table table-striped table-bordered" id="EstimationSearchTable">    
                        <thead id="EstimationsListHeader">
                            <tr>
                                <th colspan="5" class="text-left bg-white"></th>
                                <th colspan="3" class="text-center bg-white">Total</th>                                   
                                <th colspan="2" class="text-left bg-white"></th>
                            </tr>                                
                            <tr>
                                <th width="1%" class="text-vertical-align text-center">S.No</th>
                                <th width="10%" class="text-vertical-align text-center">QE No</th>
                                <th width="10%" class="text-vertical-align text-center">Enquiry</th>
                                <th width="15%" class="text-vertical-align text-center">Site Address</th>
                                <th width="8%" class="text-vertical-align text-center">Unit</th>
                                <th width="14%" class="text-center bg-orange amount-text">                                        
                                   {{$PricePackages[0]['Name']}}
                                </th>
                                <th width="14%" class="text-center bg-aqua amount-text">
                                    {{$PricePackages[1]['Name']}}
                                </th>
                                <th width="14%" class="text-center bg-green amount-text">
                                    {{$PricePackages[2]['Name']}}
                                </th>
                                <th width="10%" class="text-vertical-align text-center">Work Type</th>
                                <th width="4%" class="text-vertical-align text-center"></th>
                            </tr>
                        </thead>
                        <tbody id="SearchResults">
                            @if(isset($SearchString) && !empty($EstimationInfo['data']['Estimations']))
                            {!! $EstimationInfo['data']['ViewData'] !!}
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="form-overlay hidden" id="SearchCustomersFormOverlay">
        <div class="large loader"></div>
        <div class="loader-text">Fetching Estimations...</div>
    </div>
    <div id="NotificationArea"></div>
</div>
@endsection

@section('dynamicStyles')
<link href="{{ URL::assetUrl('/AdminLTE/plugins/jQueryUI/jquery-ui.min.css') }}" rel="stylesheet" />
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
<link rel="stylesheet" href="{{ URL::assetUrl('/css/estimationsearch/Search.css') }}" />
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ URL::assetUrl('/js/quickestimationsearch/Search.js') }}"></script>
<script src="{{ URL::assetUrl('/AdminLTE/plugins/jQueryUI/jquery-ui.min.js') }}"></script>
@endsection