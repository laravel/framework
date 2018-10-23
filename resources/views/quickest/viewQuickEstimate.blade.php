@extends('layouts/master_template')

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
            <i class="fa fa-globe text-info" aria-hidden="true"></i>&nbsp;{{ $QuickEstDeatils['City'] }}
        </span>
    </div>
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <div class="row">
                    <div class="col-md-2 col-sm-2">
                        <div class="form-group">
                            <label for="">QEC Ref No</label>
                            <p>{{ $QuickEstDeatils['ReferenceNumber'] }}</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-3">
                        <div class="form-group">
                            <label for="">Estimation Name</label>
                            <p class="text-capitalize">{{ $QuickEstDeatils['Name'] }}</p>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4">
                        <div class="form-group">
                            <?php
                            if (isset($Enquiry)) {
                                $EnquiryData = json_decode($Enquiry->JsonData, true);
                                $EnquiryInfo = " <b> " . $EnquiryData['step01']['ProjectName'] . " </b><br> ";
                                $EnquiryInfo .= $EnquiryData['step01']['BuilderName'] . " <br> ";
                                $EnquiryInfo .= $QuickEstDeatils['SiteAddress'];
                            } else {
                                $EnquiryInfo = $QuickEstDeatils['SiteAddress'];
                            }
                            ?>
                            <label for="">Enquiry</label>                            
                            <p class="text-capitalize">{{ $Enquiry->ReferenceNumber.' ('.$Enquiry->Name.')' }} <a href="#" data-container="body" data-toggle="popover" data-placement="bottom" data-html="true" data-popover="true" data-content="{!! $EnquiryInfo !!}" class="Info"><i class="fa fa-info" aria-hidden="true"></i></a></p>
                        </div>
                    </div> 
                    <div class="col-md-3 col-sm-3 text-right">
                        <div class="form-group mr-tp-10">
                            <a class="btn btn-primary" href="{{URL::route('quickestimate.statistics',
                                        ['quickestrefno'=>$QuickEstDeatils['ReferenceNumber']])}}">
                                Statistics
                            </a> 
                        </div>
                    </div>
                </div>                
            </div>
            @if(!empty($SumAmount))
            <div class="box-body pd-0 table-responsive">                
                <table class="table table-bordered" id="QuickEstimationTable">
                    <thead id="QuickEstimationTableHeader">
                        <tr>
                            <th colspan="5" class="caution">
                               <i class="fa fa-info-circle text-light-blue" aria-hidden="true"></i> 
                                        <i class="fa fa-exclamation-triangle text-black" aria-hidden="true"></i>&nbsp; 
                                        <span class="text-center no-text-transform">Indicates comments</span>&nbsp;|&nbsp;
                                        <i class="fa fa-image text-black" aria-hidden="true"></i>&nbsp; 
                                        <span class="text-center no-text-transform">Indicates reference images</span>&nbsp;|&nbsp;
                                        <span class="text-center no-text-transform">All dimensions in feet&nbsp;|&nbsp;All amount in Indian Rupees ( <i class="fa fa-rupee"></i> ) </span>
                            </th>
                            <th class="text-center brand-bg-color text-vertical-align amount-text pd-5">
                                <i class="fa fa-rupee"></i> 
                                <span class="SumAmount1">{{ money_format('%!.0n', $SumAmount['Total'][$PricePackages[0]['Id']]) }}</span><br/>
                                {{ $PricePackages[0]['Name'] }}
                            </th>
                            <th class="text-center hechpe-bg-color text-vertical-align amount-text pd-5">
                                <i class="fa fa-rupee"></i> 
                                <span class="SumAmount2">{{ money_format('%!.0n', $SumAmount['Total'][$PricePackages[1]['Id']]) }}</span><br/>
                                {{ $PricePackages[1]['Name'] }}
                            </th>
                            <th class="text-center market-bg-color text-vertical-align amount-text pd-5">
                                <i class="fa fa-rupee"></i> 
                                <span class="SumAmount3">{{ money_format('%!.0n', $SumAmount['Total'][$PricePackages[2]['Id']]) }}</span><br/>
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
                            <th class="text-center" width="3%">#</th>
                            <th class="text-center" width="30%">Items</th>
                            <th class="text-center" width="2%">Pay</th>
                            <th class="text-center header-tooltip" data-toggle="tooltip" title="Required Quantity, if applicable" width="3%">Nos</th>
                            <th class="text-center header-tooltip" data-toggle="tooltip" title="Width X Height/Length X Depth, if applicable" width="10%">W <small>x</small> H/L <small>x</small> D</th>
                            <th class="text-center brand-bg-color" width="11%">Amount</th>
                            <th class="text-center hechpe-bg-color" width="11%">Amount</th>
                            <th class="text-center market-bg-color" width="11%">Amount</th>
                            <th class="text-center" width="19%">Notes</th>
                        </tr>
                        @if(!empty($Rooms))
                        <?php $Cnt = 1; ?>
                        @foreach ($Rooms as $Room)
                        <tr class="bg-info text-center room-items">
                            <td width="4%"></td>
                            <td class="rooms"><span class="pull-left pd-lt-10">{{$Room['Name']}}</span></td>
                            <td width="26%" colspan="3"><span class="pull-right mr-rt-10">Section Subtotal &nbsp;<i class="fa fa-arrow-right smallarrow" aria-hidden="true"></i></span></td>
                            <td width="10%" class="brand-bg-color text-vertical-align">
                                <i class="fa fa-rupee"></i>
                                {{ money_format('%!.0n', $SumAmount['Room'][$Room['Id']][$PricePackages[0]['Id']]) }}
                            </td>
                            <td width="10%" class="hechpe-bg-color text-vertical-align">
                                <i class="fa fa-rupee"></i>
                                {{ money_format('%!.0n', $SumAmount['Room'][$Room['Id']][$PricePackages[1]['Id']]) }}
                            </td>
                            <td width="10%" class="market-bg-color text-vertical-align">
                                <i class="fa fa-rupee"></i>
                                {{ money_format('%!.0n', $SumAmount['Room'][$Room['Id']][$PricePackages[2]['Id']]) }}
                            </td>
                            <td width="19%"></td>
                        </tr>
                        @foreach ($Room['EstimationItems'] as $Key => $EstimationItem)
                        <tr>
                            <td class="text-center text-vertical-align" width="3%">{{ $Cnt++ }}</td>
                            <td class="text-vertical-align" width="30%">{{ $EstimationItem['Description'] }}
                                @if(!empty($EstimationItem['Comment']))
                                <span class="text-aqua comments-tooltip" data-toggle="tooltip" title="{{ $EstimationItem['Comment'] }}"><i class="fa fa-info-circle text-light-blue" aria-hidden="true"></i></span>
                                @endif
                                @if(!empty($EstimationItem['Note']))
                                <span class="text-danger notes-tooltip" data-toggle="tooltip" title="{{ $EstimationItem['Note'] }}"><i class="fa fa-exclamation-triangle text-black" aria-hidden="true"></i></span>
                                @endif
                                @if($EstimationItem['Image'] !== 'false')
                                <span class="text-danger CursorPointer PopOver" data-toggle="popover" data-placement="right"  data-html="true" data-content="<div class='media'><a href='#'><img src='{{$EstimationItem['Image']}}' class='media-object img-responsive' alt='Sample Image'></a></div>"><i class="fa fa-image text-black" aria-hidden="true"></i></span>
                                @endif
                            </td>
                            <td class="text-center text-vertical-align" width="2%">
                                @if(!empty($EstimationItem['PaymentBy']))
                                <span class="comments-tooltip" data-toggle="tooltip" title="{{ $EstimationItem['PaymentBy']['Description'] }}">
                                    <img src="{{ URL::CDN($EstimationItem['PaymentBy']['ImagePath'])}}" alt="{{ $EstimationItem['PaymentBy']['ShortCode'] }}">
                                </span>
                                @endif
                            </td>
                            <td class="text-center text-vertical-align" width="3%">
                                {{ $EstimationItem['Quantity'] }}
                            </td>
                            <td class="text-center text-vertical-align" width="10%">
                                {{ $EstimationItem['Width'] }}x{{ $EstimationItem['Height'] }}x{{ $EstimationItem['Depth'] }}
                            </td>
                            @foreach ($PricePackages as $PricePackage)
                            <td class="text-center text-vertical-align item-rates" width="11%">
                                {{ $EstimationItem['PricePackage'][$PricePackage['Id']]['Amount'] }}
                            </td>
                            @endforeach
                            <td class="text-center text-vertical-align" width="19%" style="word-break:break-all">
                                {{ $EstimationItem['UserNote'] }}
                            </td>
                        </tr>
                        @endforeach
                        @endforeach
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="bg-blue">
                            <th colspan="5" class="text-righ text-vertical-alignt"><span class="pull-right mr-rt-10">Total</span></th>
                            <th class="text-center brand-bg-color amount-text pd-5">
                                <i class="fa fa-rupee"></i> 
                                <span class="SumAmount1">{{ money_format('%!.0n', $SumAmount['Total'][$PricePackages[0]['Id']]) }}</span><br/>
                            </th>
                            <th class="text-center hechpe-bg-color amount-text pd-5 text-vertical-align">
                                <i class="fa fa-rupee"></i> 
                                <span class="SumAmount2">{{ money_format('%!.0n', $SumAmount['Total'][$PricePackages[1]['Id']]) }}</span><br/>
                            </th>
                            <th class="text-center market-bg-color amount-text pd-5 text-vertical-align">
                                <i class="fa fa-rupee"></i> 
                                <span class="SumAmount3">{{ money_format('%!.0n', $SumAmount['Total'][$PricePackages[2]['Id']]) }}</span><br/>
                            </th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
            <div class="row text-center"><b>No items found</b></div>
            @endif
            @include('quickest.Notes')
        </div>
        <div id="NotificationArea"></div>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="specificationModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
        </div>
    </div>
</div>
<!-----Ratecard Model ----->
<div class="modal fade" id="ratecardModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

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
@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl('/css/magnific-popup.css') }}">
<link rel="stylesheet" href="{{ URL::assetUrl('/css/quickestimate/view.css') }}">
@endsection
@section('dynamicScripts')
<script src="{{ URL::assetUrl('/js/magnific-popup.js') }}"></script>
<script src="{{ URL::assetUrl('/js/common.js') }}"></script>
<script src="{{ URL::assetUrl('/js/QuickEstimation/ViewQuickEstimation.js') }}"></script>
@endsection
