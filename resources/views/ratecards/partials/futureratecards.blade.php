@foreach($PricePackages as $Key => $PricePackage)
<div class="tab-pane" id="{{$PricePackage->Id}}">
    @if(isset($FutureRateCards["$PricePackage->Id"]))
    <table class="table table-bordered table-striped">
        <thead class="bg-light-blue text-center">
            <tr>
                <th class="text-vertical-align" width="7%">S.No</th>
                <th class="text-vertical-align text-center" width="17%">Customer Rate (&#8377;)</th>
                <th class="text-vertical-align text-center" width="16%">Vendor Rate (&#8377;)</th>
                <th class="text-vertical-align text-center" width="15%">Start Date</th>
                <th class="text-vertical-align text-center" width="24%">Created By</th>
                <th class="text-vertical-align text-center" width="21%">Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php $FutureRateCardCounter = 1; ?>
            @foreach($FutureRateCards["$PricePackage->Id"] as $FutureRateCard)
            <tr>
                <td class="text-center">{{$FutureRateCardCounter}}</td>
                <td class="text-right">{{money_format('%!i', $FutureRateCard->CustomerRate)}}</td>
                <td class="text-right">{{money_format('%!i', $FutureRateCard->VendorRate)}}</td>
                <td class="text-center">{{$FutureRateCard->StartDate}}</td>
                <td class="text-center">{{$FutureRateCard->CreatedBy ?? "N/A"}}</td>
                <td class="text-center">{{$FutureRateCard->CreatedAt}}</td>
            </tr>
            <?php $FutureRateCardCounter++; ?>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="alert alert-info" style="margin-top:0.5em;margin-bottom:0.8em;">No Future RateCards are available for {{$PricePackage->Name}} Specification.</div>
    @endif
</div>
@endforeach
