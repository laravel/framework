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
        @foreach ($futureRatecards[$pricePackage->id] as $index => $futureRatecard)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-right">{{ money_format('%!i', $futureRatecard->customerRate) }}</td>
                <td class="text-right">{{ money_format('%!i', $futureRatecard->vendorRate) }}</td>
                <td class="text-center">{{ $futureRatecard->startDate }}</td>
                <td class="text-center">{{ $futureRatecard->createdBy ?? "N/A" }}</td>
                <td class="text-center">{{ $futureRatecard->createdAt }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
