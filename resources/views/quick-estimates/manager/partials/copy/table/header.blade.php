<thead>
    <tr>
        <th colspan="8" class="text-normal" width="60%">
            <i class="fa fa-info-circle text-light-blue" aria-hidden="true"></i>
            <i class="fa fa-exclamation-triangle text-black" aria-hidden="true"></i>
            <span class="text-center no-text-transform">
                <span>Indicates comments |</span>
                <i class="fa fa-image text-black" aria-hidden="true"></i>
                <span class="no-text-transform">Indicates reference images |</span>
                <span>All dimensions in feet |</span>
                <span>All amount in Indian Rupees ( <i class="fa fa-rupee"></i> )</span>
            </span>
        </th>
        @foreach ($pricePackages as $index => $pricePackage)
            <th class="text-center text-vertical-align amount-text {{ $pricePackage->class }}" width="10%">
                <i class="fa fa-rupee"></i>
                <span class="text-bold">{{ $pricePackage->totalsVueString($index) }}</span>
                <div class="text-bold">{{ $pricePackage->name }}</div>
            </th>
        @endforeach
        <th class="text-center bg-white text-vertical-align speciciations" width="10%">
            <a href="#" class="item-specifications">Specifications</a>
            <a href="#" class="item-ratecards">Ratecards</a>
        </th>
    </tr>
    <tr class="bg-blue text-center">
        <th class="text-center" width="4%">#</th>
        <th class="text-center" width="28%">Items (Units)</th>
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
</thead>
