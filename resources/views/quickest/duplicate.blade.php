@extends('layouts/master_template')
@section('dynamicStyles')
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
        <span class="pd-5 user-info"> 
            @foreach ($ActiveCities as $ActiveCity)
            @if($ActiveCity->City->Id == $CurrentCityId)
            <i class="fa fa-globe text-info" aria-hidden="true"></i>&nbsp;{{$ActiveCity->City->Name}}
            @endif             
            @endforeach            
        </span>
    </div>
    <div class="col-md-12">
        <div class="box box-primary">
            <form id="QuickEstimationForm">
                <div class="box-header with-border">
                    <div class="row">
                        <div class="col-md-4 col-sm-3">
                            <div class="form-group">
                                <label for="">Estimation Name *</label>
                                <input type="text" name="QuickEstName" id="QuickEstName" class="form-control" placeholder="Ex: Estimation for Aparna Heights Flat in Gachibowli"/>
                            </div>
                        </div>                                      
                        <div class="col-md-3 col-sm-3">
                            <div class="form-group">
                                <?php
                                $EnquiryData = json_decode($CurrentEnquiry->JsonData, true);
                                $EnquiryInfo = " <b> " . $EnquiryData['step01']['ProjectName'] . " </b><br> ";
                                $EnquiryInfo .= $EnquiryData['step01']['BuilderName'] . " <br> ";
                                $EnquiryInfo .= $enquirySiteAddress;
                                ?>
                                <label for="">Enquiry *</label>
                                <select name="Enquiry" class="form-control Enquiry select2" id="EnquiryId">
                                    @foreach ($UserEnquiries as $Enquiry)
                                    @if($enquiryId == $Enquiry->Id)
                                    <option value='{{$Enquiry->Id}}' selected="selected">{{ $Enquiry->ReferenceNumber.' ('.$Enquiry->Name.')' }}</option>
                                    @else
                                    <option value='{{$Enquiry->Id}}'>{{ $Enquiry->ReferenceNumber.' ('.$Enquiry->Name.')' }}</option>
                                    @endif
                                    @endforeach
                                </select> 
                                <span class="pull-right EnquiryInfoSticky"><a href="#" data-container="body" data-toggle="popover" data-placement="bottom" data-popover="true" data-content="{!! $EnquiryInfo !!}" data-html="true" class="Info"><i class="fa fa-info" aria-hidden="true"></i></a></span>
                            </div>
                        </div>                    
                        <div class="col-md-3 col-sm-3">
                            <div class="form-group">
                                <label for="Rooms">Rooms*</label>                            
                                <p class="form-control-static "><span class="RoomsCount">{{sizeof($enquiryRooms)== 0 ? 'No' : sizeof($enquiryRooms)}} Rooms Selected</span>
                                    &nbsp;&nbsp;<a href="#" data-container="body" data-toggle="popover" data-placement="bottom" data-popover="true" class="RoomsView">Change</a>
                                </p>
                                <input name="Rooms[]" type="hidden" id="QERooms" value="" >
                                <input type="hidden" name="SiteAddress" id="SiteAddress" class="form-control" value="{{ $enquirySiteAddress }}" />
                                <input type="hidden" name="City" id="City" class="form-control" value="{{ $CurrentCityId }}" />
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
                                                <input type="text" name="StickyQuickEstName" id="StickyQuickEstName" class="form-control Details" placeholder="Ex: Estimation for Aparna Heights Flat in Gachibowli"/>
                                            </div>
                                        </div>                                        
                                        <!-- /.col -->                                        
                                        <div class="col-md-4 col-sm-4">
                                            <div class="form-group">
                                                <label for="" class="pull-left">Enquiry</label>
                                                <select name="Enquiry" class="form-control Enquiry Details" id="StickyEnquiryId">
                                                    @foreach ($UserEnquiries as $Enquiry)
                                                    @if($enquiryId == $Enquiry->Id)
                                                    <option value='{{$Enquiry->Id}}' selected="selected">{{ $Enquiry->ReferenceNumber.' ('.$Enquiry->Name.')' }}</option>
                                                    @else
                                                    <option value='{{$Enquiry->Id}}'>{{ $Enquiry->ReferenceNumber.' ('.$Enquiry->Name.')' }}</option>
                                                    @endif
                                                    @endforeach
                                                </select>
                                                <span class="pull-right EnquiryInfoSticky"><a href="#" data-container="body" data-toggle="popover" data-placement="bottom" data-popover="true" data-content="{!! $EnquiryInfo !!}" data-html="true" class="Info"><i class="fa fa-info" aria-hidden="true"></i></a></span>
                                            </div>
                                        </div>
                                        <!-- /.col -->
                                        <div class="col-md-4 col-sm-4">
                                            <div class="form-group">
                                                <label for="Rooms" class="pull-left">Rooms</label><br>                                               
                                                <p class="form-control-static mr-tp-3"><span class="pull-left Details RoomsCount">{{sizeof($enquiryRooms)== 0 ? 'No' : sizeof($enquiryRooms)}} Rooms Selected</span>
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
                                <th class="text-center brand-bg-color amount-text text-vertical-align">
                                    <i class="fa fa-rupee"></i> 
                                    <span class="SumAmount1">0.00</span><br/>
                                    {{ $PricePackages[0]['Name'] }}
                                </th>
                                <th class="text-center hechpe-bg-color amount-text text-vertical-align">
                                    <i class="fa fa-rupee"></i> 
                                    <span class="SumAmount2">0.00</span><br/>
                                    {{ $PricePackages[1]['Name'] }}
                                </th>
                                <th class="text-center market-bg-color amount-text text-vertical-align">
                                    <i class="fa fa-rupee"></i> 
                                    <span class="SumAmount3">0.00</span><br/>
                                    {{ $PricePackages[2]['Name'] }}
                                </th>
                                <th class="text-center bg-white text-vertical-align speciciations" rowspan="0.5" >
                                    <a href="{{URL::route('quickestimate.specifications')}}" title="" data-toggle="modal" data-target="#specificationModal">Specifications</a>
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
                                <th class="text-center brand-bg-color amount-text text-vertical-align">
                                    <i class="fa fa-rupee"></i> 
                                    <span class="SumAmount1">0.00</span><br/>
                                    {{ $PricePackages[0]['Name'] }}
                                </th>
                                <th class="text-center hechpe-bg-color amount-text text-vertical-align">
                                    <i class="fa fa-rupee"></i> 
                                    <span class="SumAmount2">0.00</span><br/>
                                    {{ $PricePackages[1]['Name'] }}
                                </th>
                                <th class="text-center market-bg-color amount-text text-vertical-align">
                                    <i class="fa fa-rupee"></i> 
                                    <span class="SumAmount3">0.00</span><br/>
                                    {{ $PricePackages[2]['Name'] }}
                                </th>                                                       
                                <th class="text-center text-vertical-align speciciations" >
                                    <a href="{{URL::route('quickestimate.specifications')}}" title="" data-toggle="modal" data-target="#specificationModal">Specifications</a>
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
                                <th class="text-center cursor-help" width="5%" title="Height/Length of the Item, if applicable">H/L</th>
                                <th class="text-center cursor-help" width="5%" title="Depth of the Item, if applicable">D</th>
                                <th class="text-center brand-bg-color" width="10%">Amount</th>
                                <th class="text-center hechpe-bg-color" width="10%">Amount</th>
                                <th class="text-center market-bg-color" width="10%">Amount</th>
                                <th class="text-center" width="10%">Notes</th>
                            </tr>
                            @if(!empty($Rooms))
                            <?php $Index = 1; ?>
                            @foreach ($Rooms as $Room)
                            @if(!in_array($Room['Id'],$DefaultRooms))
                            <tr class="bg-info text-center room-items">
                                <td width="4%"></td>
                                <td id="{{$Room['Id']}}" class="rooms"><span class="pull-left pd-lt-10">{{$Room['Name']}}</span></td>
                                <td width="26%" colspan="6"><span class="pull-right mr-rt-10">Section Subtotal &nbsp;<i class="fa fa-arrow-right smallarrow" aria-hidden="true"></i></span></td>
                                <td width="10%" class="brand-bg-color"></td>
                                <td width="10%" class="hechpe-bg-color"></td>
                                <td width="10%" class="market-bg-color"></td>
                                <td width="10%"></td>
                            </tr>
                            @foreach ($Room['EstimationItems'] as $Key => $EstimationItem)
                            <tr>
                                <td class="text-center text-vertical-align items-index" width="4%">{{$Index++}}</td>
                                <td class="text-vertical-align" width="30%">
                                    <input type="hidden" name="{{ $EstimationItem["Id"] }}-RoomId" value="{{ $Room["Id"] }}"/>
                                    {{ $EstimationItem['Description'] }}
                                    @if(!empty($EstimationItem['Comment']))
                                    <span class="text-aqua comments-tooltip" data-toggle="tooltip" title="{{$EstimationItem['Comment']}}"><i class="fa fa-info-circle text-light-blue" aria-hidden="true"></i></span>
                                    @endif
                                    @if(!empty($EstimationItem['ItemNote']))
                                    <span class="text-danger notes-tooltip" data-toggle="tooltip" title="{{$EstimationItem['ItemNote']}}"><i class="fa fa-exclamation-triangle text-black" aria-hidden="true"></i></span>
                                    @endif
                                    @if($EstimationItem['Image'] !== 'false')
                                    <span class="text-danger CursorPointer PopOver" data-toggle="popover" data-placement="right" data-content="<div class='media'><a href='#'><img src='{{$EstimationItem['Image']}}' class='media-object img-responsive' alt='Sample Image'></a></div>"><i class="fa fa-image text-black" aria-hidden="true"></i></span>
                                    @endif
                                </td>
                                <td class="text-center text-vertical-align" width="3%">
                                    @if(!empty($EstimationItem['PaymentBy']))
                                    <span class="comments-tooltip" data-toggle="tooltip" title="{{ $EstimationItem['PaymentBy']['Description'] }}">
                                        <img src="{{ URL::CDN($EstimationItem['PaymentBy']['ImagePath'])}}" alt="{{ $EstimationItem['PaymentBy']['ShortCode'] }}">
                                    </span>
                                    @endif
                                </td>
                                <td class="text-center text-vertical-align" width="3%">
                                    @if(isset($EstimationItem["IsEditable"]))
                                        <input type="checkbox" name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Required" id="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Required" {{ ($EstimationItem['IsPreSelected'] == 1 && $EstimationItem['IsDeselectable'] == 0) ? 'disabled' : '' }} class="checkbox" data-room="{{$Room['Id']}}" checked/>
                                    @else
                                        <input type="checkbox" name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Required" id="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Required" {{ ($EstimationItem['IsPreSelected'] == 1 && $EstimationItem['IsDeselectable'] == 0) ? 'checked disabled' : '' }} class="checkbox" data-room="{{$Room['Id']}}"/>
                                    @endif
                                    <label for="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Required" tabindex="0"></label>
                                </td>
                                <td class="text-center text-vertical-align" width="5%">
                                    <input type="text" name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Quantity" id="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Quantity" class="form-control input-sm text-center" value="{{ $EstimationItem['DefaultQuantity'] }}" {{($EstimationItem['IsQuantityEditable'] == 1) ? '' : 'disabled'}} data-roomid="{{$Room['Id']}}"/>
                                </td>
                                <td class="text-center text-vertical-align" width="5%">
                                    <input type="text" name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Width" id="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Width" class="form-control input-sm text-center" value="{{$EstimationItem['Width']}}" {{($EstimationItem['IsDimEditable'] == 1) ? '' : 'disabled'}} data-roomid="{{$Room['Id']}}"/>
                                </td>
                                <td class="text-center text-vertical-align" width="5%">
                                    <input type="text" name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Height" id="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Height" class="form-control input-sm text-center" value="{{$EstimationItem['Height']}}" {{($EstimationItem['IsDimEditable'] == 1) ? '' : 'disabled'}} data-roomid="{{$Room['Id']}}"/>
                                </td>
                                <td class="text-center text-vertical-align" width="5%">{{$EstimationItem['Depth']}}</td>
                                @foreach ($PricePackages as $PricePackage)
                                <td class="text-center text-vertical-align item-rates" width="10%">
                                    {{$EstimationItem['PricePackage'][$PricePackage['Id']]['CustomerRate']}}
                                </td>
                                @endforeach
                                <td class="text-center" width="10%">
                                    <textarea name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Notes" rows="1" class="form-control input-sm user-notes" style="resize:none" placeholder="Notes">{{ $EstimationItem['Note'] }}</textarea>
                                </td>
                            </tr>
                            @endforeach
                            @endif
                            @endforeach
                            @foreach ($Rooms as $Room)
                            @if(in_array($Room['Id'],$DefaultRooms))
                            <tr class="bg-info text-center room-items">
                                <td width="4%"></td>
                                <td width="30%" id="{{$Room['Id']}}" class="rooms"><span class="pull-left pd-lt-10">{{$Room['Name']}}</span></td>
                                <td width="26%" colspan="6"><span class="pull-right mr-rt-10">Section Subtotal&nbsp;<i class="fa fa-arrow-right smallarrow" aria-hidden="true"></i></span></td>
                                <td width="10%" class="brand-bg-color"></td>
                                <td width="10%" class="hechpe-bg-color"></td>
                                <td width="10%" class="market-bg-color"></td>
                                <td width="10%"></td>
                            </tr>
                            @foreach ($Room['EstimationItems'] as $Key => $EstimationItem)
                            <tr>
                                <td class="text-center text-vertical-align items-index" width="4%">{{$Index++}}</td>
                                <td class="text-vertical-align" width="30%">{{ $EstimationItem['Description'] }}
                                    @if(!empty($EstimationItem['Comment']))
                                    <span class="text-aqua comments-tooltip" data-toggle="tooltip" title="{{$EstimationItem['Comment']}}"><i class="fa fa-info-circle text-light-blue" aria-hidden="true"></i></span>
                                    @endif
                                    @if(!empty($EstimationItem['ItemNote']))
                                    <span class="text-danger notes-tooltip" data-toggle="tooltip" title="{{$EstimationItem['ItemNote']}}"><i class="fa fa-exclamation-triangle text-black" aria-hidden="true"></i></span>
                                    @endif
                                    @if($EstimationItem['Image'] !== 'false')
                                    <span class="text-danger CursorPointer PopOver" data-toggle="popover" data-placement="right"  data-html="true" data-content="<div class='media'><a href='#'><img src='{{$EstimationItem['Image']}}' class='media-object img-responsive' alt='Sample Image'></a></div>"><i class="fa fa-image text-black" aria-hidden="true"></i></span>
                                    @endif
                                </td>
                                <td class="text-center text-vertical-align" width="3%">
                                    @if(!empty($EstimationItem['PaymentBy']))
                                    <span class="comments-tooltip" data-toggle="tooltip" title="{{ $EstimationItem['PaymentBy']['Description'] }}">
                                        <img src="{{ URL::CDN($EstimationItem['PaymentBy']['ImagePath'])}}" alt="{{ $EstimationItem['PaymentBy']['ShortCode'] }}">
                                    </span>
                                    @endif
                                </td>
                                <td class="text-center text-vertical-align" width="3%">
                                    @if(isset($EstimationItem["IsEditable"]))
                                        <input type="checkbox" name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Required" id="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Required" {{ ($EstimationItem['IsPreSelected'] == 1 && $EstimationItem['IsDeselectable'] == 0) ? 'disabled' : '' }} class="checkbox" data-room="{{$Room['Id']}}" checked/>
                                    @else
                                        <input type="checkbox" name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Required" id="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Required" {{ ($EstimationItem['IsPreSelected'] == 1 && $EstimationItem['IsDeselectable'] == 0) ? 'checked disabled' : '' }} class="checkbox" data-room="{{$Room['Id']}}"/>
                                    @endif
                                    <label for="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Required" tabindex="0"></label>
                                </td>
                                <td class="text-center text-vertical-align" width="5%">
                                    <input type="text" name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Quantity" id="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Quantity" class="form-control input-sm text-center" value="{{ $EstimationItem['DefaultQuantity'] }}" {{($EstimationItem['IsQuantityEditable'] == 1) ? '' : 'disabled'}} data-roomid="{{$Room['Id']}}"/>
                                </td>
                                <td class="text-center text-vertical-align" width="5%">
                                    <input type="text" name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Width" id="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Width" class="form-control input-sm text-center" value="{{$EstimationItem['Width']}}" {{($EstimationItem['IsDimEditable'] == 1) ? '' : 'disabled'}} data-roomid="{{$Room['Id']}}"/>
                                </td>
                                <td class="text-center text-vertical-align" width="5%">
                                    <input type="text" name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Height" id="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Height" class="form-control input-sm text-center" value="{{$EstimationItem['Height']}}" {{($EstimationItem['IsDimEditable'] == 1) ? '' : 'disabled'}} data-roomid="{{$Room['Id']}}"/>
                                </td>
                                <td class="text-center text-vertical-align" width="5%">{{$EstimationItem['Depth']}}</td>
                                @foreach ($PricePackages as $PricePackage)
                                    <td class="text-center text-vertical-align item-rates" width="10%">
                                        {{$EstimationItem['PricePackage'][$PricePackage['Id']]['CustomerRate']}}
                                    </td>
                                @endforeach
                                <td class="text-center" width="10%">
                                    <textarea name="{{$EstimationItem['Id']}}-{{ $Room['Id'] }}-Notes" rows="1" class="form-control input-sm user-notes" style="resize:none" placeholder="Notes">{{ $EstimationItem['Note'] }}</textarea>
                                </td>
                            </tr>
                            @endforeach
                            @endif
                            @endforeach
                            @else
                            <tr>
                                <td colspan="12" class="text-center">There are no items available for this City.</td>
                            </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr class="bg-blue">
                                <th colspan="8" class="text-right text-vertical-align"><span class="pull-right mr-rt-10">Total</span></th>
                                <th class="text-center brand-bg-color amount-text text-vertical-align">
                                    <i class="fa fa-rupee"></i> 
                                    <span class="SumAmount1">0.00</span><br/>
                                </th>
                                <th class="text-center hechpe-bg-color amount-text text-vertical-align">
                                    <i class="fa fa-rupee"></i> 
                                    <span class="SumAmount2">0.00</span><br/>
                                </th>
                                <th class="text-center market-bg-color amount-text text-vertical-align">
                                    <i class="fa fa-rupee"></i> 
                                    <span class="SumAmount3">0.00</span><br/>
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                    <p class="text-center pd-8 footerbuttons" id="Bthfooter">
                        <input type="reset" id="QuickEstimationFormReset" class="btn button-custom" value="Clear" />
                        <input type="submit" id="QuickEstimationFormSubmit" class="btn btn-primary button-custom" value="Submit" />
                        <input type="button" id="QuickEstimationGoToTop" class="btn pull-right" value="Top" />
                    </p>
                </div>
                <input type="hidden" name="EncUserId" value="{{ $EncUserId }}">
                <input type="hidden" name="RefNo" id="RefNo" value="{{ isset($RefNo) ? $RefNo : '' }}">
                <div id="NotificationArea"></div>
                <div class="form-loader hidden" id="QuickEstimationFormLoader">
                    <div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>
                </div>
                @include('quickest.Notes')
            </form>
            <div class="box-header hidden" id="CalloutNotification">
                <div class="callout">
                    <h4 id="NotificationHeader"></h4>
                    <p id="NotificationBody"></p>
                </div>
            </div>
        </div>
    </div>
