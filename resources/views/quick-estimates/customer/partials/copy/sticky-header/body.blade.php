<tbody>
    <tr class="bg-blue text-center">
        <th class="text-center" width="4%">#</th>
        <th class="text-center" width="30%">Items (Units)</th>
        <th class="text-center" width="4%">
            <span class="header-tooltip" title="Who is going to pay whom?">Pay</span>
        </th>
        <th class="text-center cursor-help" width="4%">
            <span class="header-tooltip" title="Select, if this item is required for estimation">Req?</span>
        </th>
        <th class="text-center cursor-help" width="5%">
            <span class="header-tooltip" title="Required Quantity, if applicable">Nos</span>
        </th>
        <th class="text-center cursor-help" width="5%">
            <span class="header-tooltip" title="Width of the Item, if applicable">W</span>
        </th>
        <th class="text-center cursor-help" width="5%">
            <span class="header-tooltip" title="Height/Length of the Ite, if applicable">H/L</span>
        </th>
        <th class="text-center cursor-help" width="5%">
            <span class="header-tooltip" title="Depth of the Item, if applicable">D</span>
        </th>
        @foreach ($pricePackages as $pricePackage)
            <th class="text-center {{ $pricePackage->class }}" width="10%">Amount</th>
        @endforeach
        <th class="text-center" width="10%">Notes</th>
    </tr>
</tbody>
