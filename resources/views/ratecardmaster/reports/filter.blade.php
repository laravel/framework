@extends('layouts/master_template')
@section('content')
<div class="row">
    <div class="col-md-12">     
        <div class="box box-primary">
            <div class="box-header with-border">
                <form id="RateCardFilterForm" method="POST">
                    <div class="row">
                        <div class="col-md-3">
                            <select class="form-control" name="City" id="City">
                                @foreach($City as  $Key=>$City)
                                <option value="{{ $Key }}">{{ $City }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row all-radio">
                        <div class="col-md-12">
                            <div class="radio form-group">
                                <input type="radio"  name="Status" value="allspecifications" class="input-radio" id="CurrentActiveRate" />
                                <label for="CurrentActiveRate" tabindex="0"></label>
                                <label for="CurrentActiveRate" class="text-normal cursor-pointer mr-rt-8">Current Active Rate card for All Specification</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="radio">
                                <input type="radio"  name="Status" value="selectedspecifications" class="input-radio" id="CurrentActiveRateSpecific" />
                                <label for="CurrentActiveRateSpecific" tabindex="0"></label>
                                <label for="CurrentActiveRateSpecific" class="text-normal cursor-pointer mr-rt-8">Current Active Rate card for a selected specification</label>                                   
                            </div>
                        </div>
                        <div class="col-md-3 package-dropdown">
                            <select class="form-control" id="Package" name="Package">
                                @foreach($PricePackages as $Key => $PricePackage)                               
                                <option value='{{ $PricePackage->Id }}' <?php
                                if ($PricePackage->Name == 'Market Standard') {
                                    echo "selected='selected'";
                                }
                                ?>>{{ $PricePackage->Name }}</option>
                                @endforeach
                            </select>                               
                        </div> 
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="radio">  
                                <input type="radio"  name="Status" value="historicrate" class="input-radio" id="RateCardHistoric" />
                                <label for="RateCardHistoric" tabindex="0"></label>
                                <label for="RateCardHistoric" class="text-normal cursor-pointer mr-rt-8">Rate card for specific item - Historic rate changes</label> 
                            </div>
                        </div>
                        <div class="col-md-4 item-dropdown">
                            <select class="form-control" name="RateCardItem" id="RateCardItem">                                  
                                @foreach($Items as $Key => $Item)
                                <option value='{{ $Item->Id }}' <?php
                                if ($Item->Name == 'Box with 12-24" Depth') {
                                    echo "selected='selected'";
                                }
                                ?>>{{ $Item->Name }}</option>
                                @endforeach
                            </select> 
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="radio">
                                <input type="radio"  name="Status" value="ratecardwithinperiod" class="input-radio" id="RateCardForPeroid" />
                                <label for="RateCardForPeroid" tabindex="0"></label>
                                <label for="RateCardForPeroid" class="text-normal cursor-pointer mr-rt-8">Rate card for All Specification for a particular peroid</label>    
                            </div>
                        </div>
                        <div class="col-md-3 from-date">     
                            <div class="has-feedback">                  
                                <input type="text" name="FromDate" id="FromDate" placeholder="From Date" class="form-control input-sm date-picker"  data-provide="datepicker" readonly="true" />
                                <i class="fa fa-calendar form-control-feedback"></i>
                            </div>
                        </div>
                        <div class="col-md-3 from-date">
                            <div class="has-feedback">
                                <input type="text" name="ToDate" id="ToDate" class="form-control input-sm date-picker" placeholder="To Date" data-provide="datepicker" readonly="true" />
                                <i class="fa fa-calendar form-control-feedback"></i>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">
                            <div class="radio">
                                <input type="radio"  name="Status" value="ratecardwithinperiodwithspec" class="input-radio" id="RateCardSpecificPeroid" />
                                <label for="RateCardSpecificPeroid" class="col-md-2" tabindex="0"></label>
                                <label for="RateCardSpecificPeroid" class="text-normal col-md-10 cursor-pointer mr-rt-8">Rate card for a selected specification for a particular peroid</label>      
                            </div>
                        </div>
                        <div class="col-md-3 specific-period-selection">
                            <select class="form-control" id="Packages" name="Packages">
                                @foreach($PricePackages as $Key => $PricePackage)                               
                                <option value='{{ $PricePackage->Id }}'>{{ $PricePackage->Name }}</option>
                                @endforeach
                            </select>  
                        </div>
                        <div class="col-md-2 specific-period-selection">     
                            <div class="has-feedback">                  
                                <input type="text" name="SpecificFromDate" id="SpecificFromDate" placeholder="From Date" class="form-control input-sm date-picker"  data-provide="datepicker" readonly="true" />
                                <i class="fa fa-calendar form-control-feedback"></i>
                            </div>
                        </div>
                        <div class="col-md-2 specific-period-selection">
                            <div class="has-feedback">
                                <input type="text" name="SpecificToDate" id="SpecificToDate" class="form-control input-sm date-picker" placeholder="To Date" data-provide="datepicker" readonly="true" />
                                <i class="fa fa-calendar form-control-feedback"></i>
                            </div>
                        </div>
                        <div class="form-group col-md-6 col-md-offset-4 col-sm-8 col-sm-offset-4 col-xs-9 col-xs-offset-3">
                            <span class="input-group-btn">
                                <button type="submit" id="generateRateCardReport" class="btn btn-primary btn-flat">Generate</button>
                            </span>
                        </div>
                    </div>
                </form>
                <div class="callout hidden callout-warning" id="Notification">  
                    <p class="alert-body" id="ErrorMessage"></p>
                </div>
                <div id="RateCard_Loader" class="hidden"><div class="large loader"></div>
                    <div class="loader-text">Fetching Reports...</div>
                </div>
                <div class="hidden" id="ItemsNotFoundMsg">
                    <div class='callout callout-info'><h4>No RateCards Found!</h4><p>No Items' Current RateCards are available for the selected City. Choose another City or <a href="{{ route('de-items.create') }}">Add an Item</a>.</p></div>
                </div>
            </div>
            <div class="row" id="ReportResult" style="display: none;"> 
                <div id="RateCardResults" class="col-sm-12">
                </div>
            </div>
        </div>
        </div>
    </div>
@endsection

@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
<link rel="stylesheet" href="{{ URL::assetUrl('/plugins/select2/select2.min.css') }}" />
<link rel="stylesheet" href="{{ URL::assetUrl('/plugins/datepicker/bootstrap-datepicker.min.css') }}" />
<link rel="stylesheet" href="{{ URL::assetUrl('/css/RateCardFilter.css') }}" />
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ URL::assetUrl('/plugins/datepicker/bootstrap-datepicker.min.js') }}"></script>
<script src="{{ URL::assetUrl('/plugins/select2/select2.min.js') }}"></script>
<script src="{{ URL::assetUrl('/js/ratecardmaster/reports/list.js') }}"></script>
@endsection