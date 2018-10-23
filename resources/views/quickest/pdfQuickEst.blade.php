@extends('layouts/Pdfs/PDFTemplate')
@section('content')
    <div class="box box-primary">
        <div class="box-header with-border text-center pd-5"><b>Material Specification</b></div>
            <div class="box-body pd-0">
                <div class="row" style="page-break-after: always;">
                    <div class="col-xs-12">
                         @include('quickest.statistics.specifications')
                    </div>
                </div>
                <div class="row"  style="page-break-after: always;">
                    <div class="col-xs-12">
                        @include('quickest.statistics.pdfQuickEstStatistics')
                    </div>
                </div>
                @if(!empty($SumAmount))
                <div class="box-header with-border text-center pd-5"><b>Estimate Details</b></div>
                <?php
              $PayTypes = App\Http\Models\PaymentBy::where('IsActive', 1)->get(['ShortCode', 'ImagePath', 'Description'])
              ?>
                <table class="table table-bordered">
                    <thead>
                        <tr style="page-break-inside: avoid !important;">
                            <th colspan="9" style="font-size: 12px !important;font-weight: 400 !important;">
                                <span class="text-center no-text-transform">
                                    All dimensions in feet | All amount in Indian Rupees (&#8377;) |
                                    @foreach ($PayTypes as $PayType)
                                    <span>
                                        <img class="mr-lt-4" style="height:19px; width:25px;" src="{{ URL::CDN($PayType['ImagePath']) }}" alt="{{ URL::CDN($PayType['ShortCode']) }}"> - {{ $PayType['Description'] }}
                                    </span>
                                    @endforeach
                                </span>
                            </th>                            
                        </tr>
                        <tr>
                            <th colspan="5" class="text-vertical-align pd-2" style="font-size:12px;">
                                <p class="text-right mr-0" >Total &#8594;</p>
                                <p class="text-right mr-0">Specification &#8594;</p>
                            </th>
                            <th class="text-center brand-bg-color amount-text text-vertical-align pd-2">
                                <i class="fa fa-rupee"></i> 
                                <span class="SumAmount1">&#8377;{{ money_format('%!.0n', $SumAmount['Total'][$PricePackages[0]['Id']]) }}</span><br/>
                                {{ $PricePackages[0]['Name'] }}
                            </th>
                            <th class="text-center hechpe-bg-color amount-text text-vertical-align pd-2">
                                <i class="fa fa-rupee"></i> 
                                <span class="SumAmount2">&#8377;{{ money_format('%!.0n', $SumAmount['Total'][$PricePackages[1]['Id']]) }}</span><br/>
                                {{ $PricePackages[1]['Name'] }}
                            </th>
                            <th class="text-center market-bg-color amount-text text-vertical-align pd-2">
                                <i class="fa fa-rupee"></i> 
                                <span class="SumAmount3">&#8377;{{ money_format('%!.0n', $SumAmount['Total'][$PricePackages[2]['Id']]) }}</span><br/>
                                {{ $PricePackages[2]['Name'] }}
                            </th>
                            <th></th>
                        </tr>
                        <tr class="bg-blue text-center pd-2 pdf-estimationtable-header" style="page-break-inside: avoid !important;">
                            <th class="text-center pd-2" width="3%">#</th>
                            <th class="text-center pd-2" width="26%">Items</th>
                            <th class="text-center pd-2" width="2%">Pay</th>
                            <th class="text-center pd-2" width="3%">Nos</th>
                            <th class="text-center pd-2" width="15%">W x H/L x D</th>
                            <th class="text-center brand-bg-color pd-2" width="11%">Amount</th>
                            <th class="text-center hechpe-bg-color pd-2" width="11%">Amount</th>
                            <th class="text-center market-bg-color pd-2" width="11%">Amount</th>
                            <th class="text-center pd-2" width="18%">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($Rooms))
                        <?php $Cnt = 1; ?>
                        @foreach ($Rooms as $Room)
                        <tr class="bg-info text-right pd-2 room-items" style="page-break-inside: avoid !important;">
                            <td colspan="5" class="text-capitalize"><span class="pull-left pd-lt-10">{{ $Room['Name'] }}</span><span class="pull-right mr-rt-10">Section Subtotal &#8594;</span></td>
                            <td width="11%" class="brand-bg-color text-vertical-align pd-2">
                                &#8377;{{ money_format('%!.0n', $SumAmount['Room'][$Room['Id']][$PricePackages[0]['Id']]) }}
                            </td>
                            <td width="11%" class="hechpe-bg-color text-vertical-align pd-2">
                                &#8377;{{ money_format('%!.0n', $SumAmount['Room'][$Room['Id']][$PricePackages[1]['Id']]) }}
                            </td>
                            <td width="11%" class="market-bg-color text-vertical-align pd-2">
                               &#8377;{{ money_format('%!.0n', $SumAmount['Room'][$Room['Id']][$PricePackages[2]['Id']]) }}
                            </td>
                            <td class="pd-2" width="18%"></td>
                        </tr>
                        @foreach ($Room['EstimationItems'] as $Key => $EstimationItem)
                        <tr style="page-break-inside: avoid !important;">
                            <td class="text-center text-vertical-align pd-2" width="3%">{{ $Cnt++ }}</td>
                            <td class="text-vertical-align pd-2" width="26%">
                                {{ $EstimationItem['Description'] }}
                            </td>
                            <td class="text-center text-vertical-align" width="2%">
                                @if(!empty($EstimationItem['PaymentBy']))
                                <span class="comments-tooltip" data-toggle="tooltip" title="{{ $EstimationItem['PaymentBy']['Description'] }}">
                                    <img src="{{ URL::CDN($EstimationItem['PaymentBy']['ImagePath'])}}" alt="{{ $EstimationItem['PaymentBy']['ShortCode'] }}">
                                </span>
                                @endif
                            </td>
                            <td class="pd-2 text-center text-vertical-align" width="3%">
                                {{ $EstimationItem['Quantity'] }}
                            </td>
                            <td class="pd-2 text-center text-vertical-align" width="15%" style="font-size: 12px;">
                                {{ $EstimationItem['Width'] }} x {{ $EstimationItem['Height'] }} x {{ $EstimationItem['Depth'] }}
                            </td>
                            @foreach ($PricePackages as $PricePackage)
                            <td class="pd-2 text-right text-vertical-align item-rates" width="11%">
                                {{ $EstimationItem['PricePackage'][$PricePackage['Id']]['Amount'] }}
                            </td>
                            @endforeach
                            <td class="pd-2 text-center text-vertical-align" width="18%" style="font-size:10px; word-break:break-all">
                                <p class="mr-0">{{ $EstimationItem['UserNote'] }}
                                @if(!empty($EstimationItem['Comment']))
                                {{ $EstimationItem['Comment'] }} 
                                @endif
                                @if(!empty($EstimationItem['Note']))
                                {{ $EstimationItem['Note'] }}
                                @endif
                                @if(!empty($EstimationItem['Comments']))
                                {{ $EstimationItem['Comments'] }}
                                @endif
                                </p>
                            </td>
                        </tr>
                        @endforeach
                        @endforeach
                        <tr class="text-right pd-2 room-items">
                            <td colspan="5">
                                <p class="text-right mr-0" >Total &#8594;</p>
                            </td>
                            <td class="text-right brand-bg-color text-vertical-align">
                                &#8377;{{ money_format('%!.0n', $SumAmount['Total'][$PricePackages[0]['Id']]) }}
                            </td>
                            <td class="text-right hechpe-bg-color text-vertical-align">
                                &#8377;{{ money_format('%!.0n', $SumAmount['Total'][$PricePackages[1]['Id']]) }}
                            </td>
                            <td class="text-right market-bg-color text-vertical-align">
                                &#8377;{{ money_format('%!.0n', $SumAmount['Total'][$PricePackages[2]['Id']]) }}
                            </td>
                            <td></td>
                        </tr>
                        @endif
                    </tbody>
                </table>
		@else
               <div class="col-xs-12 text-center"><b>No Items Found</b></div>
               @endif
          </div> 
	      @include('quickest.Notes')
    </div>
@endsection