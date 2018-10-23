@extends('layouts/master_template')
@section("dynamicStyles")
<link rel="stylesheet" href="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{ URL::assetUrl('/plugins/multiselect/bootstrap-multiselect.css') }}">
<link rel="stylesheet" href="{{ URL::assetUrl('/css/magnific-popup.css') }}">
<link rel="stylesheet" href="{{ URL::assetUrl('/css/quickestimate/create.css') }}">
@endsection
@section('content')
<div class="row">
    <div class="col-md-12 text-right custom-info-block top-header-user-info">
        <span class="pd-5 text-capitalize user-info">
            <i class="fa fa-user text-info" aria-hidden="true"></i>&nbsp;
            {{ $CustomerFullName }}
        </span>
        <span class="pd-5 user-info">
            <i class="fa fa-phone-square text-info" aria-hidden="true"></i>&nbsp;
            {{ $CustomerMobile }}
        </span>
        <span class="pd-5 user-info"> 
            <i class="fa fa-envelope-square text-info" aria-hidden="true"></i>&nbsp;
            {{ $CustomerEmail }}
        </span>
        @if(count($UserEnquiries) !== 0)
        <span class="pd-5 user-info">             
            <i class="fa fa-globe text-info" aria-hidden="true"></i>&nbsp;<span id="CityName"></span>
        </span>
        @endif
    </div>
    <div class="col-md-12">
        <div class="box box-primary">
            @if(count($UserEnquiries)==0)
            <div class="box-header">
                <div class="callout callout-info">
                    <p id="CalloutBody">Please fill the Enquiry form before generating Quick Estimate.  Would you like to create a <a href="{{route('enquiry', $NewEnquiryHref)}}"><u>New Enquiry</u></a>?</p>
                </div>
            </div>
            @else
            <form id="QuickEstimationForm">
                <div class="box-header with-border">
                    <div class="row">
                        <div class="col-md-2 hidden">
                            <div class="form-group">
                                <select name="City" id="City" class="form-control">
                                    @foreach ($ActiveCities as $ActiveCity)
                                    <option value="{{$ActiveCity->City->Id}}">{{$ActiveCity->City->Name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-3">
                            <div class="form-group">
                                <label for="">Estimation Name *</label>
                                <input type="text" name="QuickEstName" id="QuickEstName" class="form-control" placeholder="Ex: Estimation for Aparna Heights Flat in Gachibowli"/>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-3">
                            <div class="form-group">
                                <label for="">Enquiry *</label>
                                <select name="Enquiry" id="Enquiry" class="form-control">
                                    @foreach ($UserEnquiries as $Enquiry)
                                    <option value="{{$Enquiry->Id}}">{{ $Enquiry->ReferenceNumber.' ('.$Enquiry->Name.')' }}</option>
                                    @endforeach
                                </select>
                                <span class="pull-right EnquiryInfoSticky"><a href="#" data-container="body" data-toggle="popover" data-placement="bottom" data-popover="true" data-content="" data-html="true" class="Info"><i class="fa fa-info" aria-hidden="true"></i></a></span>

                            </div>
                        </div>                       
                        <div class="col-md-4 col-sm-4">
                            <div class="form-group">
                                <label for="Rooms">Rooms*</label>
                                <p class="form-control-static "><span id="RoomsCount"></span>
                                    &nbsp;&nbsp;<a href="#" data-container="body" data-toggle="popover" data-placement="bottom" data-popover="true" class="RoomsView">Change</a>
                                </p>
                                <input name="Rooms[]" type="hidden" id="QERooms" value="" >
                                <input type="hidden" name="SiteAddress" id="SiteAddress" class="form-control" />
                            </div>
                        </div>                    
                    </div>
                </div>
                <div class="box-body pd-0 table-responsive">
                    <table class="table table-bordered hidden" id="QuickEstimationStickyHeader">
                        <thead>
                            <tr>
                                <th colspan="8" class="bg-white">
                                    <div class="row">                                        
                                        <div class="col-md-4 col-sm-4">
                                            <div class="form-group">
                                                <label for="" class="pull-left">Estimation </label>
                                                <input type="text" name="StickyQuickEstName" id="EstNameSticky" class="form-control Details" placeholder="Ex: Estimation for Aparna Heights Flat in Gachibowli"/>
                                            </div>
                                        </div>                                        
                                        <!-- /.col -->                                        
                                        <div class="col-md-4 col-sm-4">
                                            <div class="form-group">
                                                <label for="" class="pull-left">Enquiry</label>
                                                <select name="" id="EnquiryNameSticky" class="form-control Details">
                                                    @foreach ($UserEnquiries as $Enquiry)
                                                    <option value="{{$Enquiry->Id}}">{{ $Enquiry->ReferenceNumber.' ('.$Enquiry->Name.')' }}</option>
                                                    @endforeach
                                                </select>
                                                <span class="pull-right EnquiryInfoSticky"><a href="#" data-container="body" data-toggle="popover" data-placement="bottom" data-popover="true" data-content="" data-html="true" class="Info"><i class="fa fa-info" aria-hidden="true"></i></a></span>
                                            </div>
                                        </div>
                                        <!-- /.col -->
                                        <div class="col-md-4col-sm-4">
                                            <div class="form-group">
                                                <label for="Rooms" class="pull-left">Rooms</label><br>                                               
                                                <p class="form-control-static mr-tp-3"><span class="pull-left Details" id="StickyRoomsCount"></span>
                                                    <a href="#" data-container="body" data-toggle="popover" data-placement="bottom" data-popover="true" class="pull-left RoomsView">&nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-pencil"></i></a>
                                                </p>                                                
                                            </div>
                                        </div>
                                        <!-- /.col -->
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 caution">
                                            <i class="fa fa-info-circle text-light-blue" aria-hidden="true"></i> 
                                            <i class="fa fa-exclamation-triangle text-black" aria-hidden="true"></i>&nbsp; 
                                            <span class="text-center no-text-transform">Indicates comments&nbsp;|&nbsp; 
                                            <i class="fa fa-image text-black" aria-hidden="true"></i>&nbsp; 
                                            <span class="text-center no-text-transform">Indicates reference images</span>&nbsp;|&nbsp;
                                             All dimensions in feet&nbsp;|&nbsp;All amount in Indian Rupees ( <i class="fa fa-rupee"></i> ) </span>&nbsp;
                                        </div>
                                    </div>
                                </th>
                                <th class="text-center brand-bg-color text-vertical-align amount-text">
                                    <i class="fa fa-rupee"></i> 
                                    <span class="SumAmount1">0</span><br/>
                                    {{ $PricePackages[0]['Name'] }}
                                </th>
                                <th class="text-center hechpe-bg-color text-vertical-align amount-text">
                                    <i class="fa fa-rupee"></i> 
                                    <span class="SumAmount2">0</span><br/>
                                    {{ $PricePackages[1]['Name'] }}
                                </th>
                                <th class="text-center market-bg-color text-vertical-align amount-text">
                                    <i class="fa fa-rupee"></i> 
                                    <span class="SumAmount3">0</span><br/>
                                    {{ $PricePackages[2]['Name'] }}
                                </th>
                                <th class="text-center bg-white text-vertical-align speciciations" rowspan="0.5" >
                                    <a href="{{URL::route('quickestimate.specifications')}}" title="" data-toggle="modal" data-target="#specificationModal">Specifications</a></br>
                                <a href="{{URL::route('quickestimate.ratecards')}}" title="" data-toggle="modal" data-target="#ratecardModal">Rate Cards</a>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-blue text-center">
                                <th width="4%" class="text-center">#</th>
                                <th class="text-center" width="30%">Items</th>
                                <th class="text-center" width="3%">Pay</th>
                                <th class="text-center cursor-help" width="3%" title="Select, if this item is required for estimation">Req?</th>
                                <th class="text-center cursor-help" width="5%" title="Required Quantity, if applicable">Nos</th>
                                <th class="text-center cursor-help" width="5%" title="Width of the Item, if applicable">W</th>
                                <th class="text-center cursor-help" width="5%" title="Height/Length of the Item, if applicable">H/L</th>
                                <th class="text-center cursor-help" width="5%" title="Depth of the Item, if applicable">D</th>
                                <th class="text-center brand-bg-color" width="10%" >Amount</th>
                                <th class="text-center hechpe-bg-color" width="10%" >Amount</th>
                                <th class="text-center market-bg-color" width="10%" >Amount</th>
                                <th class="text-center" width="10%">Notes</th>
                            </tr>
                        </tbody>
                    </table>
                    <table class="table table-bordered" id="QuickEstimationTable">
                        <thead id="QuickEstimationTableHeader">
                            <tr>
                                <th colspan="8" class="caution">
                                    <i class="fa fa-info-circle text-light-blue" aria-hidden="true"></i> 
                                            <i class="fa fa-exclamation-triangle text-black" aria-hidden="true"></i>&nbsp; 
                                            <span class="text-center no-text-transform">Indicates comments</span>&nbsp;|&nbsp;
                                            <i class="fa fa-image text-black" aria-hidden="true"></i>&nbsp; 
                                            <span class="text-center no-text-transform">Indicates reference images</span>&nbsp;|&nbsp;
                                            <span class="text-center no-text-transform">All dimensions in feet&nbsp;|&nbsp;All amount in Indian Rupees ( <i class="fa fa-rupee"></i> ) </span>
                                </th>
                                <th class="text-center brand-bg-color text-vertical-align amount-text">
                                    <i class="fa fa-rupee"></i> 
                                    <span class="SumAmount1">0</span><br/>
                                    {{ $PricePackages[0]['Name'] }}
                                </th>
                                <th class="text-center hechpe-bg-color text-vertical-align amount-text">
                                    <i class="fa fa-rupee"></i> 
                                    <span class="SumAmount2">0</span><br/>
                                    {{ $PricePackages[1]['Name'] }}
                                </th>
                                <th class="text-center market-bg-color text-vertical-align amount-text">
                                    <i class="fa fa-rupee"></i> 
                                    <span class="SumAmount3">0</span><br/>
                                    {{ $PricePackages[2]['Name'] }}
                                </th>
                                <th class="text-center bg-white text-vertical-align speciciations" rowspan="0.5" >
                                    <a href="{{URL::route('quickestimate.specifications')}}" title="" data-toggle="modal" data-target="#specificationModal">Specifications</a></br>
                                    <a href="{{URL::route('quickestimate.ratecards')}}" title="" data-toggle="modal" data-target="#ratecardModal">Rate Cards</a>
                                </th>
                            </tr>                        
                        </thead>
                        <tbody id="QuickEstimationTableBody">
                            <tr class="bg-blue text-center">
                                <th class="text-center" width="4%">#</th>
                                <th class="text-center" width="30%">Items</th>
                                <th class="text-center" width="3%">Pay</th>
                                <th class="text-center cursor-help" width="3%" title="Select, if this item is required for estimation">Req?</th>
                                <th class="text-center cursor-help" width="5%" title="Required Quantity, if applicable">Nos</th>
                                <th class="text-center cursor-help" width="5%" title="Width of the Item, if applicable">W</th>
                                <th class="text-center cursor-help" width="5%" title="Height/Length of the Ite, if applicable">H/L</th>
                                <th class="text-center cursor-help" width="5%" title="Depth of the Item, if applicable">D</th>
                                <th class="text-center brand-bg-color" width="10%">Amount</th>
                                <th class="text-center hechpe-bg-color" width="10%">Amount</th>
                                <th class="text-center market-bg-color" width="10%">Amount</th>
                                <th class="text-center" width="10%">Notes</th>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-blue">
                                <th colspan="8" class="text-right text-vertical-align"><span class="pull-right mr-rt-10">Total</span></th>
                                <th class="text-center brand-bg-color text-vertical-align amount-text">
                                    <i class="fa fa-rupee"></i> 
                                    <span class="SumAmount1">0</span><br/>
                                </th>
                                <th class="text-center hechpe-bg-color text-vertical-align amount-text">
                                    <i class="fa fa-rupee"></i> 
                                    <span class="SumAmount2">0</span><br/>
                                </th>
                                <th class="text-center market-bg-color text-vertical-align amount-text">
                                    <i class="fa fa-rupee"></i> 
                                    <span class="SumAmount3">0</span><br/>
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                    <input type="hidden" name="EncUserId" value="{{ $EncUserId }}">
                    <p class="text-center pd-8">
                        <input type="reset" id="QuickEstimationFormReset" class="btn button-custom" value="Clear" />
                        <input type="submit" id="QuickEstimationFormSubmit" class="btn btn-primary button-custom" value="Submit" />
                        <input type="button" id="QuickEstimationGoToTop" class="btn pull-right" value="Top" />
                    </p>
                </div>
                <div id="NotificationArea"></div>
                <div class="form-loader hidden" id="QuickEstimationFormLoader">
                    <div class="overlay">
                        <i class="fa fa-refresh fa-spin"></i>
                    </div>
                </div>
                @include('quickest.Notes')
            </form>
            @endif
            <div class="box-header hidden" id="CalloutNotification">
                <div class="callout">
                    <h4 id="NotificationHeader"></h4>
                    <p id="NotificationBody"></p>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- Specification Modal -->
<div class="modal fade" id="specificationModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

        </div>
    </div>
</div>

<!-- Ratecard Modal -->
<div class="modal fade" id="ratecardModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

        </div>
    </div>
</div>

<!-- City, Enquiry and Rooms selection modal -->

<div id="CityModel" class="modal fade" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title no-capitalize" id="HeadTitle">Select City, Enquiry and Rooms</h4>
            </div>
            <div class="modal-body">
                <form action="" method="POST" accept-charset="utf-8" id="EstimationPopupForm">
                    <div class="row CityEnquiry">                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="EnquiryPopUp">Enquiry*</label>
                                <select name="EnquiryPopUp" id="EnquiryPopUp" class="form-control">
                                    <option value="">Select an Enquiry</option>
                                    @foreach ($UserEnquiries as $Enquiry)
                                    <option value="{{$Enquiry->Id}}">{{ $Enquiry->ReferenceNumber.' ('.$Enquiry->Name.')' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 city hidden">
                            <div class="form-group">
                                <label for="CityPopUp">City*</label>
                                <select name="CityPopUp" id="CityPopUp" class="form-control" disabled="disabled">
                                    <option value="">Select a City</option>
                                    @foreach ($ActiveCities as $ActiveCity)
                                    <option value="{{$ActiveCity->City->Id}}">{{$ActiveCity->City->Name}}</option>
                                    @endforeach
                                </select>                               
                            </div>
                        </div>
                    </div>
                    <div class="row hidden" id="RoomsTags">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="PopupRoomss">Rooms*</label>
                                <span class="mr-lt-20" id="RoomsTagCnt"></span>
                                <div class="help-block">
                                    <span id="helpBlock">Please select the room for which you want to do interiors.</span>
                                    <span class="pull-right">
                                        <i class="fa fa-square text-blue indicates">  </i> Indicates Selected
                                    </span>
                                </div>
                                <div id="Roomtags">
                                    @foreach($Rooms as $Key => $Room)
                                    <div class="room">
                                        <input type='checkbox' name="checkboxes[]" value="{{ $Room->Id }}" class="RoomsData hidden" id="{{ $Room->Id }}">
                                        <label for="{{ $Room->Id }}"><span class="badge RoomBadges">{{ $Room->Name }}</span></label>
                                    </div>
                                    @endforeach
                                </div>
                                <div id="RoomErrorMessage" class="has-error"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">                                
                                <input type="submit" value="Next" class="btn btn-primary button-custom" id="EstimationPopupFormSubmit" />
                                <input type="reset" value="Clear" class="btn button-custom" id="EstimationPopupFormReset" />
                                <input type="button" value="Cancel" class="btn button-custom hidden" id="EstimationPopupFormCancel" />
                                <a href="{{ auth()->user()->isManager()? URL::route("search.quickestimates"):URL::route('quickestimates.list') }}" target="_self" class="mr-tp-12 pull-right">Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="form-overlay hidden" id="EstimationPopupFormOverlay">
                <div class="loader-text">Fetching Items...</div>
            </div>
        </div>
    </div>
</div>
<!-- Rooms selection modal in Edit-->

<div id="UpdateRoomModel" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title no-capitalize" id="HeadTitle">Change Rooms</h4>
            </div>
            <div class="modal-body">
                <div class="row" id="UpdateRoomsTags">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="PopupRoomss">Rooms* </label>
                            <span class="mr-lt-20" id="RoomsUpdateTagCnt"></span>
                            <div class="help-block">
                                <span id="helpBlock">Please select the room for which you want to do interiors.</span>
                                <span class="pull-right">
                                    <i class="fa fa-square text-blue indicates">  </i> Indicates Selected
                                </span>
                            </div>
                            <div id="Roomtags">
                                @foreach($Rooms as $Key => $Room)
                                <div class="updateroom">
                                    <input type='checkbox' value="{{ $Room->Id }}" name="UpdateCheckboxes[]" class="RoomsChange hidden" id="edit-{{ $Room->Id }}">
                                    <label for="edit-{{ $Room->Id }}"><span class="badge RoomBadges" id="Names{{ $Room->Id }}">{{ $Room->Name }}</span></label>
                                </div>
                                @endforeach
                            </div>                            
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">                                
                            <input type="button" value="OK" class="btn btn-primary button-custom" data-dismiss="modal" id="OkBtn" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-overlay hidden" id="ChangeRoomPopupFormOverlay">
                <div class="loader-text">Fetching Items...</div>
            </div>
        </div>        
        <div class = "alert alert-success alert-dismissable hidden">
            <button type = "button" class = "close" data-dismiss = "alert" aria-hidden = "true">
                &times;
            </button>
            <div id="RoomStatusMessage" class="has-error"></div>
        </div>
    </div>
</div>
<!--Item's Image slide show popup-->
<div id="ImageLoaderModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">           
            <div class="form-overlay" id="slideshowLoader" style="min-height: 378px !important;">
                <div class="loader-text">Loading Reference Images...</div>
            </div>                 
        </div>
    </div>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl('/js/common.js') }}"></script>
<script src="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.full.min.js') }}"></script>
<script src="{{ URL::assetUrl('/plugins/multiselect/bootstrap-multiselect.js') }}"></script>
<script src="{{ URL::assetUrl('/js/magnific-popup.js') }}"></script>
<script src="{{ URL::assetUrl('/js/QuickEstimation/CreateQuickEstimation.js') }}"></script>
@endsection