</div>
<!--- specificationModal--->
<div class="modal fade" id="specificationModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content"></div>
    </div>
</div>

<!--- ratecardModal--->
<div class="modal fade" id="ratecardModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content"></div>
    </div>
</div>

<div class="modal fade" id="ConfirmCityChangeModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title no-text-transform">Confirm change?</h4>
            </div>
            <div class="modal-body">Are you sure that you would like to change the City? If you change it, Quick Estimation rates will change according to the selected City.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="ConfirmCityChange">Confirm</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ConfirmEnquiryChangeModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title no-text-transform">Confirm change?</h4>
            </div>
            <div class="modal-body">Are you sure that you would like to change the Enquiry? If you change it, Quick Estimation rates will change according to the selected Enquiry.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-custom" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="ConfirmEnquiryChange">Confirm</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ConfirmEstimationRateModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title no-text-transform">Information</h4>
            </div>
            <div class="modal-body">The estimate generated using the existing quick estimate will have rates as per the rates applicable on the day on which the new quick estimate is generated using the 'Copy as New' option.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="ConfirmRates">OK</button>
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
                            <span class="mr-lt-10" id="RoomsUpdateTagCnt"></span>
                            <div class="help-block">
                                <span id="helpBlock">Please select the room for which you want to do interiors.</span>
                                <span class="pull-right">
                                    <i class="fa fa-square text-blue indicates">  </i> Indicates Selected
                                </span>
                            </div>
                            <div id="Roomtags">
                                @foreach($allRooms as $Key => $Room)                                
                                <div class="updateroom">
                                    @if(in_array($Room->Id, $enquiryRooms))
                                    <input type='checkbox' value="{{ $Room->Id }}" name="UpdateCheckboxes[]" class="RoomsChange hidden" id="edit-{{ $Room->Id }}" checked="true">
                                    <label for="edit-{{ $Room->Id }}"><span class="badge bg-darkgray RoomBadges" id="Names{{ $Room->Id }}">{{ $Room->Name }}</span></label>
                                    @else
                                    <input type='checkbox' value="{{ $Room->Id }}" name="UpdateCheckboxes[]" class="RoomsChange hidden" id="edit-{{ $Room->Id }}">
                                    <label for="edit-{{ $Room->Id }}"><span class="badge RoomBadges" id="Names{{ $Room->Id }}">{{ $Room->Name }}</span></label>
                                    @endif
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
        <div id="NotificationBox">
            <div class = "alert alert-success alert-dismissable hidden">
                <button type = "button" class = "close" data-dismiss = "alert" aria-hidden = "true">
                    &times;
                </button>
                <div id="RoomStatusMessage" class="has-error"></div>
            </div>
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
<script src="{{asset('/js/common.js')}}"></script>
<script src="{{ URL::assetUrl('/AdminLTE/plugins/select2/select2.full.min.js') }}"></script>
<script src="{{ URL::assetUrl('/plugins/multiselect/bootstrap-multiselect.js') }}"></script>
<script src="{{ URL::assetUrl('/js/magnific-popup.js') }}"></script>
<script src="{{asset('/js/QuickEstimation/duplicate.js')}}"></script>
@endsection
