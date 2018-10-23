<tbody>
    <tr class="bg-blue text-center">
        <th class="text-center" width="5%">#</th>
        <th class="text-center" width="30%">Items (Units)</th>
        <th class="text-center" width="5%">
            <span class="header-tooltip" title="Who is going to pay whom?">Pay</span>
        </th>
        <th class="text-center cursor-help" width="5%">
            <span class="header-tooltip" title="Required Quantity, if applicable">Nos</span>
        </th>
        <th class="text-center cursor-help" width="10%">
            <span class="header-tooltip no-text-transform" title="Dimensions of the Item, if applicable">W x H/L x D</span>
        </th>
        @foreach ($estimate->totals as $total)
            <th class="text-center {{ $total->class }}" width="10%">Amount</th>
        @endforeach
        <th class="text-center" width="15%">Notes</th>
    </tr>
</tbody>
