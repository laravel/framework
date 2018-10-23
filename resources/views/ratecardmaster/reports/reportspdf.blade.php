@extends('layouts/Pdfs/PDFTemplate')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border text-center pd-5"><b>RateCard Item Master Report</b></div>
            <div class="box-body pd-0">
                @if(count($RateCardItems) === 0)
                <div class="callout callout-info">
                    <h4>No Data Available!</h4>
                    <p>No Items' Current RateCards are available for the selected City. Choose another City or Add an Item.</p>
                </div>
                @else
                 <table class="table table-striped table-bordered"> 
                    <thead class="bg-light-blue text-center">
                       <tr style="page-break-inside: avoid !important; font-size: 12px;">
                            @if($ViewType)
                            <th rowspan="2" class="text-vertical-align text-center">#</th>
                            <th rowspan="2" class="text-vertical-align text-center">Name</th>
                            <th rowspan="2" class="text-vertical-align text-center">Unit</th>                         
                            @foreach($PricePackages as $Key => $PricePackage)
                            <th colspan="2" class="rate-text-center">{{$PricePackage->Name}}</th>
                            @endforeach     
                            @else
                            <th class="text-vertical-align text-center">#</th>
                            <th class="text-vertical-align text-center">Name</th>
                            <th class="text-vertical-align text-center" width="8%">Unit</th>  
                            <th class="text-vertical-align text-center">Customer Rate(&#8377;)</th> 
                            <th class="text-vertical-align text-center">Vendor Rate (&#8377;)</th> 
                            <th class="text-vertical-align text-center">Price Package</th>
                            <th class="text-vertical-align text-center" width="11%">Start Date</th>
                            <th class="text-vertical-align text-center" width="13%">Created Date</th>
                            @endif
                        </tr>
                        @if($ViewType)
                        <tr>
                            @foreach($PricePackages as $Key => $PricePackage)
                            <th  class="text-center"><span>Customer Rate (&#8377;)</span></th>
                            <th  class="text-center">Vendor Rate (&#8377;)</th>
                            @endforeach
                        </tr>
                        @endif
                    </thead>
                    <tbody>
                        <?php $ItemsCounter = 1; ?>
                            @foreach($RateCardItems as $Key => $Item)
                            @if($ViewType)
                            <tr class="pd-2" style="page-break-inside: avoid !important;"> 
                                <td class="text-vertical-align text-center">{{ $ItemsCounter }}</td>
                                <td class="text-vertical-align">{{$Item->Name}}</td>
                                <td class="text-vertical-align">{{$Item->Unit}}</td>
                                @foreach($Item->PricePackages as $PricePackageKey => $PricePackage)
                                <td class="text-vertical-align text-center">{{$PricePackage->CurrentRateCard->CustomerRate !== 'N/A' ? money_format('%!i', $PricePackage->CurrentRateCard->CustomerRate) : $PricePackage->CurrentRateCard->CustomerRate}}</td>
                                <td class="text-vertical-align text-center">{{$PricePackage->CurrentRateCard->VendorRate !== 'N/A' ? money_format('%!i', $PricePackage->CurrentRateCard->VendorRate) : $PricePackage->CurrentRateCard->VendorRate}}</td>
                                @endforeach
                            </tr>
                            @else
                            @foreach($Item->PricePackages as $PricePackageKey => $PricePackage)
                            <tr class="pd-2" style="page-break-inside: avoid !important;">
                                <td class="text-vertical-align text-center">{{ $ItemsCounter }}</td>
                                <td class="text-vertical-align">{{$Item->Name}}</td>
                                <td class="text-vertical-align">{{$Item->Unit}}</td>
                                <td class="rate-text-center">{{money_format('%!i', $PricePackage->CurrentRateCard->CustomerRate)}}</td>
                                <td class="rate-text-center">{{money_format('%!i', $PricePackage->CurrentRateCard->VendorRate)}}</td>
                                <td class="rate-text-center"> {{$PricePackage->Name}}</td>
                                <td class="text-vertical-align">{{$PricePackage->CurrentRateCard->StartDate}}</td>
                                <td class="text-vertical-align"> {{$PricePackage->CurrentRateCard->CreatedDate}}</td>
                            </tr>
                            @endforeach
                            @endif
                            <?php $ItemsCounter++ ?>
                            @endforeach
                    </tbody>
                </table>
                @endif
            </div> 
    </div>
@endsection