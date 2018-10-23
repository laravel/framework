<div class="box-body table-responsive">
    <table class="table table-striped table-bordered"> 
        <thead class="bg-light-blue text-center">
            <tr>
                @if($ViewType == 1)
                <th rowspan="2" class="rate-text-center" style="text-align:center;width:8">S.No</th>
                <th rowspan="2" class="rate-text-center" style="text-align:center;">Name</th>
                <th rowspan="2" class="rate-text-center" style="text-align:center;">Unit</th>                         
                @foreach($PricePackages as $Key => $PricePackage)
                <th colspan="2"class="rate-text-center" style="text-align:center;">{{$PricePackage->Name}}</th>
                @endforeach     
                @else
                <th class="rate-text-center" style="text-align:center;width:8">S.No</th>
                <th class="rate-text-center" style="text-align:center;">Name</th>
                <th class="rate-text-center" style="text-align:center;">Unit</th>  
                <th class="rate-text-center" style="text-align:center;">Customer Rate(&#8377;)</th> 
                <th class="rate-text-center" style="text-align:center;">Vendor Rate (&#8377;)</th> 
                <th class="rate-text-center" style="text-align:center;">Price Package</th>
                <th class="rate-text-center" style="text-align:center;">Start Date</th>
                <th class="rate-text-center" style="text-align:center;">Created Date</th>
                @endif
            </tr>
            @if($ViewType == 1)
            <tr>
                <th></th>
                <th></th>
                <th></th>
                @foreach($PricePackages as $Key => $PricePackage)
                <th class="rate-text-center" style="text-align:center;"><span>Customer Rate (&#8377;)</span></th>
                <th class="rate-text-center " style="text-transform:none;text-align:center;"><span>Vendor Rate (&#8377;)</span></th>
                @endforeach
            </tr>
            @endif
        </thead>
        <tbody>
            <?php $ItemsCounter = 1; ?>
            @foreach($RateCardItems as $Key => $Item)
            @if($ViewType)
            <tr> 
                <td class="text-vertical-align text-center">{{ $ItemsCounter }}</td>
                <td class="text-vertical-align">{{$Item->Name}}</td>
                <td class="text-vertical-align">{{$Item->Unit}}</td>
                @foreach($Item->PricePackages as $PricePackageKey => $PricePackage)
                <td class="text-vertical-align text-center">{{$PricePackage->CurrentRateCard->CustomerRate}}</td>
                <td class="text-vertical-align text-center">{{$PricePackage->CurrentRateCard->VendorRate}}</td>
                @endforeach
            </tr>
            @else
            @foreach($Item->PricePackages as $PricePackageKey => $PricePackage)
            <tr>
                <td class="text-vertical-align text-center">{{ $ItemsCounter }}</td>
                <td class="text-vertical-align">{{$Item->Name}}</td>
                <td class="text-vertical-align">{{$Item->Unit}}</td>
                <td class="rate-text-center">{{$PricePackage->CurrentRateCard->CustomerRate}}</td>
                <td class="rate-text-center">{{$PricePackage->CurrentRateCard->VendorRate}}</td>
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
</div>
